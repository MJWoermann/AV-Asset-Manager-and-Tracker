<?php

namespace App\Exports;

use App\Models\AssetCheckout;
use App\Support\CsvFormula;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CheckoutReportExport implements FromCollection, WithHeadings
{
    /**
     * @param  list<array{manufacturer: string, model: string, available: int, checked_out: int, in_register: int}>  $modelRows
     * @param  list<array{label: string, detail: string, checkouts: Collection<int, AssetCheckout>}>  $holderGroups
     */
    public function __construct(
        public string $view,
        public array $modelRows,
        public array $holderGroups,
    ) {}

    public function collection(): Collection
    {
        if ($this->view === 'holders') {
            return $this->holderRows();
        }

        return collect($this->modelRows)->map(fn (array $row): array => $this->escapedRow([
            $row['manufacturer'],
            $row['model'],
            $row['available'],
            $row['checked_out'],
            $row['in_register'],
        ]));
    }

    public function headings(): array
    {
        if ($this->view === 'holders') {
            return [
                'Holder',
                'Holder detail',
                'Asset',
                'Serial',
                'TP barcode',
                'FMI AST#',
                'Manufacturer',
                'Model',
                'Purpose',
                'Checked out',
                'Expected return',
                'Returned',
                'Location',
                'Notes',
            ];
        }

        return [
            'Manufacturer',
            'Model',
            'Available',
            'Checked out',
            'In register',
        ];
    }

    /**
     * @return Collection<int, list<string>>
     */
    protected function holderRows(): Collection
    {
        $rows = collect();

        foreach ($this->holderGroups as $group) {
            foreach ($group['checkouts'] as $checkout) {
                $asset = $checkout->asset;

                $rows->push($this->escapedRow([
                    $group['label'],
                    $group['detail'],
                    $asset?->name ?? '',
                    $asset?->serial_number ?? '',
                    $asset?->tp_barcode ?? '',
                    $asset?->fmi_ast ?? '',
                    $asset?->manufacturer?->name ?? '',
                    $asset?->manufacturerModel?->name ?? '',
                    $checkout->purposeLabel(),
                    $checkout->created_at?->format('Y-m-d H:i') ?? '',
                    $checkout->expected_return_at?->format('Y-m-d H:i') ?? '',
                    $checkout->returned_at?->format('Y-m-d H:i') ?? '',
                    $asset?->location?->name ?? '',
                    $checkout->notes ?? '',
                ]));
            }
        }

        return $rows;
    }

    /**
     * @param  list<mixed>  $cells
     * @return list<string>
     */
    protected function escapedRow(array $cells): array
    {
        return array_map(
            fn (mixed $cell): string => CsvFormula::escape($cell),
            $cells,
        );
    }
}
