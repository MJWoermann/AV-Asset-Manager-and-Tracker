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
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
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
     *     recorded_at?: DateTimeInterface|string|null,
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

        $recordedAt = $this->resolveRecordedAt($input['recorded_at'] ?? null);
        $existing = $this->orderedMovements($asset);
        $opening = $existing->isEmpty()
            ? max(0, (int) ($asset->quantity ?? 0))
            : (int) $existing->first()->quantity_before;
        $previous = $existing->last(fn (StockMovement $movement): bool => $movement->recorded_at !== null && $movement->recorded_at->lessThanOrEqualTo($recordedAt));
        $before = $previous instanceof StockMovement ? (int) $previous->quantity_after : $opening;
        $after = $this->resolveQuantityAfter($type, $before, $input);

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock quantity cannot go below zero.',
            ]);
        }

        // #region agent log
        $chronologicalPrevious = StockMovement::query()
            ->where('asset_id', $asset->id)
            ->where('recorded_at', '<=', $recordedAt)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first(['id', 'recorded_at', 'quantity_after', 'type']);
        $latestMovement = StockMovement::query()
            ->where('asset_id', $asset->id)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first(['id', 'recorded_at', 'quantity_after']);
        file_put_contents(base_path('debug-ff4d0e.log'), json_encode([
            'sessionId' => 'ff4d0e',
            'runId' => 'pre-fix',
            'hypothesisId' => 'H5',
            'location' => 'StockMovementService.php:record',
            'message' => 'restock base quantity vs chronological previous',
            'data' => [
                'assetId' => $asset->id,
                'type' => $type->value,
                'inputQuantity' => $input['quantity'] ?? $input['quantity_delta'] ?? null,
                'assetQuantityBefore' => $before,
                'resolvedAfter' => $after,
                'recordedAt' => $recordedAt->toIso8601String(),
                'chronologicalPreviousAfter' => $chronologicalPrevious?->quantity_after,
                'chronologicalPreviousAt' => $chronologicalPrevious?->recorded_at?->toIso8601String(),
                'latestMovementAfter' => $latestMovement?->quantity_after,
                'latestMovementAt' => $latestMovement?->recorded_at?->toIso8601String(),
                'baseDiffersFromPrevious' => $chronologicalPrevious !== null && (int) $chronologicalPrevious->quantity_after !== $before,
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ]).PHP_EOL, FILE_APPEND);
        // #endregion

        return DB::transaction(function () use ($asset, $type, $source, $before, $after, $input, $user, $uploads, $recordedAt, $opening): StockMovement {
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
                'recorded_at' => $recordedAt,
            ]);

            $this->replayLedger($asset, $opening);

            foreach ($uploads as $upload) {
                $title = $movement->external_po_number
                    ? 'PO '.$movement->external_po_number
                    : ('Restock PDF '.$movement->id);

                $this->attachments->storeUpload($upload, [
                    'title' => $title,
                    'category' => FileCategory::Invoice->value,
                    'note' => 'PO export',
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
     * @param  array{
     *     type: StockMovementType|string,
     *     quantity?: int|null,
     *     quantity_delta?: int|null,
     *     notes?: string|null,
     *     external_po_number?: string|null,
     *     external_po_url?: string|null,
     *     recorded_at?: DateTimeInterface|string|null,
     * }  $input
     */
    public function update(StockMovement $movement, array $input, ?User $user = null): StockMovement
    {
        $movement->loadMissing('asset.itemType');
        $asset = $movement->asset;
        if ($asset === null) {
            throw ValidationException::withMessages([
                'asset' => 'This stock event is not attached to an asset.',
            ]);
        }

        $this->assertStockTracked($asset);

        $type = $input['type'] instanceof StockMovementType
            ? $input['type']
            : StockMovementType::from((string) $input['type']);

        if ($type === StockMovementType::Breakage) {
            $notes = trim((string) ($input['notes'] ?? ''));
            if ($notes === '') {
                throw ValidationException::withMessages([
                    'notes' => 'Notes are required when logging breakage.',
                ]);
            }
        }

        $recordedAt = $this->resolveRecordedAt($input['recorded_at'] ?? null);
        $absolute = $type === StockMovementType::Count
            ? $this->requireNonNegativeInt($input['quantity'] ?? null, 'quantity')
            : null;
        $delta = $type === StockMovementType::Count
            ? 0
            : $this->intendedDelta($type, $input);

        return DB::transaction(function () use ($movement, $asset, $type, $input, $user, $recordedAt, $absolute, $delta): StockMovement {
            $beforeQuantity = (int) $asset->quantity;
            $opening = $this->openingBalance($asset);
            $movement->fill([
                'type' => $type,
                'quantity_after' => $absolute ?? $movement->quantity_after,
                'quantity_delta' => $delta,
                'external_po_number' => NullableString::from($input['external_po_number'] ?? null),
                'external_po_url' => NullableString::from($input['external_po_url'] ?? null),
                'notes' => NullableString::from($input['notes'] ?? null),
                'recorded_at' => $recordedAt,
            ]);
            $movement->save();

            $this->replayLedger($asset, $opening);
            $asset->refresh();

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'stock_movement_updated',
                'old_values' => [
                    'quantity' => $beforeQuantity,
                    'stock_movement_id' => $movement->id,
                ],
                'new_values' => [
                    'quantity' => (int) $asset->quantity,
                    'stock_movement_id' => $movement->id,
                    'type' => $type->value,
                    'recorded_at' => $recordedAt->toIso8601String(),
                    'notes' => $movement->notes,
                    'external_po_number' => $movement->external_po_number,
                    'external_po_url' => $movement->external_po_url,
                ],
                'created_at' => now(),
            ]);

            return $movement->fresh(['files', 'user']);
        });
    }

    public function delete(StockMovement $movement, ?User $user = null): void
    {
        $movement->loadMissing('asset');
        $asset = $movement->asset;
        if ($asset === null) {
            throw ValidationException::withMessages([
                'asset' => 'This stock event is not attached to an asset.',
            ]);
        }

        $this->assertStockTracked($asset);

        DB::transaction(function () use ($movement, $asset, $user): void {
            $beforeQuantity = (int) $asset->quantity;
            $opening = $this->openingBalance($asset);
            $movementId = $movement->id;
            $type = $movement->type->value;
            $movement->delete();
            $this->replayLedger($asset, $opening);
            $asset->refresh();

            AuditLog::create([
                'user_id' => $user?->id,
                'auditable_type' => Asset::class,
                'auditable_id' => $asset->id,
                'action' => 'stock_movement_deleted',
                'old_values' => [
                    'quantity' => $beforeQuantity,
                    'stock_movement_id' => $movementId,
                    'type' => $type,
                ],
                'new_values' => [
                    'quantity' => (int) $asset->quantity,
                    'stock_movement_id' => $movementId,
                ],
                'created_at' => now(),
            ]);
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

    /**
     * HTML date inputs arrive as Y-m-d (midnight). Keep today's wall-clock time so same-day
     * entries sort after earlier movements; past dates keep the current clock on that day.
     */
    protected function resolveRecordedAt(mixed $value): CarbonInterface
    {
        if ($value === null || $value === '') {
            return now();
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance(DateTimeImmutable::createFromInterface($value));
        }

        $raw = (string) $value;
        $parsed = Carbon::parse($raw);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw) === 1) {
            return $parsed->setTimeFrom(now());
        }

        return $parsed;
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

    /**
     * @return Collection<int, StockMovement>
     */
    protected function orderedMovements(Asset $asset): Collection
    {
        return StockMovement::query()
            ->where('asset_id', $asset->id)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();
    }

    protected function openingBalance(Asset $asset): int
    {
        $first = $this->orderedMovements($asset)->first();

        return $first instanceof StockMovement
            ? (int) $first->quantity_before
            : max(0, (int) ($asset->quantity ?? 0));
    }

    /**
     * Rebuild before/after from each event's own delta. Counts stay absolute.
     * A restock therefore adds to the previous on-hand value, including when it is entered out of order.
     */
    public function replayLedger(Asset $asset, int $opening): void
    {
        $movements = $this->orderedMovements($asset);

        if ($movements->isEmpty()) {
            $asset->update(['quantity' => max(0, $opening)]);

            return;
        }

        $running = $opening;

        foreach ($movements as $movement) {
            $after = $movement->type === StockMovementType::Count
                ? (int) $movement->quantity_after
                : $running + (int) $movement->quantity_delta;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'That change would take stock below zero.',
                ]);
            }

            $movement->update([
                'quantity_before' => $running,
                'quantity_after' => $after,
                'quantity_delta' => $after - $running,
            ]);
            $running = $after;
        }

        $asset->update(['quantity' => $running]);
    }

    /**
     * @param  array{quantity?: int|null, quantity_delta?: int|null}  $input
     */
    protected function intendedDelta(StockMovementType $type, array $input): int
    {
        return match ($type) {
            StockMovementType::Count => 0,
            StockMovementType::Restock => $this->requirePositiveInt($input['quantity'] ?? $input['quantity_delta'] ?? null, 'quantity'),
            StockMovementType::Breakage => -$this->requirePositiveInt($input['quantity'] ?? $input['quantity_delta'] ?? null, 'quantity'),
            StockMovementType::Adjustment => $this->requireInt($input['quantity_delta'] ?? null, 'quantity_delta'),
        };
    }
}
