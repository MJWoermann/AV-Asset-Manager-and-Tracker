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
        'manufacturer',
        'model',
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
            'manufacturer' => 'manufacturer',
            'model' => 'model',
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
     * Apply asset-level browse filters to an Asset query builder.
     *
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    public static function applyToAssetQuery(Builder $query, Request $request, bool $includeQuantity = true): Builder
    {
        $term = self::term($request);
        $locationId = $request->integer('location_id') ?: null;
        $itemTypeId = $request->integer('item_type_id') ?: null;
        $status = $request->string('status')->toString();

        $query
            ->search($term)
            ->when(
                $status !== '' && AssetStatus::tryFrom($status) !== null,
                fn (Builder $builder) => $builder->where('status', $status)
            )
            ->when($locationId, function (Builder $builder) use ($locationId) {
                $builder->whereIn('location_id', Location::selfAndDescendantIds($locationId));
            })
            ->when($itemTypeId, fn (Builder $builder) => $builder->where('item_type_id', $itemTypeId));

        foreach (self::TEXT_COLUMNS as $column) {
            $value = trim($request->string($column)->toString());

            if ($value === '') {
                continue;
            }

            $like = '%'.AssetSearch::escapeLike($value).'%';
            $query->whereLike($column, $like);
        }

        $parent = trim($request->string('parent')->toString());
        if ($parent !== '') {
            $like = '%'.AssetSearch::escapeLike($parent).'%';
            $query->whereHas(
                'parent',
                fn (Builder $builder) => $builder->whereLike('name', $like)
            );
        }

        if ($includeQuantity) {
            $quantity = $request->input('quantity');
            if ($quantity !== null && $quantity !== '' && is_numeric($quantity)) {
                $query->where('quantity', (int) $quantity);
            }
        }

        $testTagExpiry = $request->string('test_tag_expiry')->toString();
        if ($testTagExpiry !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $testTagExpiry) === 1) {
            $query->whereDate('test_tag_expiry', $testTagExpiry);
        }

        return $query;
    }

    /**
     * Apply list-item-specific filters (quantity shown on list membership).
     *
     * @param  Builder<AssetListItem>|Relation<AssetList, AssetListItem>  $query
     * @return Builder<AssetListItem>|Relation<AssetList, AssetListItem>
     */
    public static function applyToListItemQuery(Builder|Relation $query, Request $request): Builder|Relation
    {
        $quantity = $request->input('quantity');
        if ($quantity !== null && $quantity !== '' && is_numeric($quantity)) {
            $query->where('asset_list_items.quantity', (int) $quantity);
        }

        return $query;
    }

    public static function hasActiveFilters(Request $request): bool
    {
        if (filled($request->input('q'))) {
            return true;
        }

        foreach (self::columnParams() as $param) {
            if (filled($request->input($param))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Active filter query params that should be preserved when their column is hidden.
     *
     * @param  list<string>  $selectedColumns
     * @return array<string, string>
     */
    public static function hiddenPreservedParams(Request $request, array $selectedColumns): array
    {
        $selectedParams = [];
        foreach ($selectedColumns as $column) {
            $param = self::columnParams()[$column] ?? null;
            if ($param !== null) {
                $selectedParams[$param] = true;
            }
        }

        $preserved = [];
        foreach (self::columnParams() as $param) {
            if (isset($selectedParams[$param])) {
                continue;
            }

            $value = $request->input($param);
            if ($value === null || $value === '') {
                continue;
            }

            $preserved[$param] = is_scalar($value) ? (string) $value : '';
        }

        return array_filter($preserved, fn (string $value) => $value !== '');
    }
}
