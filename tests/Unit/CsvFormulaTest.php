<?php

namespace Tests\Unit;

use App\Support\CsvFormula;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CsvFormulaTest extends TestCase
{
    #[DataProvider('formulaLikeValues')]
    public function test_escape_prefixes_formula_like_values(string $input, string $expected): void
    {
        $this->assertSame($expected, CsvFormula::escape($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function formulaLikeValues(): array
    {
        return [
            'equals' => ['=1+1', "'=1+1"],
            'plus' => ['+1234', "'+1234"],
            'minus' => ['-SUM(A1)', "'-SUM(A1)"],
            'at' => ['@cmd', "'@cmd"],
            'plain text' => ['Normal name', 'Normal name'],
            'empty' => ['', ''],
            'number-like text' => ['42', '42'],
            'leading space then equals' => [' =1+1', ' =1+1'],
        ];
    }

    public function test_escape_casts_non_strings(): void
    {
        $this->assertSame('3', CsvFormula::escape(3));
        $this->assertSame("'-2", CsvFormula::escape(-2));
    }
}
