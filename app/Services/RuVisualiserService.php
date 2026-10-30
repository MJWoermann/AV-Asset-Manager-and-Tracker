<?php

namespace App\Services;

use App\Enums\RuPlacementFace;
use App\Enums\RuPlacementKind;
use App\Enums\RuPlacementWidth;
use App\Models\Asset;
use App\Models\AssetRuPlacement;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
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
     *     full_depth?: bool,
     *     width?: string|RuPlacementWidth,
     *     slot?: int|null,
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
            $fullDepth = $kind === RuPlacementKind::Asset && (bool) ($data['full_depth'] ?? false);
            $width = $this->resolveWidth($data['width'] ?? null);
            $slot = $this->resolveSlot($width, $data['slot'] ?? null, array_key_exists('slot', $data));

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

                if ($fullDepth) {
                    $face = RuPlacementFace::Front;
                    $this->assertFullDepthAllowed($rack);
                }
            } else {
                $note = isset($data['note']) ? trim((string) $data['note']) : '';
                if ($note === '') {
                    throw ValidationException::withMessages([
                        'note' => 'A note is required for note placements.',
                    ]);
                }
                $ruHeight ??= 1;
                $fullDepth = false;
            }

            $this->assertFaceAllowed($rack, $face);
            $this->assertSpanValid($rack, $face, $startRu, $ruHeight, null, $fullDepth, $width, $slot);

            $placement = AssetRuPlacement::query()->create([
                'rack_asset_id' => $rack->id,
                'face' => $face,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'full_depth' => $fullDepth,
                'width' => $width,
                'slot' => $slot,
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
                'full_depth' => $fullDepth,
                'width' => $width->value,
                'slot' => $slot,
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
     *     full_depth?: bool,
     *     width?: string|RuPlacementWidth,
     *     slot?: int|null,
     *     kind?: string|RuPlacementKind,
     *     child_asset_id?: int|null,
     *     note?: string|null,
     *     confirm_parent_change?: bool,
     *     move_row?: bool
     * }  $data
     */
    public function update(AssetRuPlacement $placement, array $data): AssetRuPlacement
    {
        return DB::transaction(function () use ($placement, $data): AssetRuPlacement {
            $placement->refresh();
            $rack = Asset::query()->lockForUpdate()->findOrFail($placement->rack_asset_id);
            $this->assertRackReady($rack);

            $originalStartRu = (int) $placement->start_ru;
            $originalWidth = $placement->width ?? RuPlacementWidth::Full;
            $originalFace = $placement->face;

            $face = array_key_exists('face', $data)
                ? $this->resolveFace($data['face'])
                : $placement->face;
            $kind = array_key_exists('kind', $data)
                ? $this->resolveKind($data['kind'])
                : $placement->kind;
            $startRu = array_key_exists('start_ru', $data)
                ? (int) $data['start_ru']
                : $originalStartRu;
            $confirmParentChange = (bool) ($data['confirm_parent_change'] ?? false);
            $width = array_key_exists('width', $data)
                ? $this->resolveWidth($data['width'])
                : ($placement->width ?? RuPlacementWidth::Full);
            $slot = $this->resolveSlot(
                $width,
                array_key_exists('slot', $data) ? $data['slot'] : ($placement->slot ?? 1),
                array_key_exists('slot', $data),
            );

            $previousChildId = $placement->child_asset_id ? (int) $placement->child_asset_id : null;
            $child = null;
            $note = $placement->note;
            $ruHeight = array_key_exists('ru_height', $data) && $data['ru_height'] !== null && $data['ru_height'] !== ''
                ? (int) $data['ru_height']
                : (int) $placement->ru_height;
            $fullDepth = array_key_exists('full_depth', $data)
                ? (bool) $data['full_depth']
                : (bool) $placement->full_depth;

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

                if ($fullDepth) {
                    $face = RuPlacementFace::Front;
                    $this->assertFullDepthAllowed($rack);
                }
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
                $fullDepth = false;
            }

            $this->assertFaceAllowed($rack, $face);

            $moveRow = (bool) ($data['move_row'] ?? false);
            $rowMembers = new Collection;
            if ($moveRow && $originalWidth !== RuPlacementWidth::Full && $startRu !== $originalStartRu) {
                $rowMembers = AssetRuPlacement::query()
                    ->where('rack_asset_id', $rack->id)
                    ->where('face', $originalFace->value)
                    ->where('width', $originalWidth->value)
                    ->where('start_ru', $originalStartRu)
                    ->orderBy('slot')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            if ($rowMembers->count() > 1) {
                return $this->moveRowTogether($rack, $placement, $rowMembers, [
                    'face' => $face,
                    'start_ru' => $startRu,
                    'ru_height' => $ruHeight,
                    'full_depth' => $fullDepth,
                    'width' => $width,
                    'slot' => $slot,
                    'kind' => $kind,
                    'child' => $child,
                    'note' => $kind === RuPlacementKind::Note ? $note : null,
                    'confirm_parent_change' => $confirmParentChange,
                    'original_start_ru' => $originalStartRu,
                ]);
            }

            $this->assertSpanValid($rack, $face, $startRu, $ruHeight, $placement->id, $fullDepth, $width, $slot);

            $old = $placement->only([
                'face', 'start_ru', 'ru_height', 'full_depth', 'width', 'slot', 'kind', 'child_asset_id', 'note',
            ]);

            $placement->update([
                'face' => $face,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'full_depth' => $fullDepth,
                'width' => $width,
                'slot' => $slot,
                'kind' => $kind,
                'child_asset_id' => $child?->id,
                'note' => $kind === RuPlacementKind::Note ? $note : null,
            ]);

            if ($child) {
                $this->assignParent($rack, $child, $confirmParentChange);
            }

            $this->auditRack($rack, 'ru_placement_updated', $old, [
                'placement_id' => $placement->id,
                'face' => $face->value,
                'start_ru' => $startRu,
                'ru_height' => $ruHeight,
                'full_depth' => $fullDepth,
                'width' => $width->value,
                'slot' => $slot,
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

            $old = $placement->only([
                'id', 'face', 'start_ru', 'ru_height', 'full_depth', 'width', 'slot', 'kind', 'child_asset_id', 'note',
            ]);

            // Keep parent_id — child remains under the rack and shows as unplaced.
            $placement->delete();

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
            $blocksRearDisable = $rack->ruPlacements->contains(
                fn (AssetRuPlacement $p): bool => $p->face === RuPlacementFace::Rear || (bool) $p->full_depth
            );

            if ($blocksRearDisable) {
                throw ValidationException::withMessages([
                    'ru_rear_enabled' => 'Clear rear-face and full-depth placements before disabling the rear face.',
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

    protected function assertFullDepthAllowed(Asset $rack): void
    {
        if (! $rack->ru_rear_enabled) {
            throw ValidationException::withMessages([
                'full_depth' => 'Full-depth placements require the rear face to be enabled on this rack.',
            ]);
        }
    }

    /**
     * Move every half or third that shares the open item's face, width, and start RU.
     *
     * @param  Collection<int, AssetRuPlacement>  $members
     * @param  array{
     *     face: RuPlacementFace,
     *     start_ru: int,
     *     ru_height: int,
     *     full_depth: bool,
     *     width: RuPlacementWidth,
     *     slot: int,
     *     kind: RuPlacementKind,
     *     child: ?Asset,
     *     note: ?string,
     *     confirm_parent_change: bool,
     *     original_start_ru: int
     * }  $edited
     */
    protected function moveRowTogether(Asset $rack, AssetRuPlacement $placement, Collection $members, array $edited): AssetRuPlacement
    {
        $memberIds = $members
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->sort()
            ->values()
            ->all();

        $proposed = [];
        foreach ($members as $member) {
            $isEdited = (int) $member->id === (int) $placement->id;
            $proposed[] = [
                'face' => $isEdited ? $edited['face'] : $member->face,
                'start_ru' => (int) $edited['start_ru'],
                'ru_height' => $isEdited ? (int) $edited['ru_height'] : (int) $member->ru_height,
                'full_depth' => $isEdited ? (bool) $edited['full_depth'] : (bool) $member->full_depth,
                'width' => $isEdited ? $edited['width'] : ($member->width ?? RuPlacementWidth::Full),
                'slot' => $isEdited ? (int) $edited['slot'] : (int) $member->slot,
            ];
        }

        foreach ($proposed as $candidate) {
            $this->assertSpanValid(
                $rack,
                $candidate['face'],
                $candidate['start_ru'],
                $candidate['ru_height'],
                null,
                $candidate['full_depth'],
                $candidate['width'],
                $candidate['slot'],
                $memberIds,
            );
        }

        $count = count($proposed);
        for ($index = 0; $index < $count; $index++) {
            for ($other = $index + 1; $other < $count; $other++) {
                if ($this->spansConflict($proposed[$index], $proposed[$other])) {
                    throw ValidationException::withMessages([
                        'start_ru' => 'This placement overlaps another placement on the same face.',
                    ]);
                }
            }
        }

        $previousPlacement = [
            'id' => (int) $placement->id,
            'face' => $placement->face->value,
            'width' => ($placement->width ?? RuPlacementWidth::Full)->value,
            'slot' => (int) $placement->slot,
            'ru_height' => (int) $placement->ru_height,
            'full_depth' => (bool) $placement->full_depth,
            'kind' => $placement->kind->value,
            'child_asset_id' => $placement->child_asset_id ? (int) $placement->child_asset_id : null,
            'note' => $placement->note,
        ];

        foreach ($members as $member) {
            if ((int) $member->id === (int) $placement->id) {
                $member->update([
                    'face' => $edited['face'],
                    'start_ru' => $edited['start_ru'],
                    'ru_height' => $edited['ru_height'],
                    'full_depth' => $edited['full_depth'],
                    'width' => $edited['width'],
                    'slot' => $edited['slot'],
                    'kind' => $edited['kind'],
                    'child_asset_id' => $edited['child']?->id,
                    'note' => $edited['kind'] === RuPlacementKind::Note ? $edited['note'] : null,
                ]);

                continue;
            }

            $member->update([
                'start_ru' => $edited['start_ru'],
            ]);
        }

        if ($edited['child'] instanceof Asset) {
            $this->assignParent($rack, $edited['child'], $edited['confirm_parent_change']);
        }

        $this->auditRack($rack, 'ru_placement_row_moved', [
            'start_ru' => (int) $edited['original_start_ru'],
            'member_ids' => $memberIds,
            'placement' => $previousPlacement,
        ], [
            'start_ru' => (int) $edited['start_ru'],
            'member_ids' => $memberIds,
            'placement' => [
                'id' => (int) $placement->id,
                'face' => $edited['face']->value,
                'width' => $edited['width']->value,
                'slot' => (int) $edited['slot'],
                'ru_height' => (int) $edited['ru_height'],
                'full_depth' => (bool) $edited['full_depth'],
                'kind' => $edited['kind']->value,
                'child_asset_id' => $edited['child']?->id,
                'note' => $edited['kind'] === RuPlacementKind::Note ? $edited['note'] : null,
            ],
        ]);

        return $placement->fresh(['childAsset']);
    }

    /**
     * @param  list<int>  $ignorePlacementIds
     */
    protected function assertSpanValid(
        Asset $rack,
        RuPlacementFace $face,
        int $startRu,
        int $ruHeight,
        ?int $ignorePlacementId,
        bool $fullDepth = false,
        RuPlacementWidth $width = RuPlacementWidth::Full,
        int $slot = 1,
        array $ignorePlacementIds = [],
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

        $ignoreIds = $ignorePlacementIds;
        if ($ignorePlacementId !== null) {
            $ignoreIds[] = $ignorePlacementId;
        }
        $ignoreIds = array_values(array_unique(array_map(intval(...), $ignoreIds)));

        $candidate = [
            'face' => $face,
            'start_ru' => $startRu,
            'ru_height' => $ruHeight,
            'full_depth' => $fullDepth,
            'width' => $width,
            'slot' => $slot,
        ];

        $overlaps = AssetRuPlacement::query()
            ->where('rack_asset_id', $rack->id)
            ->when($ignoreIds !== [], fn ($query) => $query->whereNotIn('id', $ignoreIds))
            ->get()
            ->filter(fn (AssetRuPlacement $existing): bool => $this->spansConflict($candidate, [
                'face' => $existing->face,
                'start_ru' => (int) $existing->start_ru,
                'ru_height' => (int) $existing->ru_height,
                'full_depth' => (bool) $existing->full_depth,
                'width' => $existing->width ?? RuPlacementWidth::Full,
                'slot' => (int) ($existing->slot ?? 1),
            ]));

        if ($overlaps->isNotEmpty()) {
            throw ValidationException::withMessages([
                'start_ru' => $fullDepth
                    ? 'This full-depth placement overlaps another placement on the front or rear face.'
                    : 'This placement overlaps another placement on the same face.',
            ]);
        }
    }

    /**
     * A shared RU span conflicts when, on any face both occupy, either bay is full width, the widths differ, or the slots match.
     * Full-depth gear is stored on the front; its rear column is the mirror of that stored slot.
     *
     * @param  array{face: RuPlacementFace, start_ru: int, ru_height: int, full_depth: bool, width: RuPlacementWidth, slot: int}  $left
     * @param  array{face: RuPlacementFace, start_ru: int, ru_height: int, full_depth: bool, width: RuPlacementWidth, slot: int}  $right
     */
    protected function spansConflict(array $left, array $right): bool
    {
        $leftEnd = $left['start_ru'] + $left['ru_height'] - 1;
        $rightEnd = $right['start_ru'] + $right['ru_height'] - 1;
        if ($left['start_ru'] > $rightEnd || $right['start_ru'] > $leftEnd) {
            return false;
        }

        $leftFaces = $left['full_depth']
            ? [RuPlacementFace::Front->value, RuPlacementFace::Rear->value]
            : [$left['face']->value];
        $rightFaces = $right['full_depth']
            ? [RuPlacementFace::Front->value, RuPlacementFace::Rear->value]
            : [$right['face']->value];

        $sharedFaces = array_intersect($leftFaces, $rightFaces);
        if ($sharedFaces === []) {
            return false;
        }

        foreach ($sharedFaces as $faceValue) {
            $face = RuPlacementFace::from($faceValue);
            if ($this->baysConflict(
                $left['width'],
                $this->slotOnFace($left, $face),
                $right['width'],
                $this->slotOnFace($right, $face),
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Column a placement occupies when that face is drawn left-to-right.
     *
     * @param  array{face: RuPlacementFace, full_depth: bool, width: RuPlacementWidth, slot: int}  $placement
     */
    protected function slotOnFace(array $placement, RuPlacementFace $face): int
    {
        $slot = (int) $placement['slot'];

        if (
            $placement['full_depth']
            && $placement['face'] === RuPlacementFace::Front
            && $face === RuPlacementFace::Rear
        ) {
            return $placement['width']->mirroredSlot($slot);
        }

        return $slot;
    }

    protected function baysConflict(RuPlacementWidth $width, int $slot, RuPlacementWidth $otherWidth, int $otherSlot): bool
    {
        if ($width === RuPlacementWidth::Full || $otherWidth === RuPlacementWidth::Full) {
            return true;
        }

        if ($width !== $otherWidth) {
            return true;
        }

        return $slot === $otherSlot;
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

    protected function resolveWidth(mixed $width): RuPlacementWidth
    {
        if ($width instanceof RuPlacementWidth) {
            return $width;
        }

        if ($width === null || $width === '') {
            return RuPlacementWidth::Full;
        }

        $resolved = RuPlacementWidth::tryFrom((string) $width);
        if (! $resolved) {
            throw ValidationException::withMessages([
                'width' => 'Width must be full, half, or third.',
            ]);
        }

        return $resolved;
    }

    protected function resolveSlot(RuPlacementWidth $width, mixed $slot, bool $explicit): int
    {
        $hasValue = $slot !== null && $slot !== '';
        $resolved = $hasValue ? (int) $slot : 1;

        if ($width === RuPlacementWidth::Full) {
            if ($explicit && $hasValue && $resolved !== 1) {
                throw ValidationException::withMessages([
                    'slot' => RuPlacementWidth::FULL_SLOT_MESSAGE,
                ]);
            }

            return 1;
        }

        if ($resolved < 1 || $resolved > $width->maxSlot()) {
            throw ValidationException::withMessages([
                'slot' => RuPlacementWidth::INVALID_SLOT_MESSAGE,
            ]);
        }

        return $resolved;
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
