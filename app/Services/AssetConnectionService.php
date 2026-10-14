<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetConnection;
use App\Models\AssetInterface;
use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AssetConnectionService
{
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

        return AssetConnection::query()->create([
            'interface_a_id' => $canonicalA->id,
            'interface_b_id' => $canonicalB->id,
            'connection_type_id' => $connectionType->id,
            'protocol' => $protocol,
            'label' => $label === '' ? null : $label,
            'notes' => $notes === '' ? null : $notes,
        ]);
    }

    /**
     * @param  array{
     *     connection_type_ids?: list<int>|null,
     *     location_id?: int|null,
     *     item_type_id?: int|null,
     *     search?: string|null
     * }  $filters
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>}
     */
    public function buildGraph(array $filters = []): array
    {
        $connections = $this->filteredConnections($filters)
            ->with([
                'connectionType',
                'interfaceA.asset.location',
                'interfaceA.asset.itemType',
                'interfaceB.asset.location',
                'interfaceB.asset.itemType',
            ])
            ->get();

        /** @var array<int, array<string, mixed>> $nodes */
        $nodes = [];
        $edges = [];

        foreach ($connections as $connection) {
            $assetA = $connection->interfaceA?->asset;
            $assetB = $connection->interfaceB?->asset;

            if (! $assetA || ! $assetB) {
                continue;
            }

            foreach ([$assetA, $assetB] as $asset) {
                $nodes[$asset->id] ??= [
                    'data' => [
                        'id' => 'asset-'.$asset->id,
                        'asset_id' => $asset->id,
                        'label' => $asset->name,
                        'location' => $asset->location?->name,
                        'item_type' => $asset->itemType?->name,
                        'url' => route('assets.show', [$asset, 'tab' => 'connections']),
                    ],
                ];
            }

            $type = $connection->connectionType;
            $edges[] = [
                'data' => [
                    'id' => 'connection-'.$connection->id,
                    'connection_id' => $connection->id,
                    'source' => 'asset-'.$assetA->id,
                    'target' => 'asset-'.$assetB->id,
                    'label' => $type?->name ?? 'Connection',
                    'color' => $type?->color ?? '#64748b',
                    'icon' => $type?->icon ?? 'generic',
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

        return [
            'nodes' => array_values($nodes),
            'edges' => $edges,
        ];
    }

    /**
     * @param  array{
     *     connection_type_ids?: list<int>|null,
     *     location_id?: int|null,
     *     item_type_id?: int|null,
     *     search?: string|null
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
            $assetQuery->where(function (Builder $q) use ($search): void {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('fmi_ast', 'like', $like)
                    ->orWhere('tp_barcode', 'like', $like)
                    ->orWhere('rig_tag', 'like', $like);
            });
        }
    }
}
