<?php

namespace App\Support;

class CsvFormula
{
    /**
     * Neutralise spreadsheet formula injection by prefixing risky leading characters.
     *
     * Cells starting with =, +, -, or @ are treated as formulas by Excel/LibreOffice
     * when opened from CSV/XLSX. A leading apostrophe forces text interpretation.
     */
    public static function escape(mixed $value): string
    {
        $string = (string) $value;

        if ($string === '') {
            return $string;
        }

        $first = $string[0];

        if (in_array($first, ['=', '+', '-', '@'], true)) {
            return "'".$string;
        }

        return $string;
    }
}
