<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AssetDuplicateService
{
    /**
     * Identifier columns used to group potential duplicate assets.
     * Aligns with import duplicate matching ({@see ImportService::DUPLICATE_KEYS}).
     *
     * @var list<string>
     */
    public const MATCH_KEYS = [
        'serial_number',
        'fmi_ast',
        'mac_address',
        'rig_tag',
        'tp_barcode',
        'ip_address',
    ];

    /**
     * Human-readable labels for match keys.
     *
     * @var array<string, string>
     */
    public const MATCH_KEY_LABELS = [
        'serial_number' => 'Serial number',
        'fmi_ast' => 'FMI / AST',
        'mac_address' => 'MAC address',
        'rig_tag' => 'Rig tag',
        'tp_barcode' => 'TP barcode',
        'ip_address' => 'IP address',
    ];

    /**
     * Find groups of assets that share at least one normalised identifier.
     * Matching is transitive within a department-scoped asset query (union-find).
     *
     * @return list<array{
     *     asset_ids: list<int>,
     *     assets: Collection<int, Asset>,
     *     matched_on: list<string>,
     *     match_values: array<string, string>,
     *     count: int
     * }>
     */
    public function findGroups(?int $limit = 100): array
    {
        $columns = array_values(array_unique(array_merge(
            ['id', 'name', 'department_id', 'item_type_id', 'status', 'parent_id'],
            self::MATCH_KEYS,
        )));

        $assets = Asset::query()
            ->select($columns)
            ->where(function ($query): void {
                foreach (self::MATCH_KEYS as $index => $key) {
                    $method = $index === 0 ? 'whereNotNull' : 'orWhereNotNull';
                    $query->{$method}($key);
                }
            })
            ->orderBy('id')
            ->get();

        if ($assets->count() < 2) {
            return [];
        }

        /** @var array<int, int> $parent */
        $parent = [];
        foreach ($assets as $asset) {
            $parent[(int) $asset->id] = (int) $asset->id;
        }

        $find = function (int $id) use (&$parent, &$find): int {
            if ($parent[$id] !== $id) {
                $parent[$id] = $find($parent[$id]);
            }

            return $parent[$id];
        };

        $union = function (int $a, int $b) use (&$parent, $find): void {
            $rootA = $find($a);
            $rootB = $find($b);
            if ($rootA !== $rootB) {
                $parent[$rootB] = $rootA;
            }
        };

        /** @var array<string, array<string, list<int>>> $buckets */
        $buckets = [];
        /** @var array<string, array<string, string>> $displayValues */
        $displayValues = [];

        foreach ($assets as $asset) {
            foreach (self::MATCH_KEYS as $key) {
                $raw = $asset->getAttribute($key);
                $normalised = $this->normalise($key, $raw);
                if ($normalised === null) {
                    continue;
                }

                $buckets[$key][$normalised][] = (int) $asset->id;
                $displayValues[$key][$normalised] ??= $this->displayValue($key, $raw);
            }
        }

        /** @var array<int, array<string, true>> $matchKeysByRootCandidate */
        $edgeKeys = [];

        foreach ($buckets as $key => $valueMap) {
            foreach ($valueMap as $normalised => $ids) {
                $uniqueIds = array_values(array_unique($ids));
                if (count($uniqueIds) < 2) {
                    continue;
                }

                $first = $uniqueIds[0];
                foreach (array_slice($uniqueIds, 1) as $other) {
                    $union($first, $other);
                }

                foreach ($uniqueIds as $id) {
                    $edgeKeys[$id][$key] = true;
                }
            }
        }

        /** @var array<int, list<int>> $membersByRoot */
        $membersByRoot = [];
        foreach ($assets as $asset) {
            $id = (int) $asset->id;
            $root = $find($id);
            $membersByRoot[$root][] = $id;
        }

        $byId = $assets->keyBy(fn (Asset $asset): int => (int) $asset->id);
        $groups = [];

        foreach ($membersByRoot as $memberIds) {
            $memberIds = array_values(array_unique($memberIds));
            if (count($memberIds) < 2) {
                continue;
            }

            sort($memberIds);

            $matchedOn = [];
            $matchValues = [];

            foreach (self::MATCH_KEYS as $key) {
                $seen = [];
                foreach ($memberIds as $id) {
                    if (! isset($edgeKeys[$id][$key])) {
                        continue;
                    }
                    $raw = $byId->get($id)?->getAttribute($key);
                    $normalised = $this->normalise($key, $raw);
                    if ($normalised === null) {
                        continue;
                    }
                    $seen[$normalised] = $displayValues[$key][$normalised] ?? $this->displayValue($key, $raw);
                }

                if ($seen === []) {
                    continue;
                }

                // Only report keys that actually collide within the group.
                $colliding = [];
                foreach ($seen as $normalised => $display) {
                    $countInGroup = 0;
                    foreach ($memberIds as $id) {
                        $asset = $byId->get($id);
                        if ($asset === null) {
                            continue;
                        }
                        if ($this->normalise($key, $asset->getAttribute($key)) === $normalised) {
                            $countInGroup++;
                        }
                    }
                    if ($countInGroup >= 2) {
                        $colliding[$normalised] = $display;
                    }
                }

                if ($colliding === []) {
                    continue;
                }

                $matchedOn[] = $key;
                $matchValues[$key] = implode(', ', array_values($colliding));
            }

            if ($matchedOn === []) {
                continue;
            }

            $groupAssets = collect($memberIds)
                ->map(fn (int $id) => $byId->get($id))
                ->filter()
                ->values();

            $groups[] = [
                'asset_ids' => $memberIds,
                'assets' => $groupAssets,
                'matched_on' => $matchedOn,
                'match_values' => $matchValues,
                'count' => count($memberIds),
            ];
        }

        usort($groups, function (array $a, array $b): int {
            $countCmp = $b['count'] <=> $a['count'];
            if ($countCmp !== 0) {
                return $countCmp;
            }

            return ($a['asset_ids'][0] ?? 0) <=> ($b['asset_ids'][0] ?? 0);
        });

        if ($limit !== null && $limit > 0) {
            $groups = array_slice($groups, 0, $limit);
        }

        return $groups;
    }

    public function normalise(string $key, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        if ($key === 'mac_address') {
            $compact = Str::lower(preg_replace('/[^a-fA-F0-9]/', '', $trimmed) ?? '');

            return $compact === '' ? null : $compact;
        }

        return Str::lower($trimmed);
    }

    public function displayValue(string $key, mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    public function labelForKey(string $key): string
    {
        return self::MATCH_KEY_LABELS[$key] ?? $key;
    }

    /**
     * Filter detected groups by matched field and/or multi-field matches.
     *
     * @param  list<array{matched_on: list<string>, asset_ids?: list<int>, assets?: mixed, match_values?: array<string, string>, count?: int}>  $groups
     * @return list<array{matched_on: list<string>, asset_ids?: list<int>, assets?: mixed, match_values?: array<string, string>, count?: int}>
     */
    public function filterGroups(array $groups, ?string $field = null, bool $multiFieldOnly = false): array
    {
        $field = $field !== null && $field !== '' ? $field : null;

        if ($field !== null && ! in_array($field, self::MATCH_KEYS, true)) {
            $field = null;
        }

        return array_values(array_filter(
            $groups,
            function (array $group) use ($field, $multiFieldOnly): bool {
                $matchedOn = $group['matched_on'] ?? [];

                if ($field !== null && ! in_array($field, $matchedOn, true)) {
                    return false;
                }

                if ($multiFieldOnly && count($matchedOn) < 2) {
                    return false;
                }

                return true;
            }
        ));
    }

    /**
     * Clear duplicate-detection identifier fields on selected assets.
     *
     * @param  list<int>  $assetIds
     * @param  list<string>  $fields
     * @return array{cleared_assets: int, cleared_values: int}
     */
    public function clearIdentifierFields(array $assetIds, array $fields, int $userId): array
    {
        $ids = collect($assetIds)->map(fn ($id) => (int) $id)->filter(fn (int $id) => $id > 0)->unique()->values();
        $fields = collect($fields)
            ->map(fn ($field) => (string) $field)
            ->filter(fn (string $field) => in_array($field, self::MATCH_KEYS, true))
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'asset_ids' => 'Select at least one asset to clear.',
            ]);
        }

        if ($fields->isEmpty()) {
            throw ValidationException::withMessages([
                'fields' => 'Select at least one identifier field to clear.',
            ]);
        }

        return DB::transaction(function () use ($ids, $fields, $userId) {
            $assets = Asset::query()->whereIn('id', $ids)->get()->keyBy('id');

            if ($assets->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'One or more selected assets are not available in your department.',
                ]);
            }

            $departmentIds = $assets
                ->map(fn (Asset $asset) => $asset->department_id !== null ? (int) $asset->department_id : null)
                ->unique()
                ->values();

            if ($departmentIds->count() > 1) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'All selected assets must belong to the same department.',
                ]);
            }

            $clearedAssets = 0;
            $clearedValues = 0;

            foreach ($ids as $id) {
                /** @var Asset $asset */
                $asset = $assets->get($id);
                $oldValues = [];
                $newValues = [];

                foreach ($fields as $field) {
                    $current = $asset->getAttribute($field);
                    if ($current === null || (is_string($current) && trim($current) === '')) {
                        continue;
                    }

                    $oldValues[$field] = $current;
                    $newValues[$field] = null;
                    $asset->setAttribute($field, null);
                }

                if ($oldValues === []) {
                    continue;
                }

                // Avoid a generic "updated" log; write an explicit clear audit instead.
                Asset::withoutEvents(function () use ($asset): void {
                    $asset->save();
                });

                AuditLog::create([
                    'user_id' => $userId,
                    'department_id' => $asset->department_id !== null ? (int) $asset->department_id : null,
                    'auditable_type' => Asset::class,
                    'auditable_id' => $asset->id,
                    'action' => 'cleared',
                    'old_values' => $oldValues,
                    'new_values' => array_merge($newValues, [
                        'source' => 'duplicate_identifier_clear',
                        'fields' => array_keys($oldValues),
                    ]),
                    'created_at' => now(),
                ]);

                $clearedAssets++;
                $clearedValues += count($oldValues);
            }

            return [
                'cleared_assets' => $clearedAssets,
                'cleared_values' => $clearedValues,
            ];
        });
    }

    /**
     * Set one selected asset as parent and the rest as its children.
     *
     * @param  list<int>  $assetIds
     * @return array{parent_id: int, linked: int, skipped: int}
     */
    public function linkAsParentAndChildren(array $assetIds, int $parentId, int $userId): array
    {
        $ids = collect($assetIds)->map(fn ($id) => (int) $id)->filter(fn (int $id) => $id > 0)->unique()->values();

        if ($ids->count() < 2) {
            throw ValidationException::withMessages([
                'asset_ids' => 'Select at least two assets to create a parent/child relationship.',
            ]);
        }

        if (! $ids->contains($parentId)) {
            throw ValidationException::withMessages([
                'parent_id' => 'The parent must be one of the selected assets.',
            ]);
        }

        return DB::transaction(function () use ($ids, $parentId, $userId) {
            $assets = Asset::query()->whereIn('id', $ids)->get()->keyBy('id');

            if ($assets->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'One or more selected assets are not available in your department.',
                ]);
            }

            $departmentIds = $assets
                ->map(fn (Asset $asset) => $asset->department_id !== null ? (int) $asset->department_id : null)
                ->unique()
                ->values();

            if ($departmentIds->count() > 1) {
                throw ValidationException::withMessages([
                    'asset_ids' => 'All selected assets must belong to the same department.',
                ]);
            }

            /** @var Asset $parent */
            $parent = $assets->get($parentId);
            $childIds = $ids->filter(fn (int $id) => $id !== $parentId)->values();

            foreach ($childIds as $childId) {
                /** @var Asset $child */
                $child = $assets->get($childId);

                if ($this->wouldCreateParentCycle($parent, $child)) {
                    throw ValidationException::withMessages([
                        'parent_id' => 'Cannot make "'.$parent->name.'" the parent of "'.$child->name.'" — that would create a cycle.',
                    ]);
                }
            }

            $linked = 0;
            $skipped = 0;

            foreach ($childIds as $childId) {
                /** @var Asset $child */
                $child = $assets->get($childId);

                if ((int) ($child->parent_id ?? 0) === $parentId) {
                    $skipped++;

                    continue;
                }

                $oldParentId = $child->parent_id !== null ? (int) $child->parent_id : null;
                $oldLocationId = $child->location_id !== null ? (int) $child->location_id : null;

                $child->parent_id = $parentId;
                $child->location_id = $parent->location_id;
                if ($parent->department_id !== null) {
                    $child->department_id = $parent->department_id;
                }

                // Skip AuditableObserver; write an explicit reparented audit instead.
                // Location inheritance matches Asset::saving / updated cascade behaviour.
                Asset::withoutEvents(function () use ($child): void {
                    $child->save();
                });

                $this->cascadeLocationToDescendants($child, $parent->location_id !== null ? (int) $parent->location_id : null);

                AuditLog::create([
                    'user_id' => $userId,
                    'department_id' => $child->department_id !== null ? (int) $child->department_id : null,
                    'auditable_type' => Asset::class,
                    'auditable_id' => $child->id,
                    'action' => 'reparented',
                    'old_values' => [
                        'parent_id' => $oldParentId,
                        'location_id' => $oldLocationId,
                    ],
                    'new_values' => [
                        'parent_id' => $parentId,
                        'location_id' => $child->location_id !== null ? (int) $child->location_id : null,
                        'parent_name' => $parent->name,
                        'source' => 'duplicate_parent_child',
                    ],
                    'created_at' => now(),
                ]);

                $linked++;
            }

            return [
                'parent_id' => $parentId,
                'linked' => $linked,
                'skipped' => $skipped,
            ];
        });
    }

    /**
     * True when $child is an ancestor of $parent (or is $parent), so parenting would cycle.
     */
    protected function wouldCreateParentCycle(Asset $parent, Asset $child): bool
    {
        if ((int) $parent->id === (int) $child->id) {
            return true;
        }

        $ancestorId = $parent->parent_id;
        $guard = 0;

        while ($ancestorId !== null && $guard < 1000) {
            if ((int) $ancestorId === (int) $child->id) {
                return true;
            }

            $ancestorId = Asset::query()->withoutGlobalScopes()->whereKey($ancestorId)->value('parent_id');
            $guard++;
        }

        return false;
    }

    protected function cascadeLocationToDescendants(Asset $asset, ?int $locationId): void
    {
        $children = Asset::query()->where('parent_id', $asset->id)->get();

        foreach ($children as $child) {
            if ((int) ($child->location_id ?? 0) === (int) ($locationId ?? 0)
                && (($child->location_id === null) === ($locationId === null))) {
                $this->cascadeLocationToDescendants($child, $locationId);

                continue;
            }

            Asset::withoutEvents(function () use ($child, $locationId): void {
                $child->location_id = $locationId;
                $child->save();
            });

            $this->cascadeLocationToDescendants($child, $locationId);
        }
    }
}
