<?php

namespace App\Support;

use App\Enums\MaintenanceLogType;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AssetMaintenanceFilters
{
    /**
     * @param  Builder<AssetMaintenanceLog>|Relation  $query
     * @return Builder<AssetMaintenanceLog>|Relation
     */
    public static function apply(Builder|Relation $query, Request $request): Builder|Relation
    {
        $dateFrom = self::dateValue($request, 'date_from');
        $dateTo = self::dateValue($request, 'date_to');
        $type = trim($request->string('log_type')->toString());
        $userId = $request->string('user_id')->toString();

        if ($dateFrom !== null) {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate('occurred_at', '<=', $dateTo);
        }

        if ($type !== '' && MaintenanceLogType::tryFrom($type) !== null) {
            $query->where('type', $type);
        }

        if ($userId === 'unknown') {
            $query->whereNull('user_id');
        } elseif ($userId !== '' && ctype_digit($userId)) {
            $query->where('user_id', (int) $userId);
        }

        return $query;
    }

    public static function hasActiveFilters(Request $request): bool
    {
        $type = trim($request->string('log_type')->toString());

        return self::dateValue($request, 'date_from') !== null
            || self::dateValue($request, 'date_to') !== null
            || ($type !== '' && MaintenanceLogType::tryFrom($type) !== null)
            || trim($request->string('user_id')->toString()) !== '';
    }

    /**
     * Users who appear in this asset's maintenance history (plus Unknown when present).
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public static function userOptions(Asset $asset): Collection
    {
        $userIds = $asset->maintenanceLogs()
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $options = User::query()
            ->whereIn('id', $userIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ]);

        if ($asset->maintenanceLogs()->whereNull('user_id')->exists()) {
            $options->prepend([
                'value' => 'unknown',
                'label' => 'Unknown',
            ]);
        }

        return $options->values();
    }

    protected static function dateValue(Request $request, string $key): ?string
    {
        $value = trim($request->string($key)->toString());

        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
