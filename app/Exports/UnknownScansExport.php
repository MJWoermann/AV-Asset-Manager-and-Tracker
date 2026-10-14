<?php

namespace App\Exports;

use App\Models\UnknownScan;
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
                $scan->created_at?->format('Y-m-d H:i:s'),
                $scan->scanned_code,
                $scan->quantity,
                $scan->user?->name ?? '',
                $scan->location?->breadcrumb() ?? $scan->location?->name ?? '',
                $scan->eventList?->name ?? '',
                $scan->inventoryList?->name ?? '',
                $scan->asset_status?->label() ?? '',
                $scan->note ?? '',
                $scan->isResolved() ? 'Resolved' : 'Open',
                $scan->resolvedAsset?->name ?? '',
                $scan->resolved_at?->format('Y-m-d H:i:s') ?? '',
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
