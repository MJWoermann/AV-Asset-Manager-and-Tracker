<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

/**
 * Reads a limited set of spreadsheet data rows for mapping-step previews.
 */
class ColumnExampleImport implements ToCollection, WithHeadingRow, WithLimit
{
    public const DEFAULT_LIMIT = 50;

    /** @var Collection<int, Collection<string, mixed>> */
    public Collection $rows;

    public function __construct(protected int $rowLimit = self::DEFAULT_LIMIT)
    {
        $this->rows = collect();
    }

    public function limit(): int
    {
        return $this->rowLimit;
    }

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
