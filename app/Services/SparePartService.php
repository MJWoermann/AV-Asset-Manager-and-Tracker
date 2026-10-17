<?php

namespace App\Services;

use App\Enums\FileCategory;
use App\Enums\SparePartLifecycleStatus;
use App\Enums\SparePartMovementType;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStandbyMode;
use App\Enums\SparePartStockStatus;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\SparePart;
use App\Models\SparePartMovement;
use App\Models\User;
use App\Support\SparePartLuhn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SparePartService
{
    public function __construct(private FileAttachmentService $attachments) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $compatibleModelIds
     * @param  list<int>  $compatibleAssetIds
     */
    public function create(array $data, array $compatibleModelIds = [], array $compatibleAssetIds = []): SparePart
    {
        $code = $this->resolveCode($data['code'] ?? null);

        return DB::transaction(function () use ($data, $compatibleModelIds, $compatibleAssetIds, $code): SparePart {
            $openingOnHand = (int) ($data['quantity_on_hand'] ?? 0);
            $data['quantity_on_hand'] = 0;

            $part = SparePart::query()->create($this->partAttributes($data, $code));

            $this->syncCompatibles($part, $compatibleModelIds, $compatibleAssetIds);

            if ($openingOnHand > 0) {
                $this->record($part, [
                    'type' => SparePartMovementType::Count,
                    'quantity' => $openingOnHand,
                    'notes' => 'Opening balance',
                ], null, allowLifecycleWarning: false);
            }

            return $part->fresh([
                'manufacturer',
                'manufacturerModel',
                'location',
                'linkedAsset',
                'supersededBy',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $compatibleModelIds
     * @param  list<int>  $compatibleAssetIds
     */
    public function update(
        SparePart $part,
        array $data,
        array $compatibleModelIds = [],
        array $compatibleAssetIds = [],
    ): SparePart {
        if (isset($data['code'])) {
            $code = $this->resolveCode($data['code'], $part);
            $data['code'] = $code;
        }

        return DB::transaction(function () use ($part, $data, $compatibleModelIds, $compatibleAssetIds): SparePart {
            $part->update($this->partAttributes($data, $part->code, existing: $part));

            $this->syncCompatibles($part, $compatibleModelIds, $compatibleAssetIds);

            return $part->fresh([
                'manufacturer',
                'manufacturerModel',
                'location',
                'linkedAsset',
                'supersededBy',
                'supersedes',
            ]);
        });
    }

    public function supersede(SparePart $part, SparePart $successor, ?User $user = null): SparePart
    {
        if ($part->id === $successor->id) {
            throw ValidationException::withMessages([
                'superseded_by_id' => 'A spare cannot supersede itself.',
            ]);
        }

        if ($this->wouldCreateCycle($part, $successor)) {
            throw ValidationException::withMessages([
                'superseded_by_id' => 'That successor would create a supersession cycle.',
            ]);
        }

        $part->update([
            'lifecycle_status' => SparePartLifecycleStatus::Superseded,
            'superseded_by_id' => $successor->id,
            'superseded_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $user?->id,
            'auditable_type' => SparePart::class,
            'auditable_id' => $part->id,
            'action' => 'spare_supersede',
            'old_values' => [],
            'new_values' => [
                'superseded_by_id' => $successor->id,
                'successor_code' => $successor->code,
            ],
            'created_at' => now(),
        ]);

        return $part->fresh(['supersededBy']);
    }

    /**
     * @param  array{
     *     type: SparePartMovementType|string,
     *     quantity?: int|null,
     *     quantity_delta?: int|null,
     *     against_asset_id?: int|null,
     *     repair_id?: int|null,
     *     reason?: string|null,
     *     ticket_ref?: string|null,
     *     quoting_status?: string|null,
     *     notes?: string|null,
     *     recorded_at?: \DateTimeInterface|string|null,
     * }  $input
     * @param  list<UploadedFile>|UploadedFile|null  $pdfs
     */
    public function record(
        SparePart $part,
        array $input,
        ?User $user = null,
        UploadedFile|array|null $pdfs = null,
        bool $allowLifecycleWarning = true,
    ): SparePartMovement {
        $type = $input['type'] instanceof SparePartMovementType
            ? $input['type']
            : SparePartMovementType::from((string) $input['type']);

        if ($type->requiresAssetAndReason()) {
            $assetId = (int) ($input['against_asset_id'] ?? 0);
            if ($assetId < 1 || ! Asset::query()->whereKey($assetId)->exists()) {
                throw ValidationException::withMessages([
                    'against_asset_id' => 'Select the asset this movement applies to.',
                ]);
            }

            $reason = trim((string) ($input['reason'] ?? ''));
            if ($reason === '') {
                throw ValidationException::withMessages([
                    'reason' => 'A reason is required for use and replace movements.',
                ]);
            }
        }

        if (
            $allowLifecycleWarning
            && $part->isNonActiveLifecycle()
            && in_array($type, [SparePartMovementType::Restock, SparePartMovementType::Count], true)
        ) {
            throw ValidationException::withMessages([
                'type' => 'Restock and count are discouraged on end-of-life or superseded lines. Use the successor instead.',
            ]);
        }

        $before = max(0, (int) $part->quantity_on_hand);
        $after = $this->resolveQuantityAfter($type, $before, $input);

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'On-hand quantity cannot go below zero.',
            ]);
        }

        $uploads = $this->normalizeUploads($pdfs);
        if ($uploads !== [] && $type !== SparePartMovementType::Restock) {
            throw ValidationException::withMessages([
                'pdfs' => 'PDF attachments are only allowed on restock events.',
            ]);
        }

        return DB::transaction(function () use ($part, $type, $before, $after, $input, $user, $uploads): SparePartMovement {
            $movement = SparePartMovement::query()->create([
                'spare_part_id' => $part->id,
                'type' => $type,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_delta' => $after - $before,
                'against_asset_id' => $input['against_asset_id'] ?? null,
                'repair_id' => $input['repair_id'] ?? null,
                'reason' => $this->nullableString($input['reason'] ?? null),
                'ticket_ref' => $this->nullableString($input['ticket_ref'] ?? null),
                'quoting_status' => $this->nullableString($input['quoting_status'] ?? null),
                'user_id' => $user?->id,
                'notes' => $this->nullableString($input['notes'] ?? null),
                'recorded_at' => $input['recorded_at'] ?? now(),
            ]);

            $part->update(['quantity_on_hand' => $after]);

            foreach ($uploads as $upload) {
                $this->attachments->storeUpload($upload, [
                    'title' => 'Restock '.$part->code,
                    'category' => FileCategory::Invoice->value,
                ], $movement);
            }

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => SparePart::class,
                'auditable_id' => $part->id,
                'action' => $type->auditAction(),
                'old_values' => ['quantity_on_hand' => $before],
                'new_values' => [
                    'quantity_on_hand' => $after,
                    'quantity_delta' => $after - $before,
                    'spare_part_movement_id' => $movement->id,
                    'type' => $type->value,
                    'against_asset_id' => $movement->against_asset_id,
                    'reason' => $movement->reason,
                    'ticket_ref' => $movement->ticket_ref,
                ],
                'created_at' => now(),
            ]);

            if ($movement->against_asset_id) {
                AuditLog::create([
                    'user_id' => $user?->id,
                    'auditable_type' => Asset::class,
                    'auditable_id' => $movement->against_asset_id,
                    'action' => $type->auditAction(),
                    'old_values' => [],
                    'new_values' => [
                        'spare_part_id' => $part->id,
                        'spare_code' => $part->code,
                        'component' => $part->component,
                        'quantity_delta' => $movement->quantity_delta,
                        'reason' => $movement->reason,
                        'ticket_ref' => $movement->ticket_ref,
                    ],
                    'created_at' => now(),
                ]);
            }

            return $movement->fresh(['againstAsset', 'user', 'files']);
        });
    }

    public function ensureBaseline(SparePart $part, ?User $user = null): ?SparePartMovement
    {
        if ($part->movements()->exists()) {
            return null;
        }

        $qty = max(0, (int) $part->quantity_on_hand);

        return $this->record($part, [
            'type' => SparePartMovementType::Count,
            'quantity' => $qty,
            'notes' => 'Initial stock baseline',
        ], $user, allowLifecycleWarning: false);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function partAttributes(array $data, string $code, ?SparePart $existing = null): array
    {
        // Create form omits these; MySQL rejects explicit NULL even when columns have defaults.
        $resolvedStockStatus = $data['stock_status']
            ?? $existing?->stock_status
            ?? SparePartStockStatus::InStock;
        $resolvedStandby = $data['standby_mode']
            ?? $existing?->standby_mode
            ?? SparePartStandbyMode::None;
        $resolvedLifecycle = $data['lifecycle_status']
            ?? $existing?->lifecycle_status
            ?? SparePartLifecycleStatus::Active;
        $resolvedSeverity = $data['severity']
            ?? $existing?->severity
            ?? SparePartSeverity::Moderate;

        $attrs = [
            'code' => $code,
            'kind' => $data['kind'] ?? $existing?->kind,
            'manufacturer_id' => $data['manufacturer_id'] ?? $existing?->manufacturer_id,
            'manufacturer_model_id' => $data['manufacturer_model_id'] ?? $existing?->manufacturer_model_id,
            'product_code' => $data['product_code'] ?? $existing?->product_code,
            'component' => $data['component'] ?? $existing?->component,
            'quantity_deployed' => (int) ($data['quantity_deployed'] ?? $existing?->quantity_deployed ?? 0),
            'quantity_on_hand' => (int) ($data['quantity_on_hand'] ?? $existing?->quantity_on_hand ?? 0),
            'min_holding' => $data['min_holding'] ?? $existing?->min_holding,
            'max_holding' => $data['max_holding'] ?? $existing?->max_holding,
            'location_id' => $data['location_id'] ?? $existing?->location_id,
            'shelf_location' => $data['shelf_location'] ?? $existing?->shelf_location,
            'stock_status' => $resolvedStockStatus,
            'standby_mode' => $resolvedStandby,
            'supplier' => $data['supplier'] ?? $existing?->supplier,
            'unit_cost' => $data['unit_cost'] ?? $existing?->unit_cost,
            'lead_time' => $data['lead_time'] ?? $existing?->lead_time,
            'replacement_time' => $data['replacement_time'] ?? $existing?->replacement_time,
            'lead_time_days' => $data['lead_time_days'] ?? $existing?->lead_time_days,
            'replacement_time_minutes' => $data['replacement_time_minutes'] ?? $existing?->replacement_time_minutes,
            'severity' => $resolvedSeverity,
            'lifecycle_status' => $resolvedLifecycle,
            'superseded_by_id' => $data['superseded_by_id'] ?? $existing?->superseded_by_id,
            'superseded_at' => $data['superseded_at'] ?? $existing?->superseded_at,
            'ordered_at' => $data['ordered_at'] ?? $existing?->ordered_at,
            'expected_arrival_at' => $data['expected_arrival_at'] ?? $existing?->expected_arrival_at,
            'asset_id' => $data['asset_id'] ?? $existing?->asset_id,
            'shelf_tag_layout_id' => $data['shelf_tag_layout_id'] ?? $existing?->shelf_tag_layout_id,
            'notes' => $data['notes'] ?? $existing?->notes,
        ];

        return $attrs;
    }

    /**
     * @param  list<int>  $compatibleModelIds
     * @param  list<int>  $compatibleAssetIds
     */
    protected function syncCompatibles(SparePart $part, array $compatibleModelIds, array $compatibleAssetIds): void
    {
        $part->compatibleModels()->sync(array_values(array_unique(array_map('intval', $compatibleModelIds))));
        $part->compatibleAssets()->sync(array_values(array_unique(array_map('intval', $compatibleAssetIds))));
    }

    protected function resolveCode(?string $code, ?SparePart $existing = null): string
    {
        if ($code === null || trim($code) === '') {
            return $existing?->code ?? SparePartLuhn::generate();
        }

        $normalized = preg_replace('/\s+/', '', trim($code)) ?? '';

        if (! SparePartLuhn::isValid($normalized)) {
            throw ValidationException::withMessages([
                'code' => 'Spare code must pass the Luhn check.',
            ]);
        }

        $duplicate = SparePart::query()
            ->where('code', $normalized)
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'code' => 'That spare code is already in use.',
            ]);
        }

        return $normalized;
    }

    protected function wouldCreateCycle(SparePart $part, SparePart $successor): bool
    {
        $visited = [];
        $current = $successor;

        while ($current->superseded_by_id) {
            if ($current->id === $part->id) {
                return true;
            }
            if (isset($visited[$current->id])) {
                return true;
            }
            $visited[$current->id] = true;
            $current = $current->supersededBy ?? SparePart::query()->find($current->superseded_by_id);
            if (! $current) {
                break;
            }
        }

        return false;
    }

    /**
     * @param  array{quantity?: int|null, quantity_delta?: int|null}  $input
     */
    protected function resolveQuantityAfter(SparePartMovementType $type, int $before, array $input): int
    {
        return match ($type) {
            SparePartMovementType::Count => $this->requireNonNegativeInt($input['quantity'] ?? null, 'quantity'),
            SparePartMovementType::Restock => $before + $this->requirePositiveInt($input['quantity'] ?? $input['quantity_delta'] ?? null, 'quantity'),
            SparePartMovementType::Use, SparePartMovementType::Replace => $before - $this->requirePositiveInt($input['quantity'] ?? 1, 'quantity'),
            SparePartMovementType::Adjustment => $before + $this->requireInt($input['quantity_delta'] ?? null, 'quantity_delta'),
        };
    }

    protected function requireNonNegativeInt(mixed $value, string $field): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => 'A valid quantity is required.',
            ]);
        }

        $int = (int) $value;
        if ($int < 0) {
            throw ValidationException::withMessages([
                $field => 'Quantity cannot be negative.',
            ]);
        }

        return $int;
    }

    protected function requirePositiveInt(mixed $value, string $field): int
    {
        $int = $this->requireNonNegativeInt($value, $field);
        if ($int < 1) {
            throw ValidationException::withMessages([
                $field => 'Quantity must be at least 1.',
            ]);
        }

        return $int;
    }

    protected function requireInt(mixed $value, string $field): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => 'A valid quantity delta is required.',
            ]);
        }

        return (int) $value;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return list<UploadedFile>
     */
    protected function normalizeUploads(UploadedFile|array|null $pdfs): array
    {
        if ($pdfs === null) {
            return [];
        }

        $list = is_array($pdfs) ? $pdfs : [$pdfs];

        return array_values(array_filter($list, fn ($file): bool => $file instanceof UploadedFile));
    }
}
