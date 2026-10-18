<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AssetHistoryFilters
{
    /**
     * @param  Builder<AuditLog>|Relation  $query
     * @return Builder<AuditLog>|Relation
     */
    public static function apply(Builder|Relation $query, Request $request): Builder|Relation
    {
        $dateFrom = RequestDates::ymd($request, 'date_from');
        $dateTo = RequestDates::ymd($request, 'date_to');
        $action = trim($request->string('action')->toString());
        $userId = $request->string('user_id')->toString();

        if ($dateFrom !== null) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== null) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($userId === 'system') {
            $query->whereNull('user_id');
        } elseif ($userId !== '' && ctype_digit($userId)) {
            $query->where('user_id', (int) $userId);
        }

        return $query;
    }

    public static function hasActiveFilters(Request $request): bool
    {
        return RequestDates::ymd($request, 'date_from') !== null
            || RequestDates::ymd($request, 'date_to') !== null
            || trim($request->string('action')->toString()) !== ''
            || trim($request->string('user_id')->toString()) !== '';
    }

    /**
     * Distinct actions recorded for this asset, keyed for a select list.
     *
     * @return array<string, string>
     */
    public static function actionOptions(Asset $asset): array
    {
        return $asset->auditLogs()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->filter(fn (mixed $action): bool => is_string($action) && $action !== '')
            ->mapWithKeys(fn (string $action): array => [
                $action => self::actionLabel($action),
            ])
            ->all();
    }

    /**
     * Users who appear in this asset's history (plus System when present).
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public static function userOptions(Asset $asset): Collection
    {
        $userIds = $asset->auditLogs()
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

        if ($asset->auditLogs()->whereNull('user_id')->exists()) {
            $options->prepend([
                'value' => 'system',
                'label' => 'System',
            ]);
        }

        return $options->values();
    }

    public static function actionLabel(string $action): string
    {
        return ucfirst(str_replace('_', ' ', $action));
    }
}
