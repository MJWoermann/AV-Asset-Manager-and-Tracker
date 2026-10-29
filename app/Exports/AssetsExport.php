<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Support\AssetExportColumns;
use App\Support\ExportRowLimiter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetsExport implements FromCollection, WithHeadings
{
    /** @var list<string> */
    protected array $columns;

    /** @var Collection<int, CustomFieldDefinition> */
    protected Collection $customDefinitions;

    /**
     * @param  list<int>|null  $assetIds  Limits a register export to these assets. Null exports the full register.
     */
    public function __construct(public ?int $listId = null, public ?array $assetIds = null)
    {
        $this->columns = $this->listId
            ? AssetExportColumns::forLists()
            : AssetExportColumns::forAssets();

        $this->customDefinitions = AssetExportColumns::needsCustomFields($this->columns)
            ? AssetExportColumns::customFieldDefinitions()
            : collect();
    }

    public function collection(): Collection
    {
        $relations = AssetExportColumns::relationsFor($this->columns);

        if ($this->listId) {
            $with = ['asset'];
            foreach ($relations as $relation) {
                $with[] = 'asset.'.$relation;
            }

            $query = AssetListItem::query()
                ->with($with)
                ->where('asset_list_id', $this->listId)
                ->whereHas('asset');

            ExportRowLimiter::constrain($query, 'list export');

            return $query
                ->get()
                ->sortBy(fn (AssetListItem $item) => Str::lower((string) $item->asset?->name))
                ->values()
                ->map(fn (AssetListItem $item) => AssetExportColumns::row($item->asset, $this->columns, $item));
        }

        $query = Asset::query()
            ->with($relations)
            ->orderBy('name');

        $this->limitToAssetIds($query);

        ExportRowLimiter::constrain($query, 'asset export');

        return $query
            ->get()
            ->map(fn (Asset $asset) => AssetExportColumns::row($asset, $this->columns));
    }

    public function headings(): array
    {
        return AssetExportColumns::headings($this->columns, $this->customDefinitions);
    }

    /**
     * @param  Builder<Asset>  $query
     */
    protected function limitToAssetIds(Builder $query): void
    {
        if ($this->assetIds === null) {
            return;
        }

        if ($this->assetIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $ids = $this->assetIds;

        $query->where(function (Builder $query) use ($ids): void {
            foreach (array_chunk($ids, 500) as $chunk) {
                $query->orWhereIn('id', $chunk);
            }
        });
    }
}
