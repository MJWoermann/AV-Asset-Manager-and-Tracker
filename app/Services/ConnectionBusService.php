<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetInterface;
use App\Models\AuditLog;
use App\Models\ConnectionBus;
use App\Models\ConnectionType;
use App\Support\ScannableIdentifierFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ConnectionBusService
{
    /**
     * @param  array{name?: string|null, notes?: string|null}  $attributes
     */
    public function createBus(ConnectionType $connectionType, array $attributes = []): ConnectionBus
    {
        if (! $connectionType->allows_bus) {
            throw ValidationException::withMessages([
                'connection_bus_id' => 'This connection type does not allow device buses.',
            ]);
        }

        $name = isset($attributes['name']) ? trim((string) $attributes['name']) : '';
        if ($name === '') {
            throw ValidationException::withMessages([
                'new_bus_name' => 'A bus name is required.',
            ]);
        }

        $notes = isset($attributes['notes']) ? trim((string) $attributes['notes']) : null;

        return ConnectionBus::query()->create([
            'connection_type_id' => $connectionType->id,
            'name' => $name,
            'notes' => $notes === '' ? null : $notes,
        ]);
    }

    /**
     * Join an interface to an existing bus (or create a named bus first).
     *
     * @param  array{connection_bus_id?: int|null, new_bus_name?: string|null, notes?: string|null}  $attributes
     */
    public function join(AssetInterface $interface, array $attributes = []): ConnectionBus
    {
        $interface->loadMissing(['connectionType', 'asset', 'buses']);

        $type = $interface->connectionType;
        if (! $type || ! $type->allows_bus) {
            throw ValidationException::withMessages([
                'local_interface_id' => 'The selected interface type does not allow device buses.',
            ]);
        }

        if ($interface->currentBus() !== null) {
            throw ValidationException::withMessages([
                'local_interface_id' => 'This interface is already on a device bus. Leave it before joining another.',
            ]);
        }

        $busId = isset($attributes['connection_bus_id']) && $attributes['connection_bus_id'] !== ''
            ? (int) $attributes['connection_bus_id']
            : null;
        $newName = isset($attributes['new_bus_name']) ? trim((string) $attributes['new_bus_name']) : '';

        if ($busId) {
            $bus = ConnectionBus::query()->with('connectionType')->findOrFail($busId);
            if ((int) $bus->connection_type_id !== (int) $type->id) {
                throw ValidationException::withMessages([
                    'connection_bus_id' => 'The bus must use the same connection type as the interface.',
                ]);
            }
        } elseif ($newName !== '') {
            $bus = $this->createBus($type, [
                'name' => $newName,
                'notes' => $attributes['notes'] ?? null,
            ]);
        } else {
            throw ValidationException::withMessages([
                'connection_bus_id' => 'Select an existing bus or enter a new bus name.',
            ]);
        }

        $bus->interfaces()->attach($interface->id);

        $this->auditBusMembership($interface, $bus, 'bus_joined');

        return $bus->fresh(['connectionType', 'interfaces.asset']);
    }

    public function leave(AssetInterface $interface): void
    {
        $interface->loadMissing(['buses.connectionType', 'asset']);

        $bus = $interface->currentBus();
        if (! $bus) {
            throw ValidationException::withMessages([
                'local_interface_id' => 'This interface is not on a device bus.',
            ]);
        }

        $this->auditBusMembership($interface, $bus, 'bus_left');

        $bus->interfaces()->detach($interface->id);
    }

    /**
     * @return Collection<int, array{bus: ConnectionBus, local_interface: AssetInterface, members: Collection<int, AssetInterface>}>
     */
    public function membershipsForAsset(Asset $asset): Collection
    {
        $asset->loadMissing([
            'interfaces.connectionType',
            'interfaces.buses.connectionType',
            'interfaces.buses.interfaces.asset.itemType',
            'interfaces.buses.interfaces.asset.location',
        ]);

        $rows = collect();

        foreach ($asset->interfaces as $interface) {
            $bus = $interface->currentBus();
            if (! $bus) {
                continue;
            }

            $members = $bus->interfaces
                ->filter(fn (AssetInterface $member): bool => (int) $member->id !== (int) $interface->id)
                ->values();

            $rows->push([
                'bus' => $bus,
                'local_interface' => $interface,
                'members' => $members,
            ]);
        }

        return $rows;
    }

    /**
     * Buses available for a connection type (for join UI).
     *
     * @return Collection<int, ConnectionBus>
     */
    public function busesForType(ConnectionType $type): Collection
    {
        return ConnectionBus::query()
            ->where('connection_type_id', $type->id)
            ->withCount('interfaces')
            ->orderBy('name')
            ->orderBy('id')
            ->get();
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
    public function filteredBuses(array $filters = []): Builder
    {
        $query = ConnectionBus::query()->with([
            'connectionType',
            'interfaces.asset.location.parent.parent.parent.parent',
            'interfaces.asset.itemType',
        ]);

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
            $query->whereHas('interfaces.asset', function (Builder $assetQuery) use ($locationId, $itemTypeId, $search): void {
                if ($locationId) {
                    $assetQuery->where('location_id', $locationId);
                }

                if ($itemTypeId) {
                    $assetQuery->where('item_type_id', $itemTypeId);
                }

                if ($search !== '') {
                    $assetQuery->search($search);
                }
            });
        }

        return $query->orderBy('id');
    }

    /**
     * Expand connected component membership through shared buses.
     *
     * @param  Collection<int, ConnectionBus>  $buses
     * @param  array<int, true>  $visited
     * @return array<int, true>
     */
    public function expandConnectedAssetIds(Collection $buses, array $visited): array
    {
        /** @var array<int, list<int>> $adjacency */
        $adjacency = [];

        foreach ($buses as $bus) {
            $memberAssetIds = $bus->interfaces
                ->pluck('asset_id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values()
                ->all();

            foreach ($memberAssetIds as $assetId) {
                foreach ($memberAssetIds as $peerId) {
                    if ($assetId === $peerId) {
                        continue;
                    }
                    $adjacency[$assetId][] = $peerId;
                }
            }
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

    protected function auditBusMembership(AssetInterface $interface, ConnectionBus $bus, string $action): void
    {
        $interface->loadMissing('asset');
        $bus->loadMissing('connectionType');

        $asset = $interface->asset;
        if (! $asset) {
            return;
        }

        $payload = [
            'connection_bus_id' => $bus->id,
            'bus_name' => $bus->name,
            'connection_type' => $bus->connectionType?->name,
            'local_interface' => $interface->name,
        ];

        $isJoined = $action === 'bus_joined';

        AuditLog::create([
            'user_id' => Auth::id(),
            'auditable_type' => Asset::class,
            'auditable_id' => $asset->id,
            'action' => $action,
            'old_values' => $isJoined ? null : $payload,
            'new_values' => $isJoined ? $payload : null,
            'created_at' => now(),
        ]);
    }
}
