<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AuditLog;
use Illuminate\Support\Collection;

class DashboardStatsService
{
    /**
     * @return array{
     *     byStatus: list<array{value: string, label: string, count: int}>,
     *     byType: list<array{id: int, name: string, count: int}>,
     *     latestChanges: Collection<int, AuditLog>
     * }
     */
    public function stats(): array
    {
        $statusCounts = Asset::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = [];
        foreach (AssetStatus::cases() as $status) {
            $byStatus[] = [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => (int) ($statusCounts[$status->value] ?? 0),
            ];
        }

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

        $latestChanges = AuditLog::query()
            ->where('auditable_type', Asset::class)
            ->with(['user', 'auditable'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return [
            'byStatus' => $byStatus,
            'byType' => $byType,
            'latestChanges' => $latestChanges,
        ];
    }
}
