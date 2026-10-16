<?php

namespace App\Exports;

use App\Models\UnknownScan;
use App\Support\CsvFormula;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UnknownScansExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, UnknownScan>  $scans
     */
    public function __construct(
        public Collection $scans
    ) {}

    public function collection(): Collection
    {
        return $this->scans->map(function (UnknownScan $scan) {
            return [
                CsvFormula::escape($scan->created_at?->format('Y-m-d H:i:s') ?? ''),
                CsvFormula::escape($scan->scanned_code),
                CsvFormula::escape($scan->quantity),
                CsvFormula::escape($scan->user?->name ?? ''),
                CsvFormula::escape($scan->location?->breadcrumb() ?? $scan->location?->name ?? ''),
                CsvFormula::escape($scan->eventList?->name ?? ''),
                CsvFormula::escape($scan->inventoryList?->name ?? ''),
                CsvFormula::escape($scan->asset_status?->label() ?? ''),
                CsvFormula::escape($scan->note ?? ''),
                CsvFormula::escape($scan->isResolved() ? 'Resolved' : 'Open'),
                CsvFormula::escape($scan->resolvedAsset?->name ?? ''),
                CsvFormula::escape($scan->resolved_at?->format('Y-m-d H:i:s') ?? ''),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Scanned at',
            'Scanned code',
            'Quantity',
            'User',
            'Location',
            'Event list',
            'Inventory list',
            'Session status',
            'Note',
            'Status',
            'Resolved asset',
            'Resolved at',
        ];
    }
}
