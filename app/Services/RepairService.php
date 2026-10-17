<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\MaintenanceLogType;
use App\Enums\NotificationEventKey;
use App\Enums\RepairStatus;
use App\Enums\SparePartMovementType;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\AuditLog;
use App\Models\File;
use App\Models\Repair;
use App\Models\RepairProcedure;
use App\Models\RepairStep;
use App\Models\SparePart;
use App\Models\SparePartMovement;
use App\Models\User;
use App\Support\EmailIntegrationSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepairService
{
    public function __construct(
        private AssetMaintenanceLogService $maintenanceLogs,
        private SparePartService $spareParts,
    ) {}

    /**
     * @param  array{
     *     summary?: string|null,
     *     notes?: string|null,
     *     repair_procedure_id?: int|null,
     *     assignee_user_id?: int|null,
     *     set_asset_maintenance?: bool,
     *     highlight_spare_part_id?: int|null,
     * }  $options
     */
    public function startFromFault(
        Asset $asset,
        AssetMaintenanceLog $fault,
        ?User $user = null,
        array $options = [],
    ): Repair {
        $this->assertFaultBelongsToAsset($asset, $fault);

        if ($this->activeRepairForFault($fault) !== null) {
            throw ValidationException::withMessages([
                'maintenance_log_id' => 'This fault already has an open repair.',
            ]);
        }

        $procedure = $this->resolveProcedure($options['repair_procedure_id'] ?? null, $asset, null);

        return DB::transaction(function () use ($asset, $fault, $user, $options, $procedure): Repair {
            $setMaintenance = (bool) ($options['set_asset_maintenance'] ?? false);
            $statusBefore = null;
            $statusAfter = null;

            if ($setMaintenance) {
                $current = $asset->status instanceof AssetStatus
                    ? $asset->status
                    : AssetStatus::from((string) $asset->status);

                if ($current === AssetStatus::Available || $current === AssetStatus::InService) {
                    $statusBefore = $current->value;
                    $statusAfter = AssetStatus::Maintenance->value;
                    $asset->update(['status' => AssetStatus::Maintenance]);
                }
            }

            $summary = trim((string) ($options['summary'] ?? ''));
            if ($summary === '') {
                $summary = 'Repair: '.$fault->summary;
            }

            $repair = Repair::query()->create([
                'asset_id' => $asset->id,
                'maintenance_log_id' => $fault->id,
                'repair_procedure_id' => $procedure?->id,
                'status' => RepairStatus::Open,
                'assignee_user_id' => $options['assignee_user_id'] ?? $user?->id,
                'summary' => $summary,
                'notes' => $this->nullableString($options['notes'] ?? null),
                'started_at' => now(),
                'asset_status_before' => $statusBefore,
                'asset_status_after' => $statusAfter,
            ]);

            if ($procedure) {
                $this->cloneProcedureSteps($repair, $procedure);
            }

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'repair_started',
                'old_values' => [],
                'new_values' => [
                    'repair_id' => $repair->id,
                    'maintenance_log_id' => $fault->id,
                    'repair_procedure_id' => $procedure?->id,
                    'summary' => $repair->summary,
                ],
                'created_at' => now(),
            ]);

            $this->dispatchNotification(NotificationEventKey::RepairStarted, $asset, $user, [
                'repair.summary' => $repair->summary,
                'repair.id' => (string) $repair->id,
            ]);

            return $repair->fresh([
                'asset',
                'maintenanceLog',
                'procedure.recommendedSpareParts',
                'steps',
                'assignee',
            ]);
        });
    }

    /**
     * @param  array{
     *     summary?: string|null,
     *     notes?: string|null,
     *     repair_procedure_id?: int|null,
     *     assignee_user_id?: int|null,
     *     set_asset_maintenance?: bool,
     * }  $options
     */
    public function startFromSpare(
        SparePart $spare,
        Asset $asset,
        ?User $user = null,
        array $options = [],
    ): Repair {
        $summary = trim((string) ($options['summary'] ?? ''));
        if ($summary === '') {
            $summary = 'Repair: '.$spare->displayLabel();
        }

        $options['summary'] = $summary;
        $options['set_asset_maintenance'] = $options['set_asset_maintenance'] ?? true;
        $options['highlight_spare_part_id'] = $spare->id;

        if (! isset($options['repair_procedure_id'])) {
            $suggested = $this->suggestProcedures($asset, $spare)->first();
            if ($suggested) {
                $options['repair_procedure_id'] = $suggested->id;
            }
        }

        $fault = $this->maintenanceLogs->create($asset, [
            'type' => MaintenanceLogType::Fault,
            'summary' => $summary,
            'body' => $this->nullableString($options['notes'] ?? null),
            'status' => AssetStatus::Maintenance->value,
        ], $user);

        // Fault create may already have set status; avoid double-setting in startFromFault.
        $options['set_asset_maintenance'] = false;

        return $this->startFromFault($asset, $fault, $user, $options);
    }

    /**
     * @param  array{
     *     summary: string,
     *     body?: string|null,
     *     notes?: string|null,
     *     status?: AssetStatus|string|null,
     *     repair_procedure_id?: int|null,
     *     assignee_user_id?: int|null,
     *     set_asset_maintenance?: bool,
     * }  $options
     */
    public function startWithNewFault(
        Asset $asset,
        array $options,
        ?User $user = null,
    ): Repair {
        $summary = trim((string) ($options['summary'] ?? ''));
        if ($summary === '') {
            throw ValidationException::withMessages([
                'summary' => 'A summary is required to start a repair.',
            ]);
        }

        $faultInput = [
            'type' => MaintenanceLogType::Fault,
            'summary' => $summary,
            'body' => $this->nullableString($options['body'] ?? $options['notes'] ?? null),
        ];

        if (filled($options['status'] ?? null)) {
            $faultInput['status'] = $options['status'];
            $options['set_asset_maintenance'] = false;
        } else {
            $options['set_asset_maintenance'] = $options['set_asset_maintenance'] ?? true;
        }

        $fault = $this->maintenanceLogs->create($asset, $faultInput, $user);

        return $this->startFromFault($asset, $fault, $user, [
            'summary' => $summary,
            'notes' => $options['notes'] ?? null,
            'repair_procedure_id' => $options['repair_procedure_id'] ?? null,
            'assignee_user_id' => $options['assignee_user_id'] ?? null,
            'set_asset_maintenance' => $options['set_asset_maintenance'] ?? false,
        ]);
    }

    public function markStepComplete(RepairStep $step, ?User $user = null, ?string $notes = null): RepairStep
    {
        $repair = $step->repair;
        if ($repair === null || ! $repair->isActive()) {
            throw ValidationException::withMessages([
                'step' => 'Steps can only be updated on an open repair.',
            ]);
        }

        $step->update([
            'completed_at' => now(),
            'completed_by_user_id' => $user?->id,
            'notes' => $notes !== null ? $this->nullableString($notes) : $step->notes,
        ]);

        if ($repair->status === RepairStatus::Open) {
            $repair->update(['status' => RepairStatus::InProgress]);
        }

        return $step->fresh(['completedBy']);
    }

    public function markStepIncomplete(RepairStep $step): RepairStep
    {
        $repair = $step->repair;
        if ($repair === null || ! $repair->isActive()) {
            throw ValidationException::withMessages([
                'step' => 'Steps can only be updated on an open repair.',
            ]);
        }

        $step->update([
            'completed_at' => null,
            'completed_by_user_id' => null,
        ]);

        return $step->fresh();
    }

    /**
     * @param  array{title: string, body?: string|null, is_required?: bool}  $input
     */
    public function addAdHocStep(Repair $repair, array $input): RepairStep
    {
        if (! $repair->isActive()) {
            throw ValidationException::withMessages([
                'step' => 'Steps can only be added to an open repair.',
            ]);
        }

        $title = trim((string) ($input['title'] ?? ''));
        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => 'Step title is required.',
            ]);
        }

        $maxOrder = (int) $repair->steps()->max('sort_order');

        return RepairStep::query()->create([
            'repair_id' => $repair->id,
            'sort_order' => $maxOrder + 1,
            'title' => $title,
            'body' => $this->nullableString($input['body'] ?? null),
            'is_required' => (bool) ($input['is_required'] ?? true),
        ]);
    }

    /**
     * @param  array{
     *     type?: SparePartMovementType|string,
     *     quantity: int,
     *     reason: string,
     *     notes?: string|null,
     * }  $input
     */
    public function recordPartUse(Repair $repair, SparePart $spare, array $input, ?User $user = null): SparePartMovement
    {
        if (! $repair->isActive()) {
            throw ValidationException::withMessages([
                'repair' => 'Parts can only be issued on an open repair.',
            ]);
        }

        $type = isset($input['type'])
            ? ($input['type'] instanceof SparePartMovementType
                ? $input['type']
                : SparePartMovementType::from((string) $input['type']))
            : SparePartMovementType::Use;

        if (! in_array($type, [SparePartMovementType::Use, SparePartMovementType::Replace], true)) {
            throw ValidationException::withMessages([
                'type' => 'Only use or replace movements are allowed from a repair.',
            ]);
        }

        $movement = $this->spareParts->record($spare, [
            'type' => $type,
            'quantity' => (int) $input['quantity'],
            'against_asset_id' => $repair->asset_id,
            'repair_id' => $repair->id,
            'reason' => (string) $input['reason'],
            'notes' => $input['notes'] ?? null,
            'ticket_ref' => 'repair:'.$repair->id,
        ], $user);

        if ($repair->status === RepairStatus::Open) {
            $repair->update(['status' => RepairStatus::InProgress]);
        }

        return $movement;
    }

    /**
     * @param  array{
     *     resolution_summary: string,
     *     asset_status?: AssetStatus|string|null,
     *     notes?: string|null,
     * }  $input
     */
    public function complete(Repair $repair, array $input, ?User $user = null): Repair
    {
        if (! $repair->isActive()) {
            throw ValidationException::withMessages([
                'repair' => 'This repair is already closed.',
            ]);
        }

        $incomplete = $repair->steps()
            ->where('is_required', true)
            ->whereNull('completed_at')
            ->count();

        if ($incomplete > 0) {
            throw ValidationException::withMessages([
                'steps' => 'Complete all required checklist steps before finishing the repair.',
            ]);
        }

        $resolution = trim((string) ($input['resolution_summary'] ?? ''));
        if ($resolution === '') {
            throw ValidationException::withMessages([
                'resolution_summary' => 'A resolution summary is required.',
            ]);
        }

        return DB::transaction(function () use ($repair, $input, $user, $resolution): Repair {
            $asset = $repair->asset()->lockForUpdate()->firstOrFail();
            $statusAfter = null;

            if (filled($input['asset_status'] ?? null)) {
                $newStatus = $input['asset_status'] instanceof AssetStatus
                    ? $input['asset_status']
                    : AssetStatus::from((string) $input['asset_status']);

                $current = $asset->status instanceof AssetStatus
                    ? $asset->status
                    : AssetStatus::from((string) $asset->status);

                if ($newStatus !== $current) {
                    $statusAfter = $newStatus->value;
                    $asset->update(['status' => $newStatus]);
                }
            }

            $repair->update([
                'status' => RepairStatus::Completed,
                'resolution_summary' => $resolution,
                'notes' => $this->nullableString($input['notes'] ?? $repair->notes),
                'completed_at' => now(),
                'completed_by_user_id' => $user?->id,
                'asset_status_after' => $statusAfter ?? $repair->asset_status_after,
            ]);

            $this->maintenanceLogs->create($asset, [
                'type' => MaintenanceLogType::Service,
                'summary' => 'Repair completed: '.$repair->summary,
                'body' => $resolution,
            ], $user);

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'repair_completed',
                'old_values' => [],
                'new_values' => [
                    'repair_id' => $repair->id,
                    'resolution_summary' => $resolution,
                    'asset_status_after' => $statusAfter,
                ],
                'created_at' => now(),
            ]);

            $this->dispatchNotification(NotificationEventKey::RepairCompleted, $asset, $user, [
                'repair.summary' => $repair->summary,
                'repair.resolution' => $resolution,
                'repair.id' => (string) $repair->id,
            ]);

            // Auto-return any open checkout linked to this repair.
            app(AssetCheckoutService::class)->returnOpenForRepair(
                $repair->fresh(),
                $user,
                skipStatusRestore: $statusAfter !== null,
            );

            return $repair->fresh(['asset', 'maintenanceLog', 'steps', 'completedBy']);
        });
    }

    public function cancel(Repair $repair, string $reason, ?User $user = null): Repair
    {
        if (! $repair->isActive()) {
            throw ValidationException::withMessages([
                'repair' => 'This repair is already closed.',
            ]);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A cancellation reason is required.',
            ]);
        }

        return DB::transaction(function () use ($repair, $reason, $user): Repair {
            $asset = $repair->asset;

            $repair->update([
                'status' => RepairStatus::Cancelled,
                'resolution_summary' => $reason,
                'completed_at' => now(),
                'completed_by_user_id' => $user?->id,
            ]);

            $this->maintenanceLogs->create($asset, [
                'type' => MaintenanceLogType::Note,
                'summary' => 'Repair cancelled: '.$repair->summary,
                'body' => $reason,
            ], $user);

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'repair_cancelled',
                'old_values' => [],
                'new_values' => [
                    'repair_id' => $repair->id,
                    'reason' => $reason,
                ],
                'created_at' => now(),
            ]);

            return $repair->fresh(['asset', 'maintenanceLog', 'steps', 'completedBy']);
        });
    }

    /**
     * @return Collection<int, RepairProcedure>
     */
    public function suggestProcedures(Asset $asset, ?SparePart $spare = null): Collection
    {
        $byModel = collect();
        $byType = collect();
        $bySpare = collect();

        if ($spare?->id) {
            $bySpare = RepairProcedure::query()
                ->where('spare_part_id', $spare->id)
                ->orderBy('title')
                ->get();
        }

        if ($asset->manufacturer_model_id) {
            $byModel = RepairProcedure::query()
                ->where('manufacturer_model_id', $asset->manufacturer_model_id)
                ->orderBy('title')
                ->get();
        }

        if ($asset->item_type_id) {
            $byType = RepairProcedure::query()
                ->where('item_type_id', $asset->item_type_id)
                ->orderBy('title')
                ->get();
        }

        return $byModel
            ->concat($byType)
            ->concat($bySpare)
            ->unique('id')
            ->values();
    }

    /**
     * @return Collection<int, File>
     */
    public function guidanceFiles(Asset $asset, ?SparePart $spare = null, ?RepairProcedure $procedure = null): Collection
    {
        $categories = [FileCategory::Manual->value, FileCategory::Photo->value];
        $files = collect();

        $asset->loadMissing('files', 'itemType.files');
        $files = $files->merge($this->filterGuidanceFiles($asset->files, $categories));

        if ($asset->itemType) {
            $files = $files->merge($this->filterGuidanceFiles($asset->itemType->files, $categories));
        }

        if ($spare) {
            $spare->loadMissing('files');
            $files = $files->merge($this->filterGuidanceFiles($spare->files, $categories));
        }

        if ($procedure) {
            $procedure->loadMissing('files');
            $files = $files->merge($this->filterGuidanceFiles($procedure->files, $categories));
        }

        return $files->unique('id')->values();
    }

    public function activeRepairForFault(AssetMaintenanceLog $fault): ?Repair
    {
        return Repair::query()
            ->where('maintenance_log_id', $fault->id)
            ->active()
            ->first();
    }

    protected function assertFaultBelongsToAsset(Asset $asset, AssetMaintenanceLog $fault): void
    {
        if ((int) $fault->asset_id !== (int) $asset->id) {
            throw ValidationException::withMessages([
                'maintenance_log_id' => 'Fault does not belong to this asset.',
            ]);
        }

        $type = $fault->type instanceof MaintenanceLogType
            ? $fault->type
            : MaintenanceLogType::from((string) $fault->type);

        if ($type !== MaintenanceLogType::Fault) {
            throw ValidationException::withMessages([
                'maintenance_log_id' => 'Repairs can only start from a Fault log.',
            ]);
        }
    }

    protected function resolveProcedure(?int $procedureId, Asset $asset, ?SparePart $spare): ?RepairProcedure
    {
        if ($procedureId) {
            return RepairProcedure::query()->with('steps')->find($procedureId);
        }

        return $this->suggestProcedures($asset, $spare)->first();
    }

    protected function cloneProcedureSteps(Repair $repair, RepairProcedure $procedure): void
    {
        $procedure->loadMissing('steps');

        foreach ($procedure->steps as $index => $step) {
            RepairStep::query()->create([
                'repair_id' => $repair->id,
                'sort_order' => $step->sort_order ?? $index,
                'title' => $step->title,
                'body' => $step->body,
                'is_required' => $step->is_required,
            ]);
        }
    }

    /**
     * @param  Collection<int, File>|iterable<File>  $files
     * @param  list<string>  $categories
     * @return Collection<int, File>
     */
    protected function filterGuidanceFiles(iterable $files, array $categories): Collection
    {
        return collect($files)->filter(function (File $file) use ($categories): bool {
            $category = $file->category instanceof FileCategory
                ? $file->category->value
                : (string) $file->category;

            return in_array($category, $categories, true);
        });
    }

    /**
     * @param  array<string, string>  $context
     */
    protected function dispatchNotification(
        NotificationEventKey $key,
        Asset $asset,
        ?User $user,
        array $context,
    ): void {
        if (! EmailIntegrationSettings::isEnabled()) {
            return;
        }

        app(NotificationDispatchService::class)->dispatch($key, $asset, $user, $context);
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
