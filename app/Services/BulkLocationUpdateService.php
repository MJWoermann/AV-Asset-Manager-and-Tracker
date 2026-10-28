<?php

namespace App\Services;

use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\Location;
use App\Models\LocationAlias;
use App\Models\ScanSession;
use App\Models\SpareGroup;
use App\Models\SparePart;
use App\Models\UnknownScan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkLocationUpdateService
{
    /**
     * @param  array{
     *     location_ids: array<int, int>,
     *     action: string,
     *     fields?: array<string, mixed>,
     *     replacement_location_id?: int|null,
     * }  $payload
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int, kept: int}
     */
    public function handle(array $payload): array
    {
        $locationIds = collect($payload['location_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($payload, $locationIds) {
            return match ($payload['action'] ?? '') {
                'delete' => $this->deleteLocations(
                    $locationIds,
                    isset($payload['replacement_location_id']) ? (int) $payload['replacement_location_id'] : null,
                ),
                'merge_duplicates' => $this->mergeDuplicateNames($locationIds),
                'update' => $this->updateLocations($locationIds, $payload['fields'] ?? []),
                default => throw ValidationException::withMessages([
                    'action' => 'Unsupported bulk action.',
                ]),
            };
        });
    }

    /**
     * @param  Collection<int, int|string>  $locationIds
     * @param  array<string, mixed>  $fields
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int, kept: int}
     */
    protected function updateLocations(Collection $locationIds, array $fields): array
    {
        $locations = Location::query()->whereIn('id', $locationIds)->get();
        $updated = 0;

        foreach ($locations as $location) {
            $changes = $this->attributeChangesFor($location, $fields);

            if ($changes === []) {
                continue;
            }

            $location->update($changes);
            $updated++;
        }

        return [
            'updated' => $updated,
            'deleted' => 0,
            'merged' => 0,
            'assets_moved' => 0,
            'kept' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function attributeChangesFor(Location $location, array $fields): array
    {
        $changes = [];

        if (! array_key_exists('parent_id', $fields)) {
            return $changes;
        }

        $parentId = $fields['parent_id'];

        if ($parentId !== null) {
            $parentId = (int) $parentId;

            if ($parentId === (int) $location->id) {
                return $changes;
            }

            if (in_array($parentId, Location::selfAndDescendantIds((int) $location->id), true)) {
                return $changes;
            }
        }

        $changes['parent_id'] = $parentId;

        return $changes;
    }

    /**
     * @param  Collection<int, int|string>  $locationIds
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int, kept: int}
     */
    protected function deleteLocations(Collection $locationIds, ?int $replacementId): array
    {
        $locations = Location::query()
            ->whereIn('id', $locationIds)
            ->withCount('assets')
            ->orderByDesc('id')
            ->get();

        $replacement = null;
        if ($locations->contains(fn (Location $location): bool => (int) $location->assets_count > 0)) {
            $replacement = $replacementId !== null
                ? Location::query()->find($replacementId)
                : null;

            if ($replacement === null) {
                throw ValidationException::withMessages([
                    'replacement_location_id' => 'Choose a replacement location for assets.',
                ]);
            }
        }

        $deleted = 0;
        $kept = 0;
        $assetsMoved = 0;

        foreach ($locations as $location) {
            if ((int) $location->assets_count === 0) {
                $kept++;

                continue;
            }

            if ($replacement === null || $location->is($replacement)) {
                continue;
            }

            $assetsMoved += $this->reassignAndDelete($location, $replacement);
            $deleted++;
        }

        return [
            'updated' => 0,
            'deleted' => $deleted,
            'merged' => 0,
            'assets_moved' => $assetsMoved,
            'kept' => $kept,
        ];
    }

    public function deleteWithReplacement(Location $location, Location $replacement): void
    {
        if ($location->is($replacement)) {
            throw ValidationException::withMessages([
                'replacement_location_id' => 'Choose a different location.',
            ]);
        }

        DB::transaction(function () use ($location, $replacement): void {
            if (! $location->assets()->exists()) {
                throw ValidationException::withMessages([
                    'location' => 'Locations with no assets stay on the list.',
                ]);
            }

            $this->reassignAndDelete($location, $replacement);
        });
    }

    /**
     * Move records that point at the location, then delete it.
     * Child locations are left in place (their parent becomes empty) so locations
     * with no assets remain on the list.
     */
    protected function reassignAndDelete(Location $location, Location $replacement): int
    {
        $fromId = (int) $location->id;
        $toId = (int) $replacement->id;

        $assetsMoved = Asset::query()
            ->where('location_id', $fromId)
            ->update(['location_id' => $toId]);

        SparePart::query()->where('location_id', $fromId)->update(['location_id' => $toId]);
        SpareGroup::query()->where('location_id', $fromId)->update(['location_id' => $toId]);
        ScanSession::query()->where('location_id', $fromId)->update(['location_id' => $toId]);
        UnknownScan::query()->where('location_id', $fromId)->update(['location_id' => $toId]);
        AssetCheckout::query()->where('location_id', $fromId)->update(['location_id' => $toId]);

        LocationAlias::recordMerge($location, $replacement);
        $location->delete();

        return $assetsMoved;
    }

    /**
     * Merge selected locations that share the same name (case-insensitive).
     * Assets and child locations on duplicates move to the kept location.
     * Prefer keeping a location that has a room code and sits under a site.
     *
     * @param  Collection<int, int|string>  $locationIds
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int, kept: int}
     */
    protected function mergeDuplicateNames(Collection $locationIds): array
    {
        $locations = Location::query()->whereIn('id', $locationIds)->orderBy('id')->get();

        $groups = $locations->groupBy(fn (Location $location): string => $this->normalizedName($location->name))
            ->filter(fn (Collection $group): bool => $group->count() > 1);

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages([
                'location_ids' => 'None of the selected locations share a duplicate name to merge.',
            ]);
        }

        /** @var Collection<int, Location> $hierarchyLookup */
        $hierarchyLookup = Location::query()->get(['id', 'parent_id', 'type'])->keyBy('id');

        $deleted = 0;
        $merged = 0;
        $assetsMoved = 0;

        foreach ($groups as $group) {
            /** @var Location $survivor */
            $survivor = $this->chooseMergeSurvivor($group, $hierarchyLookup);
            $duplicates = $group->where('id', '!=', $survivor->id)->values();
            $duplicateIds = $duplicates->pluck('id')->map(fn ($id) => (int) $id)->all();
            $groupIds = $group->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($survivor->parent_id !== null && in_array((int) $survivor->parent_id, $duplicateIds, true)) {
                $survivor->update(['parent_id' => $this->parentOutsideGroup((int) $survivor->parent_id, $groupIds)]);
            }

            $assetsMoved += Asset::query()
                ->whereIn('location_id', $duplicateIds)
                ->update(['location_id' => $survivor->id]);

            Location::query()
                ->whereIn('parent_id', $duplicateIds)
                ->where('id', '!=', $survivor->id)
                ->update(['parent_id' => $survivor->id]);

            foreach ($duplicates as $duplicate) {
                LocationAlias::recordMerge($duplicate, $survivor);
                $duplicate->delete();
                $deleted++;
            }

            $merged++;
        }

        return [
            'updated' => 0,
            'deleted' => $deleted,
            'merged' => $merged,
            'assets_moved' => $assetsMoved,
            'kept' => 0,
        ];
    }

    /**
     * @param  Collection<int, Location>  $group
     * @param  Collection<int, Location>  $hierarchyLookup
     */
    protected function chooseMergeSurvivor(Collection $group, Collection $hierarchyLookup): Location
    {
        return $group
            ->sort(function (Location $a, Location $b) use ($hierarchyLookup): int {
                $scoreCmp = $this->mergeSurvivorScore($b, $hierarchyLookup)
                    <=> $this->mergeSurvivorScore($a, $hierarchyLookup);

                if ($scoreCmp !== 0) {
                    return $scoreCmp;
                }

                return $a->id <=> $b->id;
            })
            ->first();
    }

    /**
     * @param  Collection<int, Location>  $hierarchyLookup
     */
    protected function mergeSurvivorScore(Location $location, Collection $hierarchyLookup): int
    {
        $score = 0;

        if (filled($location->room_code)) {
            $score += 1;
        }

        if ($this->locationHasSite($location, $hierarchyLookup)) {
            $score += 1;
        }

        return $score;
    }

    /**
     * True when the location is a site or has a site ancestor.
     *
     * @param  Collection<int, Location>  $hierarchyLookup
     */
    protected function locationHasSite(Location $location, Collection $hierarchyLookup): bool
    {
        if ($location->type === LocationType::Site) {
            return true;
        }

        $currentId = $location->parent_id !== null ? (int) $location->parent_id : null;
        $guard = 0;

        while ($currentId !== null && $guard < 100) {
            $parent = $hierarchyLookup->get($currentId);

            if ($parent === null) {
                return false;
            }

            if ($parent->type === LocationType::Site) {
                return true;
            }

            $currentId = $parent->parent_id !== null ? (int) $parent->parent_id : null;
            $guard++;
        }

        return false;
    }

    /**
     * @param  list<int>  $groupIds
     */
    protected function parentOutsideGroup(int $parentId, array $groupIds): ?int
    {
        $currentId = $parentId;
        $guard = 0;

        while ($currentId !== null && in_array($currentId, $groupIds, true) && $guard < 100) {
            $parent = Location::query()->find($currentId);
            $currentId = $parent?->parent_id !== null ? (int) $parent->parent_id : null;
            $guard++;
        }

        return $currentId;
    }

    protected function normalizedName(string $name): string
    {
        return mb_strtolower(trim($name));
    }
}
