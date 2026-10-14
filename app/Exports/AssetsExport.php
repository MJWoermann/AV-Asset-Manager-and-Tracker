<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\AssetListItem;
use App\Support\AssetExportColumns;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetsExport implements FromCollection, WithHeadings
{
    /** @var list<string> */
    protected array $columns;

    public function __construct(public ?int $listId = null)
    {
        $this->columns = $this->listId
            ? AssetExportColumns::forLists()
            : AssetExportColumns::forAssets();
    }

    public function collection(): Collection
    {
        $relations = AssetExportColumns::relationsFor($this->columns);

        if ($this->listId) {
            $with = ['asset'];
            foreach ($relations as $relation) {
                $with[] = 'asset.'.$relation;
            }

            return AssetListItem::query()
                ->with($with)
                ->where('asset_list_id', $this->listId)
                ->whereHas('asset')
                ->get()
                ->sortBy(fn (AssetListItem $item) => Str::lower((string) $item->asset?->name))
                ->values()
                ->map(fn (AssetListItem $item) => AssetExportColumns::row($item->asset, $this->columns, $item));
        }

        return Asset::query()
            ->with($relations)
            ->orderBy('name')
            ->get()
            ->map(fn (Asset $asset) => AssetExportColumns::row($asset, $this->columns));
    }

    public function headings(): array
    {
        return AssetExportColumns::headings($this->columns);
    }
}
