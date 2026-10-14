<?php

namespace App\Services;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use App\Models\Asset;
use App\Models\AssetRuPlacement;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RuVisualiserService
{
    public const CUSTOM_RU_SLUG = 'ru';

    /**
     * When native ru_height is null, copy a positive integer from custom field slug `ru`.
     *
     * @return bool True when the asset was updated.
     */
    public function seedRuHeightFromCustomField(Asset $asset): bool
    {
        if ($asset->ru_height !== null) {
            return false;
        }

        $parsed = $this->customRuValue($asset);
        if ($parsed === null) {
            return false;
        }

        $asset->ru_height = $parsed;
        $asset->save();

        return true;
    }

    /**
     * Positive RU integer from custom field slug `ru`, or null.
     */
    public function customRuValue(Asset $asset): ?int
    {
        $asset->loadMissing('customFieldValues.definition');

        $raw = $asset->customFieldValues
            ->first(fn ($value) => $value->definition?->slug === self::CUSTOM_RU_SLUG)
            ?->value;

        return $this->parseRuValue($raw);
    }

    /**
     * Prefill placement span from child ru_height (seeded when available), default 1.
     */
    public function defaultPlacementHeight(Asset $child): int
    {
        $this->seedRuHeightFromCustomField($child);

        $height = (int) ($child->ru_height ?? 1);

        return max(1, $height);
    }

    /**
     * @param  array{
     *     face: string|RuPlacementFace,
     *     start_ru: int,
     *     ru_height?: int|null,
     *     kind: string|RuPlacementKind,
     *     child_asset_id?: int|null,
     *     note?: string|null,
     *     confirm_parent_change?: bool
     * }  $data
     */
    public function place(Asset $rack, array $data): AssetRuPlacement
    {
        return DB::transaction(function () use ($rack, $data): AssetRuPlacement {
            $rack = Asset::query()->lockForUpdate()->findOrFail($rack->id);
            $this->assertRackReady($rack);

            $face = $this->resolveFace($data['face'] ?? null);
            $kind = $this->resolveKind($data['kind'] ?? null);
            $startRu = (int) ($data['start_ru'] ?? 0);
            $confirmParentChange = (bool) ($data['confirm_parent_change'] ?? false);

            $this->assertFaceAllowed($rack, $face);

            $child = null;
            $note = null;
            $ruHeight = isset($data['ru_height']) && $data['ru_height'] !== null && $data['ru_height'] !== ''
                ? (int) $data['ru_height']
                : null;

            if ($kind === RuPlacementKind::Asset) {
                $childId = (int) ($data['child_asset_id'] ?? 0);
                $child = Asset::query()->find($childId);
                if (! $child) {
                    throw ValidationException::withMessages([
                        'child_asset_id' => 'Select a valid child asset.',
                    ]);
                }

                $this->assertChildPlaceableOnRack($rack, $child, null, $confirmParentChange);
                $ruHeight ??= $this->defaultPlacementHeight($child);
            } else {
                $note = isset($data['note']) ? trim((string) $data['note']) : '';
                if ($note === '') {
                    throw ValidationException::withMessages([
                        'note' => 'A note is required for note placements.',
                    ]);
                }
                $ruHeight ??= 1;
            }

            $this->assertSpanValid($rack, $face, $startRu, $ruHeight, null);

            $placement = AssetRuPlacement::query()->create([
                'rack_asset_id' => $rack->id,
                'face' => $face,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'kind' => $kind,
                'child_asset_id' => $child?->id,
                'note' => $kind === RuPlacementKind::Note ? $note : null,
            ]);

            if ($child) {
                $this->assignParent($rack, $child, $confirmParentChange);
            }

            $this->auditRack($rack, 'ru_placement_created', null, [
                'placement_id' => $placement->id,
                'face' => $face->value,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'kind' => $kind->value,
                'child_asset_id' => $child?->id,
                'note' => $kind === RuPlacementKind::Note ? $note : null,
            ]);

            return $placement->fresh(['childAsset']);
        });
    }

    /**
     * @param  array{
     *     face?: string|RuPlacementFace,
     *     start_ru?: int,
     *     ru_height?: int|null,
     *     kind?: string|RuPlacementKind,
     *     child_asset_id?: int|null,
     *     note?: string|null,
     *     confirm_parent_change?: bool
     * }  $data
     */
    public function update(AssetRuPlacement $placement, array $data): AssetRuPlacement
    {
        return DB::transaction(function () use ($placement, $data): AssetRuPlacement {
            $placement->refresh();
            $rack = Asset::query()->lockForUpdate()->findOrFail($placement->rack_asset_id);
            $this->assertRackReady($rack);

            $face = array_key_exists('face', $data)
                ? $this->resolveFace($data['face'])
                : $placement->face;
            $kind = array_key_exists('kind', $data)
                ? $this->resolveKind($data['kind'])
                : $placement->kind;
            $startRu = array_key_exists('start_ru', $data)
                ? (int) $data['start_ru']
                : (int) $placement->start_ru;
            $confirmParentChange = (bool) ($data['confirm_parent_change'] ?? false);

            $this->assertFaceAllowed($rack, $face);

            $previousChildId = $placement->child_asset_id ? (int) $placement->child_asset_id : null;
            $child = null;
            $note = $placement->note;
            $ruHeight = array_key_exists('ru_height', $data) && $data['ru_height'] !== null && $data['ru_height'] !== ''
                ? (int) $data['ru_height']
                : (int) $placement->ru_height;

            if ($kind === RuPlacementKind::Asset) {
                $childId = array_key_exists('child_asset_id', $data)
                    ? (int) $data['child_asset_id']
                    : (int) ($placement->child_asset_id ?? 0);
                $child = Asset::query()->find($childId);
                if (! $child) {
                    throw ValidationException::withMessages([
                        'child_asset_id' => 'Select a valid child asset.',
                    ]);
                }

                $this->assertChildPlaceableOnRack($rack, $child, $placement->id, $confirmParentChange);
                if (! array_key_exists('ru_height', $data) || $data['ru_height'] === null || $data['ru_height'] === '') {
                    if ($previousChildId !== (int) $child->id || $ruHeight < 1) {
                        $ruHeight = $this->defaultPlacementHeight($child);
                    }
                }
                $note = null;
            } else {
                if (array_key_exists('note', $data)) {
                    $note = trim((string) $data['note']);
                }
                if ($note === null || $note === '') {
                    throw ValidationException::withMessages([
                        'note' => 'A note is required for note placements.',
                    ]);
                }
                $child = null;
            }

            $this->assertSpanValid($rack, $face, $startRu, $ruHeight, $placement->id);

            $old = $placement->only([
                'face', 'start_ru', 'ru_height', 'kind', 'child_asset_id', 'note',
            ]);

            $placement->update([
                'face' => $face,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'kind' => $kind,
                'child_asset_id' => $child?->id,
                'note' => $kind === RuPlacementKind::Note ? $note : null,
            ]);

            if ($previousChildId && ($child === null || $previousChildId !== (int) $child->id)) {
                $this->clearParentIfLastPlacement($rack, $previousChildId);
            }

            if ($child) {
                $this->assignParent($rack, $child, $confirmParentChange);
            }

            $this->auditRack($rack, 'ru_placement_updated', $old, [
                'placement_id' => $placement->id,
                'face' => $face->value,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'kind' => $kind->value,
                'child_asset_id' => $child?->id,
                'note' => $kind === RuPlacementKind::Note ? $note : null,
            ]);

            return $placement->fresh(['childAsset']);
        });
    }

    public function remove(AssetRuPlacement $placement): void
    {
        DB::transaction(function () use ($placement): void {
            $placement->refresh();
            $rack = Asset::query()->lockForUpdate()->findOrFail($placement->rack_asset_id);
            $childId = $placement->child_asset_id ? (int) $placement->child_asset_id : null;

            $old = $placement->only([
                'id', 'face', 'start_ru', 'ru_height', 'kind', 'child_asset_id', 'note',
            ]);

            $placement->delete();

            if ($childId) {
                $this->clearParentIfLastPlacement($rack, $childId);
            }

            $this->auditRack($rack, 'ru_placement_deleted', $old, null);
        });
    }

    /**
     * Validate capacity / rear-face changes before persisting on the rack asset.
     */
    public function assertRackSettingsChange(Asset $rack, ?int $newRuHeight, bool $rearEnabled): void
    {
        $rack->loadMissing('ruPlacements');

        if ($newRuHeight !== null) {
            if ($newRuHeight < 1) {
                throw ValidationException::withMessages([
                    'ru_height' => 'RU height must be at least 1.',
                ]);
            }

            $maxEnd = $rack->ruPlacements
                ->map(fn (AssetRuPlacement $p): int => (int) $p->start_ru + (int) $p->ru_height - 1)
                ->max();

            if ($maxEnd !== null && $newRuHeight < $maxEnd) {
                throw ValidationException::withMessages([
                    'ru_height' => "Cannot reduce RU height below {$maxEnd} while placements occupy those units.",
                ]);
            }
        } elseif ($rack->ruPlacements->isNotEmpty()) {
            throw ValidationException::withMessages([
                'ru_height' => 'Clear RU placements before removing rack height.',
            ]);
        }

        if (! $rearEnabled) {
            $hasRear = $rack->ruPlacements->contains(
                fn (AssetRuPlacement $p): bool => $p->face === RuPlacementFace::Rear
            );

            if ($hasRear) {
                throw ValidationException::withMessages([
                    'ru_rear_enabled' => 'Clear rear-face placements before disabling the rear face.',
                ]);
            }
        }
    }

    public function assertRackSupportsVisualiser(Asset $rack): void
    {
        $rack->loadMissing('itemType');

        if (! $rack->itemType?->supports_ru_visualiser) {
            throw ValidationException::withMessages([
                'rack' => 'This asset type does not support the RU visualiser.',
            ]);
        }
    }

    protected function assertRackReady(Asset $rack): void
    {
        $this->assertRackSupportsVisualiser($rack);
        $this->seedRuHeightFromCustomField($rack);
        $rack->refresh();

        if ($rack->ru_height === null || (int) $rack->ru_height < 1) {
            throw ValidationException::withMessages([
                'ru_height' => 'Set the rack RU height before placing equipment.',
            ]);
        }
    }

    protected function assertFaceAllowed(Asset $rack, RuPlacementFace $face): void
    {
        if ($face === RuPlacementFace::Rear && ! $rack->ru_rear_enabled) {
            throw ValidationException::withMessages([
                'face' => 'Rear face placements require the rear face to be enabled on this rack.',
            ]);
        }
    }

    protected function assertSpanValid(
        Asset $rack,
        RuPlacementFace $face,
        int $startRu,
        int $ruHeight,
        ?int $ignorePlacementId,
    ): void {
        $capacity = (int) $rack->ru_height;

        if ($startRu < 1) {
            throw ValidationException::withMessages([
                'start_ru' => 'Start RU must be at least 1.',
            ]);
        }

        if ($ruHeight < 1) {
            throw ValidationException::withMessages([
                'ru_height' => 'Placement height must be at least 1 RU.',
            ]);
        }

        $endRu = $startRu + $ruHeight - 1;
        if ($endRu > $capacity) {
            throw ValidationException::withMessages([
                'start_ru' => "Placement must fit within the rack capacity of {$capacity} RU.",
            ]);
        }

        $overlaps = AssetRuPlacement::query()
            ->where('rack_asset_id', $rack->id)
            ->where('face', $face->value)
            ->when($ignorePlacementId, fn ($q) => $q->where('id', '!=', $ignorePlacementId))
            ->get()
            ->filter(function (AssetRuPlacement $existing) use ($startRu, $endRu): bool {
                $existingEnd = (int) $existing->start_ru + (int) $existing->ru_height - 1;

                return $startRu <= $existingEnd && (int) $existing->start_ru <= $endRu;
            });

        if ($overlaps->isNotEmpty()) {
            throw ValidationException::withMessages([
                'start_ru' => 'This placement overlaps another placement on the same face.',
            ]);
        }
    }

    protected function assertChildPlaceableOnRack(
        Asset $rack,
        Asset $child,
        ?int $ignorePlacementId,
        bool $confirmParentChange,
    ): void {
        if ((int) $child->id === (int) $rack->id) {
            throw ValidationException::withMessages([
                'child_asset_id' => 'An asset cannot be placed into itself.',
            ]);
        }

        if ($this->childIsAncestorOfRack($rack, $child)) {
            throw ValidationException::withMessages([
                'child_asset_id' => 'Cannot place an ancestor asset into this rack (would create a cycle).',
            ]);
        }

        $duplicate = AssetRuPlacement::query()
            ->where('rack_asset_id', $rack->id)
            ->where('child_asset_id', $child->id)
            ->when($ignorePlacementId, fn ($q) => $q->where('id', '!=', $ignorePlacementId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'child_asset_id' => 'This asset is already placed in this rack.',
            ]);
        }

        if (
            $child->parent_id
            && (int) $child->parent_id !== (int) $rack->id
            && ! $confirmParentChange
        ) {
            throw ValidationException::withMessages([
                'confirm_parent_change' => 'This asset already has a different parent. Confirm to re-parent it under this rack.',
            ]);
        }
    }

    protected function assignParent(Asset $rack, Asset $child, bool $confirmParentChange): void
    {
        if ((int) ($child->parent_id ?? 0) === (int) $rack->id) {
            return;
        }

        if (
            $child->parent_id
            && (int) $child->parent_id !== (int) $rack->id
            && ! $confirmParentChange
        ) {
            throw ValidationException::withMessages([
                'confirm_parent_change' => 'This asset already has a different parent. Confirm to re-parent it under this rack.',
            ]);
        }

        $child->update(['parent_id' => $rack->id]);
    }

    protected function clearParentIfLastPlacement(Asset $rack, int $childId): void
    {
        $child = Asset::query()->find($childId);
        if (! $child) {
            return;
        }

        if ((int) ($child->parent_id ?? 0) !== (int) $rack->id) {
            return;
        }

        $remaining = AssetRuPlacement::query()
            ->where('rack_asset_id', $rack->id)
            ->where('child_asset_id', $childId)
            ->exists();

        if (! $remaining) {
            $child->update(['parent_id' => null]);
        }
    }

    protected function childIsAncestorOfRack(Asset $rack, Asset $child): bool
    {
        $ancestorId = $rack->parent_id;
        $guard = 0;

        while ($ancestorId !== null && $guard < 1000) {
            if ((int) $ancestorId === (int) $child->id) {
                return true;
            }

            $ancestorId = Asset::query()->whereKey($ancestorId)->value('parent_id');
            $guard++;
        }

        return false;
    }

    protected function resolveFace(mixed $face): RuPlacementFace
    {
        if ($face instanceof RuPlacementFace) {
            return $face;
        }

        $resolved = RuPlacementFace::tryFrom((string) $face);
        if (! $resolved) {
            throw ValidationException::withMessages([
                'face' => 'Face must be front or rear.',
            ]);
        }

        return $resolved;
    }

    protected function resolveKind(mixed $kind): RuPlacementKind
    {
        if ($kind instanceof RuPlacementKind) {
            return $kind;
        }

        $resolved = RuPlacementKind::tryFrom((string) $kind);
        if (! $resolved) {
            throw ValidationException::withMessages([
                'kind' => 'Kind must be asset or note.',
            ]);
        }

        return $resolved;
    }

    protected function parseRuValue(mixed $raw): ?int
    {
        if ($raw === null) {
            return null;
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d+)/', $value, $matches) !== 1) {
            return null;
        }

        $parsed = (int) $matches[1];

        return $parsed >= 1 ? $parsed : null;
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function auditRack(Asset $rack, string $action, ?array $old, ?array $new): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Asset::class,
            'auditable_id' => $rack->getKey(),
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
            'created_at' => now(),
        ]);
    }
}
