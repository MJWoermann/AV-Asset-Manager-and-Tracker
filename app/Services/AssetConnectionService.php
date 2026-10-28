<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\AuditLog;
use App\Models\ConnectionBus;
use App\Models\ConnectionType;
use App\Support\AssetExportColumns;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AssetConnectionService
{
    public function __construct(protected ConnectionBusService $buses) {}

    /**
     * Return interface IDs in canonical undirected order (lower id first).
     *
     * @return array{0: int, 1: int}
     */
    public function canonicalizeInterfaceIds(int $interfaceIdA, int $interfaceIdB): array
    {
        if ($interfaceIdA === $interfaceIdB) {
            throw new InvalidArgumentException('A connection requires two different interfaces.');
        }

        return $interfaceIdA < $interfaceIdB
            ? [$interfaceIdA, $interfaceIdB]
            : [$interfaceIdB, $interfaceIdA];
    }

    /**
     * @return array{0: AssetInterface, 1: AssetInterface}
     */
    public function canonicalizeInterfaces(AssetInterface $interfaceA, AssetInterface $interfaceB): array
    {
        [$idA, $idB] = $this->canonicalizeInterfaceIds(
            (int) $interfaceA->getKey(),
            (int) $interfaceB->getKey(),
        );

        return $idA === (int) $interfaceA->getKey()
            ? [$interfaceA, $interfaceB]
            : [$interfaceB, $interfaceA];
    }

    /**
     * @param  array{protocol?: string|null, label?: string|null, notes?: string|null}  $attributes
     */
    public function create(
        AssetInterface $interfaceA,
        AssetInterface $interfaceB,
        ConnectionType $connectionType,
        array $attributes = [],
    ): AssetConnection {
        $interfaceA->loadMissing('asset');
        $interfaceB->loadMissing('asset');

        if ((int) $interfaceA->asset_id === (int) $interfaceB->asset_id) {
            throw ValidationException::withMessages([
                'peer_interface_id' => 'Connections must link interfaces on different assets.',
            ]);
        }

        [$canonicalA, $canonicalB] = $this->canonicalizeInterfaces($interfaceA, $interfaceB);

        $protocol = isset($attributes['protocol']) ? trim((string) $attributes['protocol']) : null;
        $protocol = $protocol === '' ? null : $protocol;

        if ($connectionType->allows_protocol && $protocol === null) {
            throw ValidationException::withMessages([
                'protocol' => 'A protocol is required for this connection type.',
            ]);
        }

        if (! $connectionType->allows_protocol) {
            $protocol = null;
        }

        $label = isset($attributes['label']) ? trim((string) $attributes['label']) : null;
        $notes = isset($attributes['notes']) ? trim((string) $attributes['notes']) : null;

        $connection = AssetConnection::query()->create([
            'interface_a_id' => $canonicalA->id,
            'interface_b_id' => $canonicalB->id,
            'connection_type_id' => $connectionType->id,
            'protocol' => $protocol,
            'label' => $label === '' ? null : $label,
            'notes' => $notes === '' ? null : $notes,
        ]);

        $this->auditConnectionForAssets($connection, 'connection_created');

        return $connection;
    }

    public function delete(AssetConnection $connection): void
    {
        $connection->loadMissing([
            'connectionType',
            'interfaceA.asset',
            'interfaceB.asset',
        ]);

        $this->auditConnectionForAssets($connection, 'connection_removed');

        $connection->delete();
    }

    /**
     * Write asset History entries for both ends of a connection.
     */
    protected function auditConnectionForAssets(AssetConnection $connection, string $action): void
    {
        $connection->loadMissing([
            'connectionType',
            'interfaceA.asset',
            'interfaceB.asset',
        ]);

        $interfaceA = $connection->interfaceA;
        $interfaceB = $connection->interfaceB;
        $assetA = $interfaceA?->asset;
        $assetB = $interfaceB?->asset;

        if (! $interfaceA || ! $interfaceB || ! $assetA || ! $assetB) {
            return;
        }

        $shared = [
            'connection_id' => $connection->id,
            'connection_type' => $connection->connectionType?->name,
            'protocol' => $connection->protocol,
            'label' => $connection->label,
        ];

        $this->writeAssetConnectionAudit($assetA, $action, array_merge($shared, [
            'peer_asset_id' => $assetB->id,
            'peer_asset' => $assetB->name,
            'local_interface' => $interfaceA->name,
            'peer_interface' => $interfaceB->name,
        ]));

        $this->writeAssetConnectionAudit($assetB, $action, array_merge($shared, [
            'peer_asset_id' => $assetA->id,
            'peer_asset' => $assetA->name,
            'local_interface' => $interfaceB->name,
            'peer_interface' => $interfaceA->name,
        ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function writeAssetConnectionAudit(Asset $asset, string $action, array $payload): void
    {
        $isCreated = $action === 'connection_created';

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => $action,
            'old_values' => $isCreated ? null : $payload,
            'new_values' => $isCreated ? $payload : null,
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array{
     *     connection_type_ids?: list<int>|null,
     *     location_id?: int|null,
     *     item_type_id?: int|null,
     *     search?: string|null,
     *     asset_id?: int|null
     * }  $filters
     * @return array{
     *     nodes: list<array<string, mixed>>,
     *     edges: list<array<string, mixed>>,
     *     focus_asset_id: int|null,
     *     asset_count: int
     * }
     */
    public function buildGraph(array $filters = []): array
    {
        $focusAssetId = isset($filters['asset_id']) && $filters['asset_id'] !== '' && $filters['asset_id'] !== null
            ? (int) $filters['asset_id']
            : null;

        $connections = $this->filteredConnections($filters)
            ->with([
                'connectionType',
                'interfaceA.asset.location.parent.parent.parent.parent',
                'interfaceA.asset.itemType',
                'interfaceB.asset.location.parent.parent.parent.parent',
                'interfaceB.asset.itemType',
            ])
            ->get();

        $buses = $this->buses->filteredBuses($filters)->get();

        if ($focusAssetId) {
            $componentAssetIds = $this->connectedAssetIds($connections, $focusAssetId);
            $componentAssetIds = $this->buses->expandConnectedAssetIds($buses, $componentAssetIds);

            // Re-expand until peer edges and buses stop adding assets.
            $previousCount = 0;
            while (count($componentAssetIds) !== $previousCount) {
                $previousCount = count($componentAssetIds);
                $componentAssetIds = $this->connectedAssetIds($connections, $focusAssetId, $componentAssetIds);
                $componentAssetIds = $this->buses->expandConnectedAssetIds($buses, $componentAssetIds);
            }

            $connections = $connections->filter(function (AssetConnection $connection) use ($componentAssetIds): bool {
                $assetAId = $connection->interfaceA?->asset_id;
                $assetBId = $connection->interfaceB?->asset_id;

                return $assetAId
                    && $assetBId
                    && isset($componentAssetIds[$assetAId], $componentAssetIds[$assetBId]);
            })->values();

            $buses = $buses->filter(function (ConnectionBus $bus) use ($componentAssetIds): bool {
                return $bus->interfaces->contains(
                    fn (AssetInterface $interface): bool => isset($componentAssetIds[(int) $interface->asset_id])
                );
            })->values();
        }

        /** @var array<int, Asset> $assets */
        $assets = [];
        /** @var array<int, array<int, ConnectionType>> $typesByAsset */
        $typesByAsset = [];

        foreach ($connections as $connection) {
            $assetA = $connection->interfaceA?->asset;
            $assetB = $connection->interfaceB?->asset;
            $type = $connection->connectionType;

            if (! $assetA || ! $assetB || ! $type) {
                continue;
            }

            $assets[$assetA->id] = $assetA;
            $assets[$assetB->id] = $assetB;
            $typesByAsset[$assetA->id][$type->id] = $type;
            $typesByAsset[$assetB->id][$type->id] = $type;
        }

        foreach ($buses as $bus) {
            $type = $bus->connectionType;
            if (! $type) {
                continue;
            }

            foreach ($bus->interfaces as $interface) {
                $asset = $interface->asset;
                if (! $asset) {
                    continue;
                }

                $assets[$asset->id] = $asset;
                $typesByAsset[$asset->id][$type->id] = $type;
            }
        }

        /** @var list<array<string, mixed>> $nodes */
        $nodes = [];
        /** @var array<string, string> $endpointByAssetType */
        $endpointByAssetType = [];

        foreach ($assets as $assetId => $asset) {
            $types = $typesByAsset[$assetId] ?? [];
            $built = $this->graphNodesForAsset($asset, $types, $focusAssetId);
            array_push($nodes, ...$built['nodes']);

            foreach ($built['endpoints'] as $typeId => $endpointId) {
                $endpointByAssetType[$assetId.':'.$typeId] = $endpointId;
            }
        }

        $edges = [];

        foreach ($connections as $connection) {
            $assetA = $connection->interfaceA?->asset;
            $assetB = $connection->interfaceB?->asset;
            $type = $connection->connectionType;

            if (! $assetA || ! $assetB || ! $type) {
                continue;
            }

            $source = $endpointByAssetType[$assetA->id.':'.$type->id] ?? 'asset-'.$assetA->id;
            $target = $endpointByAssetType[$assetB->id.':'.$type->id] ?? 'asset-'.$assetB->id;

            $edges[] = [
                'data' => [
                    'id' => 'connection-'.$connection->id,
                    'connection_id' => $connection->id,
                    'connection_type_id' => $type->id,
                    'source' => $source,
                    'target' => $target,
                    'label' => $type->name,
                    'color' => $type->color ?: '#64748b',
                    'icon' => $type->icon ?: 'generic',
                    'protocol' => $connection->protocol,
                    'connection_label' => $connection->label,
                    'notes' => $connection->notes,
                    'interface_a' => $connection->interfaceA->name,
                    'interface_b' => $connection->interfaceB->name,
                    'asset_a_id' => $assetA->id,
                    'asset_a_name' => $assetA->name,
                    'asset_b_id' => $assetB->id,
                    'asset_b_name' => $assetB->name,
                    'asset_a_url' => route('assets.show', [$assetA, 'tab' => 'connections']),
                    'asset_b_url' => route('assets.show', [$assetB, 'tab' => 'connections']),
                ],
            ];
        }

        foreach ($buses as $bus) {
            $type = $bus->connectionType;
            if (! $type) {
                continue;
            }

            $busNodeId = 'bus-'.$bus->id;
            $nodes[] = [
                'data' => [
                    'id' => $busNodeId,
                    'kind' => 'bus',
                    'bus_id' => $bus->id,
                    'name' => $bus->name,
                    'label' => $bus->name,
                    'type_name' => $type->name,
                    'connection_type_id' => $type->id,
                    'color' => $type->color ?: '#64748b',
                    'icon' => $type->icon ?: 'generic',
                    'notes' => $bus->notes,
                    'member_count' => $bus->interfaces->count(),
                    'url' => null,
                    'focus' => false,
                ],
                'classes' => 'bus',
            ];

            foreach ($bus->interfaces as $interface) {
                $asset = $interface->asset;
                if (! $asset) {
                    continue;
                }

                $source = $endpointByAssetType[$asset->id.':'.$type->id] ?? 'asset-'.$asset->id;

                $edges[] = [
                    'data' => [
                        'id' => 'bus-'.$bus->id.'-iface-'.$interface->id,
                        'bus_id' => $bus->id,
                        'connection_type_id' => $type->id,
                        'source' => $source,
                        'target' => $busNodeId,
                        'label' => $type->name,
                        'color' => $type->color ?: '#64748b',
                        'icon' => $type->icon ?: 'generic',
                        'protocol' => null,
                        'connection_label' => $bus->name,
                        'notes' => $bus->notes,
                        'interface_a' => $interface->name,
                        'interface_b' => $bus->name,
                        'asset_a_id' => $asset->id,
                        'asset_a_name' => $asset->name,
                        'asset_b_id' => null,
                        'asset_b_name' => $bus->name,
                        'asset_a_url' => route('assets.show', [$asset, 'tab' => 'connections']),
                        'asset_b_url' => null,
                        'is_bus' => true,
                    ],
                ];
            }
        }

        // #region agent log
        $nodeIds = [];
        $duplicateIds = [];
        $parentIds = [];
        foreach ($nodes as $node) {
            $nodeId = (string) ($node['data']['id'] ?? '');
            if (isset($nodeIds[$nodeId])) {
                $duplicateIds[] = $nodeId;
            }
            $nodeIds[$nodeId] = true;
            if (($node['data']['compound'] ?? false) === true) {
                $parentIds[$nodeId] = true;
            }
        }
        $missingEndpoints = [];
        $selfLoops = [];
        $parentEndpoints = [];
        foreach ($edges as $edge) {
            $source = (string) ($edge['data']['source'] ?? '');
            $target = (string) ($edge['data']['target'] ?? '');
            $edgeId = (string) ($edge['data']['id'] ?? '');
            if (! isset($nodeIds[$source]) || ! isset($nodeIds[$target])) {
                $missingEndpoints[] = $edgeId;
            }
            if ($source !== '' && $source === $target) {
                $selfLoops[] = $edgeId;
            }
            if (isset($parentIds[$source]) || isset($parentIds[$target])) {
                $parentEndpoints[] = $edgeId;
            }
        }
        $multiTypeAssets = [];
        foreach ($typesByAsset as $assetId => $types) {
            if (count($types) < 2) {
                continue;
            }
            $multiTypeAssets[] = [
                'asset_id' => $assetId,
                'types' => array_values(array_map(
                    fn (ConnectionType $type): string => (string) $type->slug,
                    $types
                )),
            ];
        }
        file_put_contents(base_path('debug-3223d1.log'), json_encode([
            'sessionId' => '3223d1',
            'hypothesisId' => 'H1',
            'location' => 'AssetConnectionService.php:buildGraph',
            'message' => 'graph built',
            'data' => [
                'filters' => array_values($filters['connection_type_ids'] ?? []),
                'asset_count' => count($assets),
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'multi_type_count' => count($multiTypeAssets),
                'multi_type_assets' => array_slice($multiTypeAssets, 0, 20),
                'duplicate_ids' => array_slice($duplicateIds, 0, 20),
                'missing_endpoints' => array_slice($missingEndpoints, 0, 20),
                'self_loops' => array_slice($selfLoops, 0, 20),
                'parent_endpoints' => array_slice($parentEndpoints, 0, 20),
            ],
            'timestamp' => (int) round(microtime(true) * 1000),
        ])."\n", FILE_APPEND);
        // #endregion

        return [
            'nodes' => $nodes,
            'edges' => $edges,
            'focus_asset_id' => $focusAssetId,
            'asset_count' => count($assets),
        ];
    }

    /**
     * @param  array{
     *     connection_type_ids?: list<int>|null,
     *     location_id?: int|null,
     *     item_type_id?: int|null,
     *     search?: string|null,
     *     asset_id?: int|null
     * }  $filters
     */
    public function filteredConnections(array $filters = []): Builder
    {
        $query = AssetConnection::query();

        $typeIds = collect($filters['connection_type_ids'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if ($typeIds !== []) {
            $query->whereIn('connection_type_id', $typeIds);
        }

        $locationId = isset($filters['location_id']) && $filters['location_id'] !== ''
            ? (int) $filters['location_id']
            : null;
        $itemTypeId = isset($filters['item_type_id']) && $filters['item_type_id'] !== ''
            ? (int) $filters['item_type_id']
            : null;
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';

        if ($locationId || $itemTypeId || $search !== '') {
            $query->where(function (Builder $outer) use ($locationId, $itemTypeId, $search): void {
                $outer->whereHas('interfaceA.asset', function (Builder $assetQuery) use ($locationId, $itemTypeId, $search): void {
                    $this->constrainAssetFilters($assetQuery, $locationId, $itemTypeId, $search);
                })->orWhereHas('interfaceB.asset', function (Builder $assetQuery) use ($locationId, $itemTypeId, $search): void {
                    $this->constrainAssetFilters($assetQuery, $locationId, $itemTypeId, $search);
                });
            });
        }

        return $query->orderBy('id');
    }

    /**
     * Asset IDs in the undirected connected component containing $focusAssetId.
     *
     * @param  Collection<int, AssetConnection>  $connections
     * @param  array<int, true>|null  $seedVisited  Optional seed set (e.g. after expanding buses).
     * @return array<int, true>
     */
    public function connectedAssetIds(Collection $connections, int $focusAssetId, ?array $seedVisited = null): array
    {
        /** @var array<int, list<int>> $adjacency */
        $adjacency = [];

        foreach ($connections as $connection) {
            $assetAId = $connection->interfaceA?->asset_id;
            $assetBId = $connection->interfaceB?->asset_id;

            if (! $assetAId || ! $assetBId) {
                continue;
            }

            $adjacency[(int) $assetAId][] = (int) $assetBId;
            $adjacency[(int) $assetBId][] = (int) $assetAId;
        }

        $visited = $seedVisited ?? [$focusAssetId => true];
        if (! isset($visited[$focusAssetId])) {
            $visited[$focusAssetId] = true;
        }

        if ($seedVisited === null && ! isset($adjacency[$focusAssetId])) {
            return [$focusAssetId => true];
        }

        $queue = array_keys($visited);

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($adjacency[$current] ?? [] as $peerId) {
                if (isset($visited[$peerId])) {
                    continue;
                }

                $visited[$peerId] = true;
                $queue[] = $peerId;
            }
        }

        return $visited;
    }

    /**
     * Build Cytoscape nodes for an asset. Assets with 2+ connection types become a
     * compound parent with one child port per type; single-type assets stay one node.
     *
     * @param  array<int, ConnectionType>  $types
     * @return array{
     *     nodes: list<array<string, mixed>>,
     *     endpoints: array<int, string>
     * }
     */
    protected function graphNodesForAsset(Asset $asset, array $types, ?int $focusAssetId): array
    {
        $isFocus = $focusAssetId !== null && (int) $asset->id === $focusAssetId;
        $base = [
            'asset_id' => $asset->id,
            'name' => $asset->name,
            'label' => $asset->name,
            'location' => $asset->location?->name,
            'room_rack' => $this->roomRackLabel($asset),
            'serial_number' => $asset->serial_number,
            'tp_barcode' => $asset->tp_barcode,
            'item_type' => $asset->itemType?->name,
            'url' => route('assets.show', [$asset, 'tab' => 'connections']),
            'focus' => $isFocus,
        ];

        $endpoints = [];
        $nodes = [];

        if (count($types) >= 2) {
            $parentId = 'asset-'.$asset->id;
            $parent = [
                'data' => [
                    ...$base,
                    'id' => $parentId,
                    'kind' => 'asset',
                    'compound' => true,
                    'color' => '#9ca3af',
                    'icon' => null,
                ],
            ];
            if ($isFocus) {
                $parent['classes'] = 'focus';
            }
            $nodes[] = $parent;

            foreach ($types as $type) {
                $portId = 'asset-'.$asset->id.'-type-'.$type->id;
                $nodes[] = [
                    'data' => [
                        'id' => $portId,
                        'parent' => $parentId,
                        'asset_id' => $asset->id,
                        'kind' => 'type-port',
                        'name' => $asset->name,
                        'label' => $type->name,
                        'type_name' => $type->name,
                        'location' => $asset->location?->name,
                        'room_rack' => $this->roomRackLabel($asset),
                        'serial_number' => $asset->serial_number,
                        'tp_barcode' => $asset->tp_barcode,
                        'item_type' => $asset->itemType?->name,
                        'url' => route('assets.show', [$asset, 'tab' => 'connections']),
                        'color' => $type->color ?: '#64748b',
                        'icon' => $type->icon ?: 'generic',
                        'connection_type_id' => $type->id,
                        'focus' => false,
                    ],
                    'classes' => 'type-port',
                ];
                $endpoints[$type->id] = $portId;
            }

            return ['nodes' => $nodes, 'endpoints' => $endpoints];
        }

        $type = count($types) === 1 ? reset($types) : null;
        $node = [
            'data' => [
                ...$base,
                'id' => 'asset-'.$asset->id,
                'kind' => 'asset',
                'compound' => false,
                'color' => $type?->color ?: '#00adb7',
                'icon' => $type?->icon ?: 'generic',
                'type_name' => $type?->name,
                'connection_type_id' => $type?->id,
            ],
        ];
        if ($isFocus) {
            $node['classes'] = 'focus';
        }
        $nodes[] = $node;

        if ($type) {
            $endpoints[$type->id] = 'asset-'.$asset->id;
        }

        return ['nodes' => $nodes, 'endpoints' => $endpoints];
    }

    protected function roomRackLabel(Asset $asset): ?string
    {
        $room = AssetExportColumns::value($asset, 'location_room');
        $rack = AssetExportColumns::value($asset, 'location_rack');
        $parts = array_values(array_filter(
            [$room, $rack],
            fn (string $part): bool => $part !== '',
        ));

        return $parts === [] ? null : implode(' / ', $parts);
    }

    /**
     * @return Collection<int, array{connection: AssetConnection, peer_interface: AssetInterface, peer_asset: Asset, local_interface: AssetInterface}>
     */
    public function peersForAsset(Asset $asset): Collection
    {
        $asset->loadMissing([
            'interfaces.connectionsAsA.connectionType',
            'interfaces.connectionsAsA.interfaceB.asset.itemType',
            'interfaces.connectionsAsA.interfaceB.asset.location',
            'interfaces.connectionsAsB.connectionType',
            'interfaces.connectionsAsB.interfaceA.asset.itemType',
            'interfaces.connectionsAsB.interfaceA.asset.location',
        ]);

        $rows = collect();

        foreach ($asset->interfaces as $interface) {
            foreach ($interface->connectionsAsA as $connection) {
                $peer = $connection->interfaceB;
                if (! $peer?->asset) {
                    continue;
                }
                $rows->push([
                    'connection' => $connection,
                    'local_interface' => $interface,
                    'peer_interface' => $peer,
                    'peer_asset' => $peer->asset,
                ]);
            }

            foreach ($interface->connectionsAsB as $connection) {
                $peer = $connection->interfaceA;
                if (! $peer?->asset) {
                    continue;
                }
                $rows->push([
                    'connection' => $connection,
                    'local_interface' => $interface,
                    'peer_interface' => $peer,
                    'peer_asset' => $peer->asset,
                ]);
            }
        }

        return $rows;
    }

    protected function constrainAssetFilters(
        Builder $assetQuery,
        ?int $locationId,
        ?int $itemTypeId,
        string $search,
    ): void {
        if ($locationId) {
            $assetQuery->where('location_id', $locationId);
        }

        if ($itemTypeId) {
            $assetQuery->where('item_type_id', $itemTypeId);
        }

        if ($search !== '') {
            $assetQuery->search($search);
        }
    }
}
