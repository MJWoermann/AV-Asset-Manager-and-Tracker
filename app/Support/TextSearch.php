<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared multi-token text search for table browse boxes.
 *
 * Tokens are whitespace-split. Each token must match (AND across tokens);
 * within a token, listed columns are OR'd so phrases can hit different fields.
 * Matching is case-insensitive contains (partial), which also covers complete matches.
 */
class TextSearch
{
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

    /**
     * Apply a per-token callback. Each token becomes an AND group.
     *
     * @param  callable(Builder $group, string $like, string $token): void  $matchToken
     */
    public static function constrain(Builder $query, ?string $term, callable $matchToken): Builder
    {
        foreach (self::tokens($term) as $token) {
            $like = '%'.self::escapeLike($token).'%';

            $query->where(function (Builder $group) use ($matchToken, $like, $token): void {
                $matchToken($group, $like, $token);
            });
        }

        return $query;
    }

    /**
     * Each token must match at least one of the given columns.
     *
     * @param  list<string>  $columns
     */
    public static function constrainColumns(Builder $query, ?string $term, array $columns): Builder
    {
        if ($columns === []) {
            return $query;
        }

        return self::constrain($query, $term, function (Builder $group, string $like) use ($columns): void {
            foreach ($columns as $column) {
                $group->orWhereLike($column, $like);
            }
        });
    }

    /**
     * Multi-token AND against a single column (partial contains per token).
     */
    public static function constrainColumn(Builder $query, ?string $term, string $column): Builder
    {
        return self::constrainColumns($query, $term, [$column]);
    }
}
