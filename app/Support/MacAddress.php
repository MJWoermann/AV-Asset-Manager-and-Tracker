<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * MAC address normalisation for case-insensitive, separator-agnostic matching.
 *
 * Accepts colons, dashes, dots, spaces, or no separators between hex digits.
 * Stored display values are left unchanged; comparisons use a compact hex form.
 */
final class MacAddress
{
    /**
     * Strip separators and lowercase hex digits for comparison.
     */
    public static function compact(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $compact = strtolower(preg_replace('/[^a-fA-F0-9]/', '', $trimmed) ?? '');

        return $compact === '' ? null : $compact;
    }

    /**
     * Whether the value is a standard 48-bit (12 hex) or EUI-64 (16 hex) MAC.
     */
    public static function isValid(?string $value): bool
    {
        $compact = self::compact($value);

        return $compact !== null && (strlen($compact) === 12 || strlen($compact) === 16);
    }

    /**
     * Format as uppercase colon-separated pairs when the value is a valid MAC.
     */
    public static function format(?string $value): ?string
    {
        $compact = self::compact($value);
        if ($compact === null || (strlen($compact) !== 12 && strlen($compact) !== 16)) {
            return NullableString::from($value);
        }

        return strtoupper(implode(':', str_split($compact, 2)));
    }

    /**
     * SQL expression that strips common separators and lowercases the column.
     */
    public static function sqlCompactExpression(string $column): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?$/', $column) !== 1) {
            throw new InvalidArgumentException('Invalid column for MAC compact expression.');
        }

        return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE({$column}, ':', ''), '-', ''), '.', ''), ' ', ''))";
    }

    public static function equals(?string $a, ?string $b): bool
    {
        $left = self::compact($a);
        $right = self::compact($b);

        return $left !== null && $right !== null && $left === $right;
    }

    /**
     * Case-insensitive, separator-agnostic contains match (PHP side).
     */
    public static function contains(?string $haystack, ?string $needle): bool
    {
        $compactNeedle = self::compact($needle);
        if ($compactNeedle === null) {
            return false;
        }

        $compactHaystack = self::compact($haystack) ?? '';

        return str_contains($compactHaystack, $compactNeedle);
    }

    /**
     * OR a literal LIKE plus compact-form LIKE for browse/search.
     *
     * @param  Builder<*>|QueryBuilder  $query
     */
    public static function orWhereMatches($query, string $token, string $column = 'mac_address'): void
    {
        $like = '%'.TextSearch::escapeLike($token).'%';
        $query->orWhereLike($column, $like);

        $compact = self::compact($token);
        if ($compact === null || strlen($compact) < 2) {
            return;
        }

        $compactLike = '%'.TextSearch::escapeLike($compact).'%';
        $query->orWhereRaw(self::sqlCompactExpression($column).' LIKE ?', [$compactLike]);
    }

    /**
     * Exact match on literal value or compact hex form.
     *
     * @param  Builder<*>|QueryBuilder  $query
     */
    public static function whereEquals($query, string $value, string $column = 'mac_address'): void
    {
        $query->where(function ($group) use ($value, $column): void {
            self::applyEquals($group, $value, $column);
        });
    }

    /**
     * @param  Builder<*>|QueryBuilder  $query
     */
    public static function orWhereEquals($query, string $value, string $column = 'mac_address'): void
    {
        $query->orWhere(function ($group) use ($value, $column): void {
            self::applyEquals($group, $value, $column);
        });
    }

    /**
     * @param  Builder<*>|QueryBuilder  $query
     */
    protected static function applyEquals($query, string $value, string $column): void
    {
        $query->where($column, $value);

        $compact = self::compact($value);
        if ($compact === null) {
            return;
        }

        $query->orWhereRaw(self::sqlCompactExpression($column).' = ?', [$compact]);
    }
}
