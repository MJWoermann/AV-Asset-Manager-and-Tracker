<?php

namespace App\Models;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Location extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'is_portable',
        'floorplan_path',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'is_portable' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function breadcrumb(): string
    {
        $parts = [$this->name];
        $current = $this->parent;
        while ($current) {
            array_unshift($parts, $current->name);
            $current = $current->parent;
        }

        return implode(' / ', $parts);
    }

    /**
     * Build a nested tree from a flat locations collection (single query).
     *
     * @param  Collection<int, Location>|null  $locations
     * @return EloquentCollection<int, Location>
     */
    public static function tree(?Collection $locations = null): EloquentCollection
    {
        $locations ??= static::query()->orderBy('name')->get();
        // Null parent_id cannot be used as a collection group key (PHP coerces it to "").
        $grouped = $locations->groupBy(
            fn (self $location): int|string => $location->parent_id ?? 'root'
        );

        $build = function (int|string $parentKey) use (&$build, $grouped): EloquentCollection {
            /** @var EloquentCollection<int, Location> $nodes */
            $nodes = new EloquentCollection(
                $grouped->get($parentKey, collect())->values()->all()
            );

            $nodes->each(function (self $location) use ($build): void {
                $location->setRelation('children', $build($location->id));
            });

            return $nodes;
        };

        return $build('root');
    }

    /**
     * @return list<int>
     */
    public static function selfAndDescendantIds(int $locationId): array
    {
        $grouped = static::query()
            ->orderBy('name')
            ->get(['id', 'parent_id'])
            ->groupBy(fn (self $location): int|string => $location->parent_id ?? 'root');

        $ids = [$locationId];
        $stack = [$locationId];

        while ($stack !== []) {
            $currentId = array_pop($stack);

            foreach ($grouped->get($currentId, collect()) as $child) {
                $ids[] = (int) $child->id;
                $stack[] = (int) $child->id;
            }
        }

        return $ids;
    }
}
