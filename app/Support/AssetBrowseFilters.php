<?php

namespace App\Support;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

// TextSearch used for multi-token column filters.

class AssetBrowseFilters
{
    /**
     * Text columns that accept a contains filter.
     *
     * @var list<string>
     */
    public const TEXT_COLUMNS = [
        'name',
        'tp_barcode',
        'fmi_ast',
        'rig_tag',
        'serial_number',
        'ip_address',
        'mac_address',
    ];

    /**
     * Map table column keys to request query parameter names.
     *
     * @return array<string, string>
     */
    public static function columnParams(): array
    {
        return [
            'name' => 'name',
            'item_type' => 'item_type_id',
            'status' => 'status',
            'tp_barcode' => 'tp_barcode',
            'fmi_ast' => 'fmi_ast',
            'rig_tag' => 'rig_tag',
            'serial_number' => 'serial_number',
            'location' => 'location_id',
            'manufacturer' => 'manufacturer_id',
            'model' => 'manufacturer_model_id',
            'ip_address' => 'ip_address',
            'mac_address' => 'mac_address',
            'quantity' => 'quantity',
            'test_tag_expiry' => 'test_tag_expiry',
            'parent' => 'parent',
        ];
    }

    public static function term(Request $request): ?string
    {
        $term = $request->string('q')->toString();

        return $term !== '' ? $term : null;
    }

    /**
     * Whether a column-scoped filter may be applied for the current visible columns.
     *
     * @param  list<string>|null  $selectedColumns  null means all column filters are allowed
     */
    public static function columnFilterAllowed(?array $selectedColumns, string $column): bool
    {
        if ($selectedColumns === null) {
            return true;
        }

        return in_array($column, $selectedColumns, true);
    }

    /**
     * Apply asset-level browse filters to an Asset query builder.
     *
     * Column-scoped filters are ignored when their column is not in `$selectedColumns`.
     * Global search, dashboard boolean flags, and expiry date ranges still apply.
     *
     * @param  Builder<Asset>  $query
     * @param  list<string>|null  $selectedColumns
     * @return Builder<Asset>
     */
    public static function applyToAssetQuery(
        Builder $query,
        Request $request,
        bool $includeQuantity = true,
        ?array $selectedColumns = null,
    ): Builder {
        $term = self::term($request);
        $allows = fn (string $column): bool => self::columnFilterAllowed($selectedColumns, $column);

        $query->search($term);

        $status = $request->string('status')->toString();
        if ($allows('status') && $status !== '' && AssetStatus::tryFrom($status) !== null) {
            $query->where('status', $status);
        }

        $locationId = $request->integer('location_id') ?: null;
        if ($allows('location') && $locationId) {
            $query->whereIn('location_id', Location::selfAndDescendantIds($locationId));
        }

        $itemTypeId = $request->integer('item_type_id') ?: null;
        if ($allows('item_type') && $itemTypeId) {
            $query->where('item_type_id', $itemTypeId);
        }

        $manufacturerId = $request->integer('manufacturer_id') ?: null;
        if ($allows('manufacturer') && $manufacturerId) {
            $query->where('manufacturer_id', $manufacturerId);
        }

        $manufacturerModelId = $request->integer('manufacturer_model_id') ?: null;
        if ($allows('model') && $manufacturerModelId) {
            $query->where('manufacturer_model_id', $manufacturerModelId);
        }

        if ($request->boolean('missing_identifiers')) {
            $query->where(function (Builder $builder): void {
                $builder->whereNull('tp_barcode')->orWhere('tp_barcode', '');
            })->where(function (Builder $builder): void {
                $builder->whereNull('fmi_ast')->orWhere('fmi_ast', '');
            })->where(function (Builder $builder): void {
                $builder->whereNull('serial_number')->orWhere('serial_number', '');
            });
        }

        if ($request->boolean('missing_location')) {
            $query->whereNull('location_id');
        }

        if ($request->boolean('stock_tracked')) {
            $query->whereHas('itemType', fn (Builder $builder) => $builder->where('supports_stock_tracking', true));
        }

        if ($request->boolean('stock_below_min')) {
            $query->whereHas('itemType', fn (Builder $builder) => $builder->where('supports_stock_tracking', true))
                ->whereNotNull('min_stock_level')
                ->whereColumn('quantity', '<', 'min_stock_level');
        }

        if ($request->boolean('stock_at_max')) {
            $query->whereHas('itemType', fn (Builder $builder) => $builder->where('supports_stock_tracking', true))
                ->whereNotNull('max_stock_level')
                ->whereColumn('quantity', '>=', 'max_stock_level');
        }

        if ($request->boolean('stock_zero')) {
            $query->whereHas('itemType', fn (Builder $builder) => $builder->where('supports_stock_tracking', true))
                ->where('quantity', '<=', 0);
        }

        foreach (self::TEXT_COLUMNS as $column) {
            if (! $allows($column)) {
                continue;
            }

            $value = trim($request->string($column)->toString());

            if ($value === '') {
                continue;
            }

            if ($column === 'mac_address') {
                TextSearch::constrain($query, $value, function (Builder $group, string $like, string $token): void {
                    MacAddress::orWhereMatches($group, $token, 'mac_address');
                });

                continue;
            }

            TextSearch::constrainColumn($query, $value, $column);
        }

        if ($allows('parent')) {
            $parent = trim($request->string('parent')->toString());
            if ($parent !== '') {
                TextSearch::constrain($query, $parent, function (Builder $group, string $like): void {
                    $group->whereHas(
                        'parent',
                        fn (Builder $builder) => $builder->whereLike('name', $like)
                    );
                });
            }
        }

        if ($includeQuantity && $allows('quantity')) {
            $quantity = $request->input('quantity');
            if ($quantity !== null && $quantity !== '' && is_numeric($quantity)) {
                $query->where('quantity', (int) $quantity);
            }
        }

        if ($allows('test_tag_expiry')) {
            $testTagExpiry = $request->string('test_tag_expiry')->toString();
            if ($testTagExpiry !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $testTagExpiry) === 1) {
                $query->whereDate('test_tag_expiry', $testTagExpiry);
            }
        }

        // Dashboard deep-link ranges remain independent of visible table columns.
        self::applyDateRangeFilter($query, 'test_tag_expiry', $request);
        self::applyDateRangeFilter($query, 'warranty_expiry', $request);

        return $query;
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public static function applyDateRangeFilter(Builder $query, string $column, Request $request): Builder
    {
        $from = $request->string($column.'_from')->toString();
        $to = $request->string($column.'_to')->toString();

        if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) === 1) {
            $query->whereNotNull($column)->whereDate($column, '>=', $from);
        }

        if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) === 1) {
            $query->whereNotNull($column)->whereDate($column, '<=', $to);
        }

        return $query;
    }

    /**
     * Apply list-item-specific filters (quantity shown on list membership).
     *
     * @param  Builder<AssetListItem>|Relation<AssetList, AssetListItem>  $query
     * @param  list<string>|null  $selectedColumns
     * @return Builder<AssetListItem>|Relation<AssetList, AssetListItem>
     */
    public static function applyToListItemQuery(
        Builder|Relation $query,
        Request $request,
        ?array $selectedColumns = null,
    ): Builder|Relation {
        if (! self::columnFilterAllowed($selectedColumns, 'quantity')) {
            return $query;
        }

        $quantity = $request->input('quantity');
        if ($quantity !== null && $quantity !== '' && is_numeric($quantity)) {
            $query->where('asset_list_items.quantity', (int) $quantity);
        }

        return $query;
    }

    /**
     * Date-range query params used by dashboard expiry modules.
     *
     * @return list<string>
     */
    public static function dateRangeParams(): array
    {
        return [
            'test_tag_expiry_from',
            'test_tag_expiry_to',
            'warranty_expiry_from',
            'warranty_expiry_to',
        ];
    }

    /**
     * @return list<string>
     */
    public static function booleanFilterParams(): array
    {
        return [
            'missing_identifiers',
            'missing_location',
            'stock_tracked',
            'stock_below_min',
            'stock_at_max',
            'stock_zero',
        ];
    }

    /**
     * @param  list<string>|null  $selectedColumns
     */
    public static function hasActiveFilters(Request $request, ?array $selectedColumns = null): bool
    {
        if (filled($request->input('q'))) {
            return true;
        }

        foreach (self::columnParams() as $column => $param) {
            if (! self::columnFilterAllowed($selectedColumns, $column)) {
                continue;
            }

            if (filled($request->input($param))) {
                return true;
            }
        }

        foreach (self::booleanFilterParams() as $param) {
            if ($request->boolean($param)) {
                return true;
            }
        }

        foreach (self::dateRangeParams() as $param) {
            if (filled($request->input($param))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Column filter query params that belong to columns not currently shown.
     *
     * @param  list<string>  $selectedColumns
     * @return list<string>
     */
    public static function hiddenColumnFilterParams(array $selectedColumns): array
    {
        $hidden = [];

        foreach (self::columnParams() as $column => $param) {
            if (! in_array($column, $selectedColumns, true)) {
                $hidden[] = $param;
            }
        }

        return $hidden;
    }

    /**
     * Remove column-filter query params for columns that are not shown.
     *
     * @param  array<string, mixed>  $query
     * @param  list<string>  $selectedColumns
     * @return array<string, mixed>
     */
    public static function queryWithoutHiddenColumnFilters(array $query, array $selectedColumns): array
    {
        foreach (self::hiddenColumnFilterParams($selectedColumns) as $param) {
            unset($query[$param]);
        }

        return $query;
    }

    /**
     * Whether the request carries a filled filter for a column that is not shown.
     *
     * @param  list<string>  $selectedColumns
     */
    public static function hasFilledHiddenColumnFilters(Request $request, array $selectedColumns): bool
    {
        foreach (self::hiddenColumnFilterParams($selectedColumns) as $param) {
            if (filled($request->input($param))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Absolute URL with filters for hidden columns removed from the query string.
     *
     * @param  list<string>  $selectedColumns
     */
    public static function urlWithoutHiddenColumnFilters(string $url, array $selectedColumns): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        $query = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query = self::queryWithoutHiddenColumnFilters($query, $selectedColumns);

        $rebuilt = '';
        if (isset($parts['scheme'])) {
            $rebuilt .= $parts['scheme'].'://';
        }
        if (isset($parts['host'])) {
            $rebuilt .= $parts['host'];
        }
        if (isset($parts['port'])) {
            $rebuilt .= ':'.$parts['port'];
        }
        $rebuilt .= $parts['path'] ?? '';

        if ($query !== []) {
            $rebuilt .= '?'.http_build_query($query);
        }

        if (isset($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }
}
