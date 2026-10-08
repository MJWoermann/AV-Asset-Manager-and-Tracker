<?php

namespace App\Models;

use App\Enums\LocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(self::class, 'parent_id');
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
}
