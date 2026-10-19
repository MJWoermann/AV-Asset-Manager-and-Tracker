<?php

namespace App\Services;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
}
