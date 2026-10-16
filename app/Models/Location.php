<?php

namespace App\Models;

use App\Enums\LocationType;
use App\Services\FileAttachmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

class Location extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'room_code',
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

    protected static function booted(): void
    {
        static::created(function (Location $location): void {
            LocationAlias::forgetMatching($location->name, $location->type);
        });

        static::updated(function (Location $location): void {
            if ($location->wasChanged(['name', 'type'])) {
                LocationAlias::forgetMatching($location->name, $location->type);
            }
        });

        static::deleting(function (Location $location): void {
            app(FileAttachmentService::class)->detachAll($location);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderByHierarchy();
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(LocationAlias::class)->orderBy('former_name');
    }

    /**
     * Racks are leaf locations and cannot parent other locations.
     * Rooms (and other non-rack types) may still nest under rooms.
     */
    public function scopeCanBeParent(Builder $query): Builder
    {
        return $query->where('type', '!=', LocationType::Rack);
    }

    /**
     * Sibling order within the location hierarchy: room code, then name.
     */
    public function scopeOrderByHierarchy(Builder $query): Builder
    {
        return $query->orderBy('room_code')->orderBy('name');
    }

    /**
     * @return Collection<int, Location>
     */
    public static function parentOptions(?int $excludeId = null): Collection
    {
        $query = static::query()->canBeParent()->orderByHierarchy();

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Location>  $locations
     * @return Collection<int, Location>
     */
    public static function sortHierarchySiblings(Collection $locations): Collection
    {
        return $locations
            ->sort(function (self $a, self $b): int {
                $codeCmp = ($a->room_code ?? '') <=> ($b->room_code ?? '');

                if ($codeCmp !== 0) {
                    return $codeCmp;
                }

                return $a->name <=> $b->name;
            })
            ->values();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'linkable', 'file_links')
            ->withPivot(['id', 'note'])
            ->withTimestamps()
            ->orderBy('files.title');
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
        $locations ??= static::query()->orderByHierarchy()->get();
        // Null parent_id cannot be used as a collection group key (PHP coerces it to "").
        $grouped = $locations->groupBy(
            fn (self $location): int|string => $location->parent_id ?? 'root'
        );

        $build = function (int|string $parentKey) use (&$build, $grouped): EloquentCollection {
            /** @var EloquentCollection<int, Location> $nodes */
            $nodes = new EloquentCollection(
                static::sortHierarchySiblings($grouped->get($parentKey, collect()))->all()
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
            ->orderByHierarchy()
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
