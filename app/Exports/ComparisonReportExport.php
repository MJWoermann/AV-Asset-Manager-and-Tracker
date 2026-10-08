<?php

namespace App\Exports;

use App\Models\AssetList;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ComparisonReportExport implements FromCollection, WithHeadings
{
    public function __construct(
        public array $result,
        public AssetList $source,
        public AssetList $target
    ) {}

    public function collection(): Collection
    {
        $rows = collect();

        foreach ($this->result['matched'] as $row) {
            $rows->push(['Matched', $row->name, $row->fmi_ast ?? '', $row->tp_barcode ?? '', $row->inventory_qty ?? '', $row->scanned_qty ?? '']);
        }
        foreach ($this->result['inventory_only'] as $row) {
            $rows->push(['Inventory only', $row->name, $row->fmi_ast ?? '', $row->tp_barcode ?? '', $row->quantity ?? '', '']);
        }
        foreach ($this->result['scanned_only'] as $row) {
            $rows->push(['Scanned only', $row->name, $row->fmi_ast ?? '', $row->tp_barcode ?? '', '', $row->quantity ?? '']);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Section',
            'Name',
            'FMI AST#',
            'TP Barcode',
            'Inventory Qty',
            'Scanned Qty',
        ];
    }
}
