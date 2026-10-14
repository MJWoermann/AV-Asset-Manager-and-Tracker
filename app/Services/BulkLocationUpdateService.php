<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Location;
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
     * }  $payload
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int}
     */
    public function handle(array $payload): array
    {
        $locationIds = collect($payload['location_ids'])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($payload, $locationIds) {
            return match ($payload['action'] ?? '') {
                'delete' => $this->deleteLocations($locationIds),
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
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int}
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
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int}
     */
    protected function deleteLocations(Collection $locationIds): array
    {
        $locations = Location::query()->whereIn('id', $locationIds)->orderByDesc('id')->get();
        $deleted = 0;

        foreach ($locations as $location) {
            $location->delete();
            $deleted++;
        }

        return [
            'updated' => 0,
            'deleted' => $deleted,
            'merged' => 0,
            'assets_moved' => 0,
        ];
    }

    /**
     * Merge selected locations that share the same name (case-insensitive).
     * Assets and child locations on duplicates move to the kept location.
     *
     * @param  Collection<int, int|string>  $locationIds
     * @return array{updated: int, deleted: int, merged: int, assets_moved: int}
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

        $deleted = 0;
        $merged = 0;
        $assetsMoved = 0;

        foreach ($groups as $group) {
            /** @var Location $survivor */
            $survivor = $group->first();
            $duplicateIds = $group->slice(1)->pluck('id')->map(fn ($id) => (int) $id)->all();
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

            foreach ($group->slice(1) as $duplicate) {
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
        ];
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
