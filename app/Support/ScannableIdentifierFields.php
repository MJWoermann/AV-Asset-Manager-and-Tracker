<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * Asset identifier fields that are printed/stuck on kit as barcodes or QR codes
 * and must support camera/wedge scan-to-fill in forms and identifier search boxes.
 *
 * FMI AST# and RIG Tag are typed identifiers only (software or printed numbers).
 */
final class ScannableIdentifierFields
{
    /**
     * Core asset columns that must offer Scan when rendered as editable inputs.
     *
     * @var list<string>
     */
    public const FIELDS = [
        'serial_number',
        'tp_barcode',
        'mac_address',
    ];

    /**
     * Typed-only identifier columns (must not require Scan UI).
     *
     * @var list<string>
     */
    public const TYPED_ONLY = [
        'fmi_ast',
        'rig_tag',
    ];

    /**
     * @return list<string>
     */
    public static function fields(): array
    {
        return self::FIELDS;
    }

    /**
     * Identifier columns that may receive a scanned code when linking an unknown scan.
     *
     * @return list<string>
     */
    public static function linkTargets(): array
    {
        return [...self::FIELDS, ...self::TYPED_ONLY];
    }

    public static function isScannable(string $field): bool
    {
        return in_array($field, self::FIELDS, true);
    }

    public static function isTypedOnly(string $field): bool
    {
        return in_array($field, self::TYPED_ONLY, true);
    }

    public static function isLinkTarget(string $field): bool
    {
        return in_array($field, self::linkTargets(), true);
    }

    /**
     * Human labels for form / aria copy.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'serial_number' => 'Serial Number',
            'tp_barcode' => 'TP Barcode',
            'mac_address' => 'MAC Address',
            'fmi_ast' => 'FMI AST#',
            'rig_tag' => 'RIG Tag #',
        ];
    }

    public static function label(string $field): string
    {
        return self::labels()[$field] ?? $field;
    }

    /**
     * OR scannable identifier columns with a partial (LIKE) match.
     * Call inside an existing where group that already constrains the asset table.
     *
     * @param  Builder  $query
     */
    public static function orWherePartialMatch($query, string $term): void
    {
        $like = '%'.$term.'%';

        foreach (self::FIELDS as $field) {
            $query->orWhere($field, 'like', $like);
        }
    }
}
