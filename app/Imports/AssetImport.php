<?php

namespace App\Imports;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AssetImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    /**
     * @param  array<string, string|null>  $mapping  column heading => asset field
     * @param  array<int, string>  $columns
     */
    public function __construct(
        public int $itemTypeId,
        public array $mapping,
        public array $columns = [],
        public bool $dryRun = false
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $payload = ['item_type_id' => $this->itemTypeId];

            foreach ($this->mapping as $column => $field) {
                if (! $field) {
                    continue;
                }
                $key = Str::slug($column, '_');
                $value = $row[$key] ?? $row[$column] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                if ($field === 'status') {
                    $value = Str::slug((string) $value, '_');
                    if (! AssetStatus::tryFrom($value)) {
                        $value = AssetStatus::Available->value;
                    }
                }
                $payload[$field] = $value;
            }

            if (empty($payload['name'])) {
                $this->skipped++;

                continue;
            }

            $existing = null;
            foreach (['tp_barcode', 'fmi_ast', 'device_sn', 'serial_number'] as $idField) {
                if (! empty($payload[$idField])) {
                    $existing = Asset::where($idField, $payload[$idField])->first();
                    if ($existing) {
                        break;
                    }
                }
            }

            if ($this->dryRun) {
                $existing ? $this->updated++ : $this->created++;

                continue;
            }

            if ($existing) {
                $existing->update($payload);
                $this->updated++;
            } else {
                Asset::create($payload + ['status' => $payload['status'] ?? AssetStatus::Available->value]);
                $this->created++;
            }
        }
    }
}
