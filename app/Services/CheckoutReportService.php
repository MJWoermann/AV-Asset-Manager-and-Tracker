<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\ManufacturerModel;
use App\Support\AssetSearch;
use App\Support\ExportRowLimiter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CheckoutReportService
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }
     */
    public function filters(array $validated): array
    {
        $view = ($validated['view'] ?? 'models') === 'holders' ? 'holders' : 'models';
        $status = (string) ($validated['status'] ?? 'open');

        if (! in_array($status, ['open', 'returned', 'all'], true)) {
            $status = 'open';
        }

        return [
            'view' => $view,
            'purpose' => trim((string) ($validated['purpose'] ?? '')),
            'manufacturer_id' => $this->nullableInt($validated['manufacturer_id'] ?? null),
            'manufacturer_model_id' => $this->nullableInt($validated['manufacturer_model_id'] ?? null),
            'item_type_id' => $this->nullableInt($validated['item_type_id'] ?? null),
            'location_id' => $this->nullableInt($validated['location_id'] ?? null),
            'holder_user_id' => $this->nullableInt($validated['holder_user_id'] ?? null),
            'guest' => trim((string) ($validated['guest'] ?? '')),
            'checked_out_from' => (string) ($validated['checked_out_from'] ?? ''),
            'checked_out_to' => (string) ($validated['checked_out_to'] ?? ''),
            'overdue' => (bool) ($validated['overdue'] ?? false),
            'q' => trim((string) ($validated['q'] ?? '')),
            'status' => $status,
        ];
    }

    /**
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     * @return array<string, int|string>
     */
    public function queryParameters(array $filters, bool $includeView = true): array
    {
        $parameters = [
            'view' => $includeView && $filters['view'] !== 'models' ? $filters['view'] : null,
            'purpose' => $filters['purpose'] !== '' ? $filters['purpose'] : null,
            'manufacturer_id' => $filters['manufacturer_id'],
            'manufacturer_model_id' => $filters['manufacturer_model_id'],
            'item_type_id' => $filters['item_type_id'],
            'location_id' => $filters['location_id'],
            'holder_user_id' => $filters['holder_user_id'],
            'guest' => $filters['guest'] !== '' ? $filters['guest'] : null,
            'checked_out_from' => $filters['checked_out_from'] !== '' ? $filters['checked_out_from'] : null,
            'checked_out_to' => $filters['checked_out_to'] !== '' ? $filters['checked_out_to'] : null,
            'overdue' => $filters['overdue'] ? '1' : null,
            'q' => $filters['q'] !== '' ? $filters['q'] : null,
            'status' => $filters['status'] !== 'open' ? $filters['status'] : null,
        ];

        return array_filter(
            $parameters,
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     * @return list<array{manufacturer: string, model: string, available: int, checked_out: int, in_register: int}>
     */
    public function modelSummary(array $filters): array
    {
        $register = $this->countsByModel($this->registerAssets($filters));
        $available = $this->countsByModel(
            $this->registerAssets($filters)
                ->where('assets.status', AssetStatus::Available)
                ->whereDoesntHave('checkouts', fn (Builder $query) => $query->open())
        );
        $checkedOut = $this->countsByModel(
            $this->registerAssets($filters)->whereHas('checkouts', function (Builder $query) use ($filters): void {
                $query->open();
                $this->applyCheckoutFilters($query, $filters);
            })
        );

        $keys = collect(array_keys($register))
            ->merge(array_keys($available))
            ->merge(array_keys($checkedOut))
            ->unique()
            ->values();

        $modelIds = $keys
            ->reject(fn (string $key): bool => $key === 'none')
            ->map(fn (string $key): int => (int) $key)
            ->all();

        $models = ManufacturerModel::query()
            ->with('manufacturer:id,name')
            ->whereIn('id', $modelIds)
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($keys as $key) {
            $model = $key === 'none' ? null : $models->get((int) $key);

            $rows[] = [
                'manufacturer' => $key === 'none' ? 'Uncatalogued' : ($model?->manufacturer?->name ?? '—'),
                'model' => $key === 'none' ? '—' : ($model?->name ?? '—'),
                'available' => (int) ($available[$key] ?? 0),
                'checked_out' => (int) ($checkedOut[$key] ?? 0),
                'in_register' => (int) ($register[$key] ?? 0),
                'sort' => $key === 'none'
                    ? 'zzzz'
                    : mb_strtolower(($model?->manufacturer?->name ?? '').' '.($model?->name ?? '')),
            ];
        }

        usort($rows, fn (array $left, array $right): int => $left['sort'] <=> $right['sort']);

        return array_map(function (array $row): array {
            unset($row['sort']);

            return $row;
        }, $rows);
    }

    /**
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     * @return list<array{label: string, detail: string, checkouts: Collection<int, AssetCheckout>}>
     */
    public function holderGroups(array $filters, bool $forExport = false): array
    {
        $query = $this->holderQuery($filters);

        if ($forExport) {
            ExportRowLimiter::constrain($query, 'checkout report');
        }

        $checkouts = $query->get();
        $groups = [];

        foreach ($checkouts as $checkout) {
            if ($checkout->checked_out_to_user_id) {
                $key = 'user:'.$checkout->checked_out_to_user_id;
                $label = $checkout->checkedOutTo?->name ?? 'Unknown user';
                $detail = (string) ($checkout->checkedOutTo?->email ?? '');
            } else {
                $label = $checkout->assigneeLabel();
                $detail = collect([
                    $checkout->checked_out_to_email,
                    $checkout->checked_out_to_company,
                ])->filter()->implode(' · ');
                $key = 'guest:'.mb_strtolower(implode('|', [
                    trim((string) $checkout->checked_out_to_name),
                    trim((string) $checkout->checked_out_to_company),
                    trim((string) $checkout->checked_out_to_email),
                ]));
            }

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'label' => $label,
                    'detail' => $detail,
                    'checkouts' => collect(),
                ];
            }

            $groups[$key]['checkouts']->push($checkout);
        }

        uasort($groups, fn (array $left, array $right): int => strcasecmp($left['label'], $right['label']));

        return array_values($groups);
    }

    public function filtersActive(array $filters): bool
    {
        return $this->queryParameters($filters, false) !== [];
    }

    /**
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     * @return Builder<Asset>
     */
    protected function registerAssets(array $filters): Builder
    {
        $query = Asset::query();
        $this->applyCatalogueFilters($query, $filters, true);

        return $query;
    }

    /**
     * @param  Builder<Asset>  $query
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     */
    protected function applyCatalogueFilters(Builder $query, array $filters, bool $inRegister): void
    {
        $table = $query->getModel()->getTable();

        if ($inRegister) {
            $query->whereNotIn($table.'.status', [
                AssetStatus::Disposed,
                AssetStatus::Lost,
            ]);
        }

        if ($filters['manufacturer_id'] !== null) {
            $query->where($table.'.manufacturer_id', $filters['manufacturer_id']);
        }

        if ($filters['manufacturer_model_id'] !== null) {
            $query->where($table.'.manufacturer_model_id', $filters['manufacturer_model_id']);
        }

        if ($filters['item_type_id'] !== null) {
            $query->where($table.'.item_type_id', $filters['item_type_id']);
        }

        if ($filters['location_id'] !== null) {
            $query->where($table.'.location_id', $filters['location_id']);
        }

        if ($filters['q'] !== '') {
            AssetSearch::constrain($query, $filters['q']);
        }
    }

    /**
     * @param  Builder<AssetCheckout>  $query
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     */
    protected function applyCheckoutFilters(Builder $query, array $filters): void
    {
        if ($filters['purpose'] !== '') {
            $query->where('purpose', $filters['purpose']);
        }

        if ($filters['holder_user_id'] !== null) {
            $query->where('checked_out_to_user_id', $filters['holder_user_id']);
        }

        if ($filters['guest'] !== '') {
            $like = '%'.AssetSearch::escapeLike($filters['guest']).'%';
            $query->where(function (Builder $inner) use ($like): void {
                $inner->where('checked_out_to_name', 'like', $like)
                    ->orWhere('checked_out_to_company', 'like', $like)
                    ->orWhere('checked_out_to_email', 'like', $like);
            });
        }

        if ($filters['checked_out_from'] !== '') {
            $query->where('created_at', '>=', $filters['checked_out_from'].' 00:00:00');
        }

        if ($filters['checked_out_to'] !== '') {
            $query->where('created_at', '<=', $filters['checked_out_to'].' 23:59:59');
        }

        if ($filters['overdue']) {
            $query->whereNotNull('expected_return_at')
                ->where('expected_return_at', '<', now());
        }
    }

    /**
     * @param  Builder<Asset>  $query
     * @return array<string, int>
     */
    protected function countsByModel(Builder $query): array
    {
        $counts = [];
        $rows = $query
            ->selectRaw('assets.manufacturer_model_id as manufacturer_model_id, COUNT(*) as aggregate')
            ->groupBy('assets.manufacturer_model_id')
            ->toBase()
            ->get();

        foreach ($rows as $row) {
            $key = $row->manufacturer_model_id === null || $row->manufacturer_model_id === ''
                ? 'none'
                : (string) $row->manufacturer_model_id;
            $counts[$key] = (int) $row->aggregate;
        }

        return $counts;
    }

    /**
     * @param  array{
     *     view: string,
     *     purpose: string,
     *     manufacturer_id: int|null,
     *     manufacturer_model_id: int|null,
     *     item_type_id: int|null,
     *     location_id: int|null,
     *     holder_user_id: int|null,
     *     guest: string,
     *     checked_out_from: string,
     *     checked_out_to: string,
     *     overdue: bool,
     *     q: string,
     *     status: string
     * }  $filters
     * @return Builder<AssetCheckout>
     */
    protected function holderQuery(array $filters): Builder
    {
        $query = AssetCheckout::query()
            ->with([
                'asset.manufacturer',
                'asset.manufacturerModel',
                'asset.location',
                'checkedOutTo',
                'checkoutPurpose',
            ])
            ->whereHas('asset', function (Builder $asset) use ($filters): void {
                $this->applyCatalogueFilters($asset, $filters, false);
            });

        if ($filters['overdue']) {
            $query->open();
        } elseif ($filters['status'] === 'returned') {
            $query->whereNotNull('returned_at');
        } elseif ($filters['status'] !== 'all') {
            $query->open();
        }

        $this->applyCheckoutFilters($query, $filters);

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    protected function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
