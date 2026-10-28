<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\CheckoutPurpose as SystemCheckoutPurpose;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\AuditLog;
use App\Models\CheckoutPurpose;
use App\Models\Repair;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetCheckoutService
{
    public function __construct(private RepairService $repairs) {}

    /**
     * @param  array{
     *     purpose: SystemCheckoutPurpose|string,
     *     checked_out_to_user_id?: int|null,
     *     checked_out_to_name?: string|null,
     *     checked_out_to_email?: string|null,
     *     checked_out_to_company?: string|null,
     *     location_id?: int|null,
     *     expected_return_at?: \DateTimeInterface|string|null,
     *     notes?: string|null,
     *     repair_id?: int|null,
     *     create_repair?: bool,
     *     repair_summary?: string|null,
     *     repair_notes?: string|null,
     * }  $input
     */
    public function checkout(Asset $asset, array $input, User $by): AssetCheckout
    {
        $slug = $input['purpose'] instanceof SystemCheckoutPurpose
            ? $input['purpose']->value
            : trim((string) $input['purpose']);

        $definition = CheckoutPurpose::query()->where('slug', $slug)->first();

        if ($definition === null || ! $definition->is_active) {
            throw ValidationException::withMessages([
                'purpose' => 'Choose an active checkout option.',
            ]);
        }

        $toUserId = isset($input['checked_out_to_user_id'])
            ? (int) $input['checked_out_to_user_id']
            : null;
        if ($toUserId === 0) {
            $toUserId = null;
        }

        $toName = $this->nullableString($input['checked_out_to_name'] ?? null);
        $toEmail = $this->nullableString($input['checked_out_to_email'] ?? null);
        $toCompany = $this->nullableString($input['checked_out_to_company'] ?? null);

        if ($toUserId === null && $toName === null) {
            throw ValidationException::withMessages([
                'checked_out_to_name' => 'Provide a user or a guest name for checkout.',
            ]);
        }

        if ($toEmail !== null && filter_var($toEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw ValidationException::withMessages([
                'checked_out_to_email' => 'The guest email must be a valid email address.',
            ]);
        }

        $open = AssetCheckout::query()
            ->where('asset_id', $asset->id)
            ->open()
            ->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'asset_id' => 'This asset already has an open checkout.',
            ]);
        }

        return DB::transaction(function () use ($asset, $input, $by, $definition, $toUserId, $toName, $toEmail, $toCompany): AssetCheckout {
            $current = $asset->status instanceof AssetStatus
                ? $asset->status
                : AssetStatus::from((string) $asset->status);

            $statusBefore = $current->value;
            $statusAfter = $definition->isRepair()
                ? AssetStatus::Maintenance
                : AssetStatus::OnEvent;

            if ($current !== $statusAfter) {
                $asset->update(['status' => $statusAfter]);
            }

            $repairId = isset($input['repair_id']) ? (int) $input['repair_id'] : null;
            if ($repairId === 0) {
                $repairId = null;
            }

            if ($definition->isRepair() && $repairId === null && (bool) ($input['create_repair'] ?? false)) {
                $summary = $this->nullableString($input['repair_summary'] ?? null)
                    ?? ('Checkout repair: '.$asset->name);

                $repair = $this->repairs->startWithNewFault($asset, [
                    'summary' => $summary,
                    'notes' => $input['repair_notes'] ?? $input['notes'] ?? null,
                    'assignee_user_id' => $toUserId,
                    'set_asset_maintenance' => false,
                ], $by);

                $repairId = $repair->id;
            }

            if ($repairId !== null) {
                $repair = Repair::query()->findOrFail($repairId);
                if ((int) $repair->asset_id !== (int) $asset->id) {
                    throw ValidationException::withMessages([
                        'repair_id' => 'Repair does not belong to this asset.',
                    ]);
                }

                if ($toUserId !== null && $repair->assignee_user_id === null) {
                    $repair->update(['assignee_user_id' => $toUserId]);
                }
            }

            if (! $definition->isRepair()) {
                $repairId = null;
            }

            $checkout = AssetCheckout::query()->create([
                'department_id' => $asset->department_id,
                'asset_id' => $asset->id,
                'checked_out_by_user_id' => $by->id,
                'checked_out_to_user_id' => $toUserId,
                'checked_out_to_name' => $toUserId !== null ? null : $toName,
                'checked_out_to_email' => $toUserId !== null ? null : $toEmail,
                'checked_out_to_company' => $toUserId !== null ? null : $toCompany,
                'location_id' => $input['location_id'] ?? $asset->location_id,
                'expected_return_at' => $input['expected_return_at'] ?? null,
                'purpose' => $definition->slug,
                'repair_id' => $repairId,
                'asset_status_before' => $statusBefore,
                'notes' => $this->nullableString($input['notes'] ?? null),
            ]);

            AuditLog::create([
                'user_id' => $by->id,
                'department_id' => $asset->department_id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'asset_checked_out',
                'old_values' => ['status' => $statusBefore],
                'new_values' => [
                    'checkout_id' => $checkout->id,
                    'purpose' => $definition->slug,
                    'status' => $statusAfter->value,
                    'checked_out_to_user_id' => $toUserId,
                    'checked_out_to_name' => $checkout->checked_out_to_name,
                    'repair_id' => $repairId,
                ],
                'created_at' => now(),
            ]);

            return $checkout->fresh(['asset', 'checkedOutTo', 'checkedOutBy', 'repair', 'checkoutPurpose']);
        });
    }

    /**
     * @param  array{notes?: string|null, force?: bool, skip_status_restore?: bool}  $input
     */
    public function returnCheckout(AssetCheckout $checkout, User $by, array $input = []): AssetCheckout
    {
        if (! $checkout->isOpen()) {
            throw ValidationException::withMessages([
                'checkout' => 'This checkout has already been returned.',
            ]);
        }

        if ($checkout->isRepairPurpose() && $checkout->repair_id && ! ($input['force'] ?? false)) {
            $repair = $checkout->repair ?? Repair::query()->find($checkout->repair_id);
            if ($repair !== null && $repair->isActive()) {
                throw ValidationException::withMessages([
                    'checkout' => 'Return is blocked while the linked repair is still open. Complete the repair first, or return after the repair is closed.',
                ]);
            }
        }

        return DB::transaction(function () use ($checkout, $by, $input): AssetCheckout {
            $asset = $checkout->asset()->lockForUpdate()->firstOrFail();

            $current = $asset->status instanceof AssetStatus
                ? $asset->status
                : AssetStatus::from((string) $asset->status);

            $statusAfter = $current;

            if (! ($input['skip_status_restore'] ?? false)) {
                $restore = $this->nullableString($checkout->asset_status_before) ?? AssetStatus::Available->value;
                try {
                    $restoreStatus = AssetStatus::from($restore);
                } catch (\ValueError) {
                    $restoreStatus = AssetStatus::Available;
                }

                if ($current !== $restoreStatus) {
                    $asset->update(['status' => $restoreStatus]);
                    $statusAfter = $restoreStatus;
                }
            }

            $checkout->update([
                'returned_at' => now(),
                'returned_by_user_id' => $by->id,
                'notes' => $this->nullableString($input['notes'] ?? null) ?? $checkout->notes,
            ]);

            AuditLog::create([
                'user_id' => $by->id,
                'department_id' => $asset->department_id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'asset_returned',
                'old_values' => ['status' => $current->value],
                'new_values' => [
                    'checkout_id' => $checkout->id,
                    'status' => $statusAfter->value,
                    'purpose' => (string) $checkout->purpose,
                ],
                'created_at' => now(),
            ]);

            return $checkout->fresh(['asset', 'checkedOutTo', 'returnedBy', 'repair']);
        });
    }

    public function returnOpenForRepair(Repair $repair, ?User $by = null, bool $skipStatusRestore = false): ?AssetCheckout
    {
        $checkout = AssetCheckout::query()
            ->where('repair_id', $repair->id)
            ->open()
            ->first();

        if ($checkout === null) {
            return null;
        }

        return $this->returnCheckout($checkout, $by ?? $checkout->checkedOutBy, [
            'force' => true,
            'skip_status_restore' => $skipStatusRestore,
            'notes' => $checkout->notes,
        ]);
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
