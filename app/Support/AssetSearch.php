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
     * Relation name fields searched with partial (contains) matching.
     *
     * @var array<string, string>
     */
    public const RELATION_COLUMNS = [
        'manufacturer' => 'name',
        'manufacturerModel' => 'name',
        'location' => 'name',
        'itemType' => 'name',
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
            'id' => 'ID#',
            'name' => 'Name',
            'description' => 'Description',
            'manufacturer' => 'Manufacturer',
            'manufacturerModel' => 'Model',
            'location' => 'Location',
            'itemType' => 'Type',
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
        return TextSearch::tokens($term);
    }

    public static function escapeLike(string $value): string
    {
        return TextSearch::escapeLike($value);
    }

    /**
     * OR a single token's like-pattern across asset searchable fields (for nested queries).
     */
    public static function orWhereTokenMatches(Builder $group, string $like, string $token = ''): void
    {
        foreach (self::COLUMNS as $column) {
            $group->orWhereLike($column, $like);
        }

        foreach (self::RELATION_COLUMNS as $relation => $column) {
            $group->orWhereHas(
                $relation,
                fn (Builder $related) => $related->whereLike($column, $like)
            );
        }

        $group->orWhereHas(
            'customFieldValues',
            fn (Builder $cf) => $cf->whereLike('value', $like)
        );

        self::orWhereIdMatches($group, $token);
    }

    /**
     * Match primary key when the token is digits or #digits (exact or partial).
     */
    public static function orWhereIdMatches(Builder $group, string $token): void
    {
        $idToken = str_starts_with($token, '#') ? substr($token, 1) : $token;

        if ($idToken === '' || ! ctype_digit($idToken)) {
            return;
        }

        $group->orWhere('id', (int) $idToken);

        $idLike = '%'.self::escapeLike($idToken).'%';
        $cast = $group->getConnection()->getDriverName() === 'sqlite'
            ? 'CAST(id AS TEXT)'
            : 'CAST(id AS CHAR)';

        $group->orWhereRaw($cast.' LIKE ?', [$idLike]);
    }

    /**
     * Compact label for parent / asset pickers (name + ID + identifiers).
     */
    public static function optionLabel(Asset $asset): string
    {
        $parts = array_values(array_filter([
            '#'.$asset->id,
            $asset->tp_barcode,
            $asset->fmi_ast,
            $asset->rig_tag,
            $asset->serial_number,
        ], static fn ($part) => $part !== null && $part !== ''));

        return $parts === []
            ? (string) $asset->name
            : $asset->name.' ('.implode(' · ', $parts).')';
    }

    public static function constrain(Builder $query, ?string $term): Builder
    {
        return TextSearch::constrain(
            $query,
            $term,
            fn (Builder $group, string $like, string $token) => self::orWhereTokenMatches($group, $like, $token)
        );
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
        $idTerm = str_starts_with($trimmed, '#') ? substr($trimmed, 1) : $trimmed;

        $otherColumns = array_values(array_diff(self::COLUMNS, [...self::IDENTIFIERS, 'name']));

        $exactIdentifier = [];
        $exactOther = [];
        $prefixIdentifier = [];
        $containsIdentifier = [];
        $bindings = [];
        $exactIdClause = null;

        if ($idTerm !== '' && ctype_digit($idTerm)) {
            $exactIdClause = 'id = ?';
            $bindings[] = (int) $idTerm;
        }

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

        $exactWhen = $exactIdClause !== null
            ? $exactIdClause.' OR '.implode(' OR ', $exactIdentifier)
            : implode(' OR ', $exactIdentifier);

        $sql = 'CASE'
            .' WHEN '.$exactWhen.' THEN 0'
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
        $idTerm = str_starts_with($full, '#') ? substr($full, 1) : $full;

        if ($idTerm !== '' && ctype_digit($idTerm)) {
            $idValue = (string) $asset->id;
            $score = self::scoreValue($idValue, $idTerm, [$idTerm]);

            if ($score !== null) {
                $best = [
                    'field' => 'id',
                    'label' => self::fieldLabels()['id'],
                    'value' => '#'.$asset->id,
                    'score' => $score,
                ];
            }
        }

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

        foreach (self::RELATION_COLUMNS as $relation => $column) {
            $value = (string) (data_get($asset, $relation.'.'.$column) ?? '');

            if ($value === '') {
                continue;
            }

            $score = self::scoreValue($value, $full, $tokens);

            if ($score === null) {
                continue;
            }

            $rankedScore = $score + 1;

            if ($best === null || $rankedScore < $best['score']) {
                $field = match ($relation) {
                    'manufacturerModel' => 'model',
                    'itemType' => 'item_type',
                    default => $relation,
                };

                $best = [
                    'field' => $field,
                    'label' => self::fieldLabels()[$relation] ?? $relation,
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
        return TextSearch::matchesText($haystack, $term);
    }
}
