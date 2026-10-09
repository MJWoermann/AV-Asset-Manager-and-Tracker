<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AssetSearch
{
    /**
     * Columns searched with partial (contains) matching.
     *
     * @var list<string>
     */
    public const COLUMNS = [
        'name',
        'description',
        'manufacturer',
        'model',
        'serial_number',
        'fmi_ast',
        'tp_barcode',
        'rig_tag',
        'ip_address',
        'mac_address',
        'supplier',
        'notes',
    ];

    /**
     * High-priority identifier columns for relevance ranking.
     *
     * @var list<string>
     */
    public const IDENTIFIERS = [
        'fmi_ast',
        'tp_barcode',
        'rig_tag',
        'serial_number',
        'ip_address',
        'mac_address',
    ];

    /**
     * @return array<string, string>
     */
    public static function fieldLabels(): array
    {
        return [
            'name' => 'Name',
            'description' => 'Description',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'serial_number' => 'Serial',
            'fmi_ast' => 'FMI AST#',
            'tp_barcode' => 'TP Barcode',
            'rig_tag' => 'RIG Tag',
            'ip_address' => 'IP',
            'mac_address' => 'MAC',
            'supplier' => 'Supplier',
            'notes' => 'Notes',
            'custom_field' => 'Custom field',
        ];
    }

    /**
     * @return list<string>
     */
    public static function tokens(?string $term): array
    {
        if ($term === null) {
            return [];
        }

        $trimmed = trim($term);

        if ($trimmed === '') {
            return [];
        }

        return preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [$trimmed];
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public static function constrain(Builder $query, ?string $term): Builder
    {
        $tokens = self::tokens($term);

        if ($tokens === []) {
            return $query;
        }

        foreach ($tokens as $token) {
            $like = '%'.self::escapeLike($token).'%';

            $query->where(function (Builder $group) use ($like) {
                foreach (self::COLUMNS as $column) {
                    $group->orWhereLike($column, $like);
                }

                $group->orWhereHas(
                    'customFieldValues',
                    fn (Builder $cf) => $cf->whereLike('value', $like)
                );
            });
        }

        return $query;
    }

    public static function orderByRelevance(Builder $query, ?string $term): Builder
    {
        $trimmed = trim((string) $term);

        if ($trimmed === '') {
            return $query->orderBy('name');
        }

        $lower = mb_strtolower($trimmed);
        $prefix = self::escapeLike($trimmed).'%';
        $contains = '%'.self::escapeLike($trimmed).'%';

        $otherColumns = array_values(array_diff(self::COLUMNS, [...self::IDENTIFIERS, 'name']));

        $exactIdentifier = [];
        $exactOther = [];
        $prefixIdentifier = [];
        $containsIdentifier = [];
        $bindings = [];

        foreach (self::IDENTIFIERS as $column) {
            $exactIdentifier[] = "LOWER({$column}) = ?";
            $bindings[] = $lower;
        }

        $bindings[] = $lower;

        foreach ($otherColumns as $column) {
            $exactOther[] = "LOWER({$column}) = ?";
            $bindings[] = $lower;
        }

        foreach (self::IDENTIFIERS as $column) {
            $prefixIdentifier[] = "{$column} LIKE ?";
            $bindings[] = $prefix;
        }

        $bindings[] = $prefix;

        foreach (self::IDENTIFIERS as $column) {
            $containsIdentifier[] = "{$column} LIKE ?";
            $bindings[] = $contains;
        }

        $sql = 'CASE'
            .' WHEN '.implode(' OR ', $exactIdentifier).' THEN 0'
            .' WHEN LOWER(name) = ? THEN 1'
            .' WHEN '.implode(' OR ', $exactOther).' THEN 2'
            .' WHEN '.implode(' OR ', $prefixIdentifier).' THEN 3'
            .' WHEN name LIKE ? THEN 4'
            .' WHEN '.implode(' OR ', $containsIdentifier).' THEN 5'
            .' ELSE 6 END';

        return $query
            ->orderByRaw($sql, $bindings)
            ->orderBy('name');
    }

    public static function highlight(?string $text, ?string $term): HtmlString
    {
        if ($text === null || $text === '') {
            return new HtmlString('');
        }

        $escaped = e($text);
        $tokens = self::tokens($term);

        if ($tokens === []) {
            return new HtmlString($escaped);
        }

        $pattern = '/('.implode('|', array_map(
            static fn (string $token) => preg_quote($token, '/'),
            $tokens
        )).')/iu';

        $highlighted = preg_replace(
            $pattern,
            '<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">$1</mark>',
            $escaped
        );

        return new HtmlString($highlighted ?? $escaped);
    }

    /**
     * Best matching field for display hints (lower score = better).
     *
     * @return array{field: string, label: string, value: string, score: float|int}|null
     */
    public static function bestMatch(Asset $asset, ?string $term): ?array
    {
        $tokens = self::tokens($term);

        if ($tokens === []) {
            return null;
        }

        $full = trim((string) $term);
        $best = null;

        foreach (self::COLUMNS as $column) {
            $value = (string) ($asset->{$column} ?? '');

            if ($value === '') {
                continue;
            }

            $score = self::scoreValue($value, $full, $tokens);

            if ($score === null) {
                continue;
            }

            // Prefer identifier fields over free-text when scores tie.
            $rankedScore = in_array($column, self::IDENTIFIERS, true)
                ? $score
                : $score + ($column === 'name' ? 0.5 : 1);

            if ($best === null || $rankedScore < $best['score']) {
                $best = [
                    'field' => $column,
                    'label' => self::fieldLabels()[$column] ?? $column,
                    'value' => $value,
                    'score' => $rankedScore,
                ];
            }
        }

        if ($asset->relationLoaded('customFieldValues')) {
            foreach ($asset->customFieldValues as $customValue) {
                $value = (string) ($customValue->value ?? '');

                if ($value === '') {
                    continue;
                }

                $score = self::scoreValue($value, $full, $tokens);

                if ($score === null) {
                    continue;
                }

                // Custom fields rank after column contains matches.
                $score = max($score, 5);

                if ($best === null || $score < $best['score']) {
                    $best = [
                        'field' => 'custom_field',
                        'label' => self::fieldLabels()['custom_field'],
                        'value' => $value,
                        'score' => $score,
                    ];
                }
            }
        }

        return $best;
    }

    /**
     * @param  list<string>  $tokens
     */
    public static function scoreValue(string $value, string $fullTerm, array $tokens): ?int
    {
        $lowerValue = mb_strtolower($value);
        $lowerFull = mb_strtolower($fullTerm);

        if ($lowerValue === $lowerFull) {
            return 0;
        }

        if (str_starts_with($lowerValue, $lowerFull)) {
            return 2;
        }

        if (str_contains($lowerValue, $lowerFull)) {
            return 4;
        }

        foreach ($tokens as $token) {
            if (str_contains($lowerValue, mb_strtolower($token))) {
                return 5;
            }
        }

        return null;
    }

    /**
     * Whether haystack matches all search tokens (client-side / collection filter).
     */
    public static function matchesText(string $haystack, ?string $term): bool
    {
        $tokens = self::tokens($term);

        if ($tokens === []) {
            return true;
        }

        $lower = mb_strtolower($haystack);

        foreach ($tokens as $token) {
            if (! str_contains($lower, mb_strtolower($token))) {
                return false;
            }
        }

        return true;
    }
}
