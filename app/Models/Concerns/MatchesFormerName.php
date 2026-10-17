<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Shared former-name matching for entity alias models (item types, locations,
 * manufacturers, manufacturer models).
 *
 * @mixin Model
 */
trait MatchesFormerName
{
    /**
     * Match former_name exactly or case-insensitively; optionally also former_slug.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereFormerNameMatches(Builder $query, string $name, ?string $slug = null): Builder
    {
        $name = trim($name);

        return $query->where(function (Builder $inner) use ($name, $slug): void {
            $inner->where('former_name', $name)
                ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)]);

            if ($slug !== null && $slug !== '') {
                $inner->orWhere('former_slug', $slug);
            }
        });
    }

    /**
     * Delete alias rows whose former_name (and optional slug) match.
     *
     * @param  array<string, mixed>  $extraConstraints
     */
    protected static function deleteMatchingFormerName(string $name, array $extraConstraints = [], ?string $slug = null): void
    {
        static::query()
            ->where($extraConstraints)
            ->whereFormerNameMatches($name, $slug)
            ->delete();
    }

    /**
     * Resolve the related entity for a former name (case-insensitive).
     *
     * @param  array<string, mixed>  $extraConstraints
     */
    protected static function resolveRelationByFormerName(
        string $name,
        string $relation,
        array $extraConstraints = [],
        ?string $slug = null,
    ): ?Model {
        $alias = static::query()
            ->with($relation)
            ->where($extraConstraints)
            ->whereFormerNameMatches($name, $slug)
            ->first();

        $related = $alias?->{$relation};

        return $related instanceof Model ? $related : null;
    }

    /**
     * Re-point existing aliases from one entity id to another.
     *
     * @param  array<string, mixed>  $updates  Column values to set (must include the FK).
     */
    protected static function repointAliases(string $foreignKey, int $fromId, array $updates): void
    {
        static::query()
            ->where($foreignKey, $fromId)
            ->update($updates);
    }
}
