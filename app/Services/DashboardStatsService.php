<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailActionStatus;
use App\Enums\RepairStatus;
use App\Enums\RigEventStatus;
use App\Enums\SparePartSeverity;
use App\Enums\SparePartStockStatus;
use App\Enums\StockMovementType;
use App\Models\Asset;
use App\Models\AssetInterface;
use App\Models\AssetList;
use App\Models\AuditLog;
use App\Models\InboundEmailAction;
use App\Models\Repair;
use App\Models\RigEvent;
use App\Models\ScanSession;
use App\Models\SparePart;
use App\Models\SparePartMovement;
use App\Models\StockMovement;
use App\Models\UnknownScan;
use App\Models\User;
use App\Support\DashboardModules;
use App\Support\DashboardQuickLinks;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function stats(?User $user = null): array
    {
        $available = DashboardModules::availableFor($user);
        $stored = is_array(($user?->preferences ?? [])[DashboardModules::PREFERENCE_KEY] ?? null)
            ? ($user->preferences)[DashboardModules::PREFERENCE_KEY]
            : null;
        $modules = DashboardModules::resolve($stored, $available);
        $enabled = DashboardModules::enabledIds($modules);

        $byStatus = [];
        if (in_array(DashboardModules::ASSETS_BY_STATUS, $enabled, true)) {
            $statusCounts = Asset::query()
                ->whereNull('parent_id')
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            foreach (AssetStatus::cases() as $status) {
                $byStatus[] = [
                    'value' => $status->value,
                    'label' => $status->label(),
                    'count' => (int) ($statusCounts[$status->value] ?? 0),
                ];
            }
        }

        $byType = [];
        if (in_array(DashboardModules::ASSETS_BY_TYPE, $enabled, true)) {
            $byType = Asset::query()
                ->whereNull('parent_id')
                ->join('item_types', 'item_types.id', '=', 'assets.item_type_id')
                ->select('item_types.id', 'item_types.name')
                ->selectRaw('count(*) as aggregate')
                ->groupBy('item_types.id', 'item_types.name')
                ->orderByDesc('aggregate')
                ->orderBy('item_types.name')
                ->get()
                ->map(fn ($row): array => [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'count' => (int) $row->aggregate,
                ])
                ->all();
        }

        $latestChanges = collect();
        if (in_array(DashboardModules::LATEST_CHANGES, $enabled, true)) {
            $latestChanges = AuditLog::query()
                ->where('auditable_type', Asset::class)
                ->with(['user', 'auditable'])
                ->latest('created_at')
                ->limit(10)
                ->get();
        }

        return [
            'modules' => $modules,
            'quickLinks' => in_array(DashboardModules::QUICK_LINKS, $enabled, true)
                ? DashboardQuickLinks::forUser($user)
                : [],
            'byStatus' => $byStatus,
            'byType' => $byType,
            'latestChanges' => $latestChanges,
            'pendingInboundEmails' => in_array(DashboardModules::INBOUND_EMAILS, $enabled, true)
                ? $this->pendingInboundEmailCount($user)
                : null,
            'warrantyExpiry' => in_array(DashboardModules::WARRANTY_EXPIRY, $enabled, true)
                ? $this->expiryBuckets('warranty_expiry')
                : [],
            'testTagExpiry' => in_array(DashboardModules::TEST_TAG_EXPIRY, $enabled, true)
                ? $this->expiryBuckets('test_tag_expiry')
                : [],
            'maintenanceActivities' => in_array(DashboardModules::MAINTENANCE_ACTIVITIES, $enabled, true)
                ? $this->maintenanceActivities()
                : ['buckets' => [], 'recent' => collect()],
            'sparesStats' => in_array(DashboardModules::SPARES_STATS, $enabled, true)
                ? $this->sparesStats()
                : [],
            'stocktakePulse' => in_array(DashboardModules::STOCKTAKE_PULSE, $enabled, true)
                ? $this->stocktakePulse($user)
                : ['buckets' => [], 'resumeUrl' => null],
            'assetsByLocation' => in_array(DashboardModules::ASSETS_BY_LOCATION, $enabled, true)
                ? $this->assetsByLocation()
                : [],
            'dataQuality' => in_array(DashboardModules::DATA_QUALITY, $enabled, true)
                ? $this->dataQuality()
                : [],
            'listActivity' => in_array(DashboardModules::LIST_ACTIVITY, $enabled, true)
                ? $this->listActivity()
                : ['counts' => [], 'recent' => []],
            'myRepairs' => in_array(DashboardModules::MY_REPAIRS, $enabled, true)
                ? $this->myRepairs($user)
                : ['count' => 0, 'recent' => collect()],
            'assetsNeedingAttention' => in_array(DashboardModules::ASSETS_NEEDING_ATTENTION, $enabled, true)
                ? $this->assetsNeedingAttention()
                : [],
            'spareMovementsRecent' => in_array(DashboardModules::SPARE_MOVEMENTS_RECENT, $enabled, true)
                ? $this->spareMovementsRecent()
                : ['usage7Days' => 0, 'recent' => collect()],
            'consumablesStats' => in_array(DashboardModules::CONSUMABLES_STATS, $enabled, true)
                ? $this->consumablesStats()
                : ['buckets' => [], 'recentRestocks' => collect()],
            'connectionCoverage' => in_array(DashboardModules::CONNECTION_COVERAGE, $enabled, true)
                ? $this->connectionCoverage()
                : [],
            'riggingUpcoming' => in_array(DashboardModules::RIGGING_UPCOMING, $enabled, true)
                ? $this->riggingUpcoming()
                : [],
        ];
    }

    /**
     * Exclusive buckets: overdue, 1–30, 31–60, and 61–90 days.
     *
     * @return list<array{key: string, label: string, count: int, url: string}>
     */
    public function expiryBuckets(string $column): array
    {
        $today = Carbon::today();
        $day30 = $today->copy()->addDays(30);
        $day31 = $today->copy()->addDays(31);
        $day60 = $today->copy()->addDays(60);
        $day61 = $today->copy()->addDays(61);
        $day90 = $today->copy()->addDays(90);
        $yesterday = $today->copy()->subDay();

        $fromParam = $column.'_from';
        $toParam = $column.'_to';

        $buckets = [
            [
                'key' => 'overdue',
                'label' => 'Overdue',
                'from' => null,
                'to' => $yesterday,
            ],
            [
                'key' => '30',
                'label' => 'Within 30 days',
                'from' => $today,
                'to' => $day30,
            ],
            [
                'key' => '60',
                'label' => '31–60 days',
                'from' => $day31,
                'to' => $day60,
            ],
            [
                'key' => '90',
                'label' => '61–90 days',
                'from' => $day61,
                'to' => $day90,
            ],
        ];

        return array_map(function (array $bucket) use ($column, $fromParam, $toParam): array {
            $query = Asset::query()->whereNull('parent_id')->whereNotNull($column);

            if ($bucket['from'] instanceof CarbonInterface) {
                $query->whereDate($column, '>=', $bucket['from']->toDateString());
            }

            if ($bucket['to'] instanceof CarbonInterface) {
                $query->whereDate($column, '<=', $bucket['to']->toDateString());
            }

            $params = [];
            if ($bucket['from'] instanceof CarbonInterface) {
                $params[$fromParam] = $bucket['from']->toDateString();
            }
            if ($bucket['to'] instanceof CarbonInterface) {
                $params[$toParam] = $bucket['to']->toDateString();
            }

            return [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'count' => (int) $query->count(),
                'url' => route('assets.index', $params),
            ];
        }, $buckets);
    }

    /**
     * @return array{
     *     buckets: list<array{key: string, label: string, count: int, url: string}>,
     *     recent: Collection<int, Repair>
     * }
     */
    public function maintenanceActivities(): array
    {
        $repairCounts = Repair::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $completedSince = Carbon::now()->subDays(30);

        $buckets = [
            [
                'key' => 'assets_maintenance',
                'label' => 'Assets in maintenance',
                'count' => Asset::query()->whereNull('parent_id')->where('status', AssetStatus::Maintenance)->count(),
                'url' => route('assets.index', ['status' => AssetStatus::Maintenance->value]),
            ],
            [
                'key' => 'open',
                'label' => 'Open repairs',
                'count' => (int) ($repairCounts[RepairStatus::Open->value] ?? 0),
                'url' => route('repairs.index', ['status' => RepairStatus::Open->value]),
            ],
            [
                'key' => 'in_progress',
                'label' => 'In progress',
                'count' => (int) ($repairCounts[RepairStatus::InProgress->value] ?? 0),
                'url' => route('repairs.index', ['status' => RepairStatus::InProgress->value]),
            ],
            [
                'key' => 'completed_30',
                'label' => 'Completed (30 days)',
                'count' => Repair::query()
                    ->where('status', RepairStatus::Completed)
                    ->where('completed_at', '>=', $completedSince)
                    ->count(),
                'url' => route('repairs.index', ['status' => RepairStatus::Completed->value]),
            ],
        ];

        $recent = Repair::query()
            ->active()
            ->with(['asset:id,name'])
            ->latest('started_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'buckets' => $buckets,
            'recent' => $recent,
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, url: string|null}>
     */
    public function sparesStats(): array
    {
        $active = SparePart::query()->activeLifecycle();

        $activeCount = (clone $active)->count();
        $belowMin = (clone $active)
            ->whereNotNull('min_holding')
            ->whereColumn('quantity_on_hand', '<', 'min_holding')
            ->count();
        $onOrder = (clone $active)
            ->where('stock_status', SparePartStockStatus::OnOrder)
            ->count();
        $quarantine = (clone $active)
            ->where('stock_status', SparePartStockStatus::Quarantine)
            ->count();
        $critical = (clone $active)
            ->where('severity', SparePartSeverity::Critical)
            ->count();
        $unitsOnHand = (int) ((clone $active)->sum('quantity_on_hand'));

        return [
            [
                'key' => 'active',
                'label' => 'Active SKUs',
                'count' => $activeCount,
                'url' => route('spares.index'),
            ],
            [
                'key' => 'below_min',
                'label' => 'Below minimum',
                'count' => $belowMin,
                'url' => route('spares.index', ['below_min' => 1]),
            ],
            [
                'key' => 'on_order',
                'label' => 'On order',
                'count' => $onOrder,
                'url' => route('spares.index', ['stock_status' => SparePartStockStatus::OnOrder->value]),
            ],
            [
                'key' => 'quarantine',
                'label' => 'Quarantine',
                'count' => $quarantine,
                'url' => route('spares.index', ['stock_status' => SparePartStockStatus::Quarantine->value]),
            ],
            [
                'key' => 'critical',
                'label' => 'Critical severity',
                'count' => $critical,
                'url' => route('spares.index', ['severity' => SparePartSeverity::Critical->value]),
            ],
            [
                'key' => 'units_on_hand',
                'label' => 'Units on hand',
                'count' => $unitsOnHand,
                'url' => null,
            ],
        ];
    }

    /**
     * @return array{buckets: list<array{key: string, label: string, count: int, url: string}>, resumeUrl: string|null}
     */
    public function stocktakePulse(?User $user): array
    {
        $recentSince = Carbon::now()->subDays(7);

        $activeCount = ScanSession::query()->where('status', 'active')->count();
        $recentClosed = ScanSession::query()
            ->where('status', 'closed')
            ->where('updated_at', '>=', $recentSince)
            ->count();
        $openUnknowns = UnknownScan::query()->unresolved()->count();

        $resumeUrl = null;
        if ($user !== null) {
            $hasActive = ScanSession::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists();
            if ($hasActive) {
                $resumeUrl = route('scan.index');
            }
        }

        return [
            'buckets' => [
                [
                    'key' => 'active_sessions',
                    'label' => 'Active sessions',
                    'count' => $activeCount,
                    'url' => route('scan.index'),
                ],
                [
                    'key' => 'recently_closed',
                    'label' => 'Closed (7 days)',
                    'count' => $recentClosed,
                    'url' => route('scan.index'),
                ],
                [
                    'key' => 'open_unknowns',
                    'label' => 'Open unknown scans',
                    'count' => $openUnknowns,
                    'url' => route('reports.unknown-scans'),
                ],
            ],
            'resumeUrl' => $resumeUrl,
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, url: string}>
     */
    public function assetsByLocation(): array
    {
        $rows = Asset::query()
            ->whereNull('assets.parent_id')
            ->leftJoin('locations', 'locations.id', '=', 'assets.location_id')
            ->selectRaw('assets.location_id, COALESCE(locations.name, ?) as location_name, count(*) as aggregate', ['Unassigned'])
            ->groupBy('assets.location_id', 'locations.name')
            ->orderByDesc('aggregate')
            ->orderBy('location_name')
            ->get();

        $top = $rows->take(8);
        $otherCount = (int) $rows->slice(8)->sum('aggregate');

        $result = $top->map(function ($row): array {
            $locationId = $row->location_id;

            return [
                'key' => $locationId === null ? 'unassigned' : (string) $locationId,
                'label' => (string) $row->location_name,
                'count' => (int) $row->aggregate,
                'url' => $locationId === null
                    ? route('assets.index', ['missing_location' => 1])
                    : route('assets.index', ['location_id' => (int) $locationId]),
            ];
        })->values()->all();

        if ($otherCount > 0) {
            $result[] = [
                'key' => 'other',
                'label' => 'Other locations',
                'count' => $otherCount,
                'url' => route('assets.index'),
            ];
        }

        return $result;
    }

    /**
     * @return list<array{key: string, label: string, count: int, url: string}>
     */
    public function dataQuality(): array
    {
        $base = Asset::query()->whereNull('parent_id');

        $missingIdentifiers = (clone $base)
            ->where(function (Builder $query): void {
                $query->whereNull('tp_barcode')->orWhere('tp_barcode', '');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('fmi_ast')->orWhere('fmi_ast', '');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('serial_number')->orWhere('serial_number', '');
            })
            ->count();

        $missingLocation = (clone $base)->whereNull('location_id')->count();

        return [
            [
                'key' => 'missing_identifiers',
                'label' => 'Missing scan identifiers',
                'count' => $missingIdentifiers,
                'url' => route('assets.index', ['missing_identifiers' => 1]),
            ],
            [
                'key' => 'missing_location',
                'label' => 'Missing location',
                'count' => $missingLocation,
                'url' => route('assets.index', ['missing_location' => 1]),
            ],
        ];
    }

    /**
     * @return array{
     *     counts: list<array{key: string, label: string, count: int, url: string}>,
     *     recent: list<array{id: int, name: string, type: string, items_count: int, last_activity: string|null, url: string}>
     * }
     */
    public function listActivity(): array
    {
        $inventoryCount = AssetList::query()->where('type', 'inventory')->count();
        $eventCount = AssetList::query()->where('type', 'event')->count();

        $recentLists = AssetList::query()
            ->withCount('items')
            ->withMax('items', 'created_at')
            ->orderByDesc('items_max_created_at')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn (AssetList $list): array => [
                'id' => (int) $list->id,
                'name' => (string) $list->name,
                'type' => (string) $list->type,
                'items_count' => (int) ($list->items_count ?? 0),
                'last_activity' => $list->lastActivityAt()?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'url' => route('lists.index'),
            ])
            ->all();

        return [
            'counts' => [
                [
                    'key' => 'inventory',
                    'label' => 'Inventory lists',
                    'count' => $inventoryCount,
                    'url' => route('lists.index'),
                ],
                [
                    'key' => 'event',
                    'label' => 'Event lists',
                    'count' => $eventCount,
                    'url' => route('lists.index'),
                ],
            ],
            'recent' => $recentLists,
        ];
    }

    /**
     * @return array{count: int, recent: Collection<int, Repair>}
     */
    public function myRepairs(?User $user): array
    {
        if ($user === null) {
            return ['count' => 0, 'recent' => collect()];
        }

        $query = Repair::query()
            ->active()
            ->where('assignee_user_id', $user->id);

        $recent = (clone $query)
            ->with(['asset:id,name'])
            ->latest('started_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'count' => (int) (clone $query)->count(),
            'recent' => $recent,
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, url: string}>
     */
    public function assetsNeedingAttention(): array
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $base = Asset::query()->whereNull('parent_id');

        return [
            [
                'key' => 'overdue_test_tag',
                'label' => 'Overdue test & tag',
                'count' => (clone $base)
                    ->whereNotNull('test_tag_expiry')
                    ->whereDate('test_tag_expiry', '<=', $yesterday)
                    ->count(),
                'url' => route('assets.index', ['test_tag_expiry_to' => $yesterday]),
            ],
            [
                'key' => 'overdue_warranty',
                'label' => 'Overdue warranty',
                'count' => (clone $base)
                    ->whereNotNull('warranty_expiry')
                    ->whereDate('warranty_expiry', '<=', $yesterday)
                    ->count(),
                'url' => route('assets.index', ['warranty_expiry_to' => $yesterday]),
            ],
            [
                'key' => 'maintenance_status',
                'label' => 'In maintenance',
                'count' => (clone $base)->where('status', AssetStatus::Maintenance)->count(),
                'url' => route('assets.index', ['status' => AssetStatus::Maintenance->value]),
            ],
            [
                'key' => 'damaged_status',
                'label' => 'Damaged',
                'count' => (clone $base)->where('status', AssetStatus::Damaged)->count(),
                'url' => route('assets.index', ['status' => AssetStatus::Damaged->value]),
            ],
        ];
    }

    /**
     * @return array{usage7Days: int, recent: Collection<int, SparePartMovement>}
     */
    public function spareMovementsRecent(): array
    {
        $since = Carbon::now()->subDays(7);

        $usage7Days = SparePartMovement::query()
            ->where('recorded_at', '>=', $since)
            ->where('quantity_delta', '<', 0)
            ->count();

        $recent = SparePartMovement::query()
            ->with(['sparePart:id,component', 'againstAsset:id,name'])
            ->latest('recorded_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'usage7Days' => $usage7Days,
            'recent' => $recent,
        ];
    }

    /**
     * @return array{
     *     buckets: list<array{key: string, label: string, count: int, url: string|null}>,
     *     recentRestocks: Collection<int, StockMovement>
     * }
     */
    public function consumablesStats(): array
    {
        $tracked = $this->stockTrackedAssetsQuery();
        $since = Carbon::now()->subDays(30);

        $total = (clone $tracked)->count();
        $belowMin = (clone $tracked)
            ->whereNotNull('min_stock_level')
            ->whereColumn('quantity', '<', 'min_stock_level')
            ->count();
        $atMax = (clone $tracked)
            ->whereNotNull('max_stock_level')
            ->whereColumn('quantity', '>=', 'max_stock_level')
            ->count();
        $zeroOnHand = (clone $tracked)->where('quantity', '<=', 0)->count();

        $restocks30 = StockMovement::query()
            ->where('type', StockMovementType::Restock)
            ->where('recorded_at', '>=', $since)
            ->whereHas('asset.itemType', fn (Builder $query) => $query->where('supports_stock_tracking', true))
            ->count();

        $breakages30 = StockMovement::query()
            ->where('type', StockMovementType::Breakage)
            ->where('recorded_at', '>=', $since)
            ->whereHas('asset.itemType', fn (Builder $query) => $query->where('supports_stock_tracking', true))
            ->count();

        $recentRestocks = StockMovement::query()
            ->where('type', StockMovementType::Restock)
            ->with(['asset:id,name'])
            ->latest('recorded_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return [
            'buckets' => [
                [
                    'key' => 'tracked_total',
                    'label' => 'Stock-tracked assets',
                    'count' => $total,
                    'url' => route('assets.index', ['stock_tracked' => 1]),
                ],
                [
                    'key' => 'below_min',
                    'label' => 'Below minimum',
                    'count' => $belowMin,
                    'url' => route('assets.index', ['stock_tracked' => 1, 'stock_below_min' => 1]),
                ],
                [
                    'key' => 'at_max',
                    'label' => 'At / above maximum',
                    'count' => $atMax,
                    'url' => route('assets.index', ['stock_tracked' => 1, 'stock_at_max' => 1]),
                ],
                [
                    'key' => 'zero_on_hand',
                    'label' => 'Zero on hand',
                    'count' => $zeroOnHand,
                    'url' => route('assets.index', ['stock_tracked' => 1, 'stock_zero' => 1]),
                ],
                [
                    'key' => 'restocks_30',
                    'label' => 'Restocks (30 days)',
                    'count' => $restocks30,
                    'url' => route('assets.index', ['stock_tracked' => 1]),
                ],
                [
                    'key' => 'breakages_30',
                    'label' => 'Breakages (30 days)',
                    'count' => $breakages30,
                    'url' => route('assets.index', ['stock_tracked' => 1]),
                ],
            ],
            'recentRestocks' => $recentRestocks,
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, url: string}>
     */
    public function connectionCoverage(): array
    {
        $unconnectedInterfaces = AssetInterface::query()
            ->whereHas('asset')
            ->whereDoesntHave('connectionsAsA')
            ->whereDoesntHave('connectionsAsB')
            ->count();

        $assetsMissingPorts = Asset::query()
            ->whereNull('parent_id')
            ->where(function (Builder $query): void {
                $query->whereHas('itemType.interfaceTemplates')
                    ->orWhereHas('manufacturerModel.interfaceTemplates');
            })
            ->whereDoesntHave('interfaces')
            ->count();

        return [
            [
                'key' => 'unconnected_interfaces',
                'label' => 'Interfaces without connections',
                'count' => $unconnectedInterfaces,
                'url' => route('reports.connection-map'),
            ],
            [
                'key' => 'assets_missing_ports',
                'label' => 'Assets missing template ports',
                'count' => $assetsMissingPorts,
                'url' => route('reports.connection-map'),
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, url: string}>
     */
    public function riggingUpcoming(): array
    {
        $openStatuses = [RigEventStatus::Draft->value, RigEventStatus::InProgress->value];

        $openCount = RigEvent::query()
            ->whereIn('status', $openStatuses)
            ->count();

        $unsignedCount = RigEvent::query()
            ->whereIn('status', $openStatuses)
            ->whereNull('structure_signed_at')
            ->count();

        return [
            [
                'key' => 'open_events',
                'label' => 'Open rig events',
                'count' => $openCount,
                'url' => route('rigging.events.index'),
            ],
            [
                'key' => 'unsigned_events',
                'label' => 'Awaiting structure sign-off',
                'count' => $unsignedCount,
                'url' => route('rigging.events.index'),
            ],
        ];
    }

    /**
     * @return Builder<Asset>
     */
    private function stockTrackedAssetsQuery(): Builder
    {
        return Asset::query()
            ->whereNull('parent_id')
            ->whereHas('itemType', fn (Builder $query) => $query->where('supports_stock_tracking', true));
    }

    private function pendingInboundEmailCount(?User $user): ?int
    {
        if ($user === null
            || ! $user->hasFeature('email_integration')
            || ! $user->hasAnyRole(['admin', 'inventory_manager'])) {
            return null;
        }

        return InboundEmailAction::query()
            ->where('status', InboundEmailActionStatus::Pending)
            ->count();
    }
}
