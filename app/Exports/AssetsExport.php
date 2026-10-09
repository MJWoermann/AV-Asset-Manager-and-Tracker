<?php

namespace App\Exports;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AssetsExport implements FromCollection, WithHeadings
{
    public function __construct(public ?int $listId = null) {}

    public function collection(): Collection
    {
        $query = Asset::query()->with('itemType')->orderBy('name');

        if ($this->listId) {
            $query->whereIn('id', function ($q) {
                $q->select('asset_id')
                    ->from('asset_list_items')
                    ->where('asset_list_id', $this->listId);
            });
        }

        return $query->get()->map(fn (Asset $a) => [
            $a->name,
            $a->description,
            $a->itemType?->name,
            $a->status?->value,
            $a->manufacturer,
            $a->model,
            $a->serial_number,
            $a->fmi_ast,
            $a->tp_barcode,
            $a->rig_tag,
            $a->ip_address,
            $a->mac_address,
            $a->quantity,
        ]);
    }

    public function headings(): array
    {
        return [
            'Name', 'Description', 'Item Type', 'Status', 'Manufacturer', 'Model', 'Serial Number',
            'FMI AST#', 'TP Barcode', 'RIG Tag #', 'IP Address', 'MAC Address', 'Quantity',
        ];
    }
}
