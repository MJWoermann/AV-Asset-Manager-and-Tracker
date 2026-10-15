<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailActionStatus;
use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\InboundEmailAction;
use App\Models\User;
use App\Support\DashboardModules;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    /**
     * @return array{
     *     modules: list<array{id: string, label: string, enabled: bool}>,
     *     byStatus: list<array{value: string, label: string, count: int}>,
     *     byType: list<array{id: int, name: string, count: int}>,
     *     latestChanges: Collection<int, AuditLog>,
     *     pendingInboundEmails: int|null,
     *     warrantyExpiry: list<array{key: string, label: string, count: int, url: string}>,
     *     testTagExpiry: list<array{key: string, label: string, count: int, url: string}>
     * }
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
            $query = Asset::query()->whereNotNull($column);

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
