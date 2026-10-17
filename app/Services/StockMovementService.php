<?php

namespace App\Services;

use App\Enums\FileCategory;
use App\Enums\StockMovementSource;
use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\NullableString;
use App\Support\UploadedFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function __construct(private FileAttachmentService $attachments) {}

    public function assertStockTracked(Asset $asset): void
    {
        $asset->loadMissing('itemType');

        if (! $asset->itemType?->supports_stock_tracking) {
            throw ValidationException::withMessages([
                'asset' => 'This asset type does not support stock tracking.',
            ]);
        }
    }

    /**
     * Ensure a baseline ledger entry exists so charts have a starting point.
     */
    public function ensureBaseline(Asset $asset, ?User $user = null): ?StockMovement
    {
        $this->assertStockTracked($asset);

        if ($asset->stockMovements()->exists()) {
            return null;
        }

        $qty = max(0, (int) ($asset->quantity ?? 0));

        return $this->record($asset, [
            'type' => StockMovementType::Count,
            'quantity' => $qty,
            'notes' => 'Initial stock baseline',
            'source' => StockMovementSource::Web,
        ], $user, allowPdf: false);
    }

    /**
     * @param  array{
     *     type: StockMovementType|string,
     *     quantity?: int|null,
     *     quantity_delta?: int|null,
     *     notes?: string|null,
     *     external_po_number?: string|null,
     *     external_po_url?: string|null,
     *     source?: StockMovementSource|string|null,
     *     recorded_at?: \DateTimeInterface|string|null,
     * }  $input
     * @param  list<UploadedFile>|UploadedFile|null  $pdfs
     */
    public function record(
        Asset $asset,
        array $input,
        ?User $user = null,
        UploadedFile|array|null $pdfs = null,
        bool $allowPdf = true,
    ): StockMovement {
        $this->assertStockTracked($asset);

        $type = $input['type'] instanceof StockMovementType
            ? $input['type']
            : StockMovementType::from((string) $input['type']);

        $source = isset($input['source'])
            ? ($input['source'] instanceof StockMovementSource
                ? $input['source']
                : StockMovementSource::from((string) $input['source']))
            : StockMovementSource::Web;

        $before = max(0, (int) ($asset->quantity ?? 0));
        $after = $this->resolveQuantityAfter($type, $before, $input);

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock quantity cannot go below zero.',
            ]);
        }

        if ($type === StockMovementType::Breakage) {
            $notes = trim((string) ($input['notes'] ?? ''));
            if ($notes === '') {
                throw ValidationException::withMessages([
                    'notes' => 'Notes are required when logging breakage.',
                ]);
            }
        }

        $uploads = UploadedFiles::normalize($pdfs);
        if ($uploads !== [] && ! $allowPdf) {
            throw ValidationException::withMessages([
                'pdfs' => 'PDF attachments are only available when signed in.',
            ]);
        }

        if ($type !== StockMovementType::Restock && $uploads !== []) {
            throw ValidationException::withMessages([
                'pdfs' => 'PDF attachments are only allowed on restock events.',
            ]);
        }

        return DB::transaction(function () use ($asset, $type, $source, $before, $after, $input, $user, $uploads): StockMovement {
            $movement = StockMovement::query()->create([
                'asset_id' => $asset->id,
                'type' => $type,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'quantity_delta' => $after - $before,
                'user_id' => $user?->id,
                'external_po_number' => NullableString::from($input['external_po_number'] ?? null),
                'external_po_url' => NullableString::from($input['external_po_url'] ?? null),
                'notes' => NullableString::from($input['notes'] ?? null),
                'source' => $source,
                'recorded_at' => $input['recorded_at'] ?? now(),
            ]);

            $asset->update(['quantity' => $after]);

            foreach ($uploads as $upload) {
                $title = $movement->external_po_number
                    ? 'PO '.$movement->external_po_number
                    : ('Restock PDF '.$movement->id);

                $this->attachments->storeUpload($upload, [
                    'title' => $title,
                    'category' => FileCategory::Invoice->value,
                    'note' => 'Momentous PO export',
                ], $movement);
            }

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => $type->auditAction(),
                'old_values' => [
                    'quantity' => $before,
                ],
                'new_values' => [
                    'quantity' => $after,
                    'quantity_delta' => $after - $before,
                    'stock_movement_id' => $movement->id,
                    'type' => $type->value,
                    'external_po_number' => $movement->external_po_number,
                    'external_po_url' => $movement->external_po_url,
                    'notes' => $movement->notes,
                    'source' => $source->value,
                ],
                'created_at' => now(),
            ]);

            return $movement->fresh(['files', 'user']);
        });
    }

    /**
     * @param  array{quantity?: int|null, quantity_delta?: int|null}  $input
     */
    protected function resolveQuantityAfter(StockMovementType $type, int $before, array $input): int
    {
        return match ($type) {
            StockMovementType::Count => $this->requireNonNegativeInt($input['quantity'] ?? null, 'quantity'),
            StockMovementType::Restock => $before + $this->requirePositiveInt($input['quantity'] ?? $input['quantity_delta'] ?? null, 'quantity'),
            StockMovementType::Breakage => $before - $this->requirePositiveInt($input['quantity'] ?? $input['quantity_delta'] ?? null, 'quantity'),
            StockMovementType::Adjustment => $before + $this->requireInt($input['quantity_delta'] ?? null, 'quantity_delta'),
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
}
