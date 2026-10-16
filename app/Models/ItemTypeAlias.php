<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ItemTypeAlias extends Model
{
    protected $fillable = [
        'former_name',
        'former_slug',
        'item_type_id',
    ];

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    /**
     * Point the deleted type's name (and any aliases already pointing at it) at the replacement.
     */
    public static function recordMerge(ItemType $from, ItemType $to): void
    {
        static::query()
            ->where('item_type_id', $from->id)
            ->update(['item_type_id' => $to->id]);

        static::forgetMatching($from->name, $from->slug);

        static::query()->create([
            'former_name' => $from->name,
            'former_slug' => $from->slug !== ''
                ? $from->slug
                : Str::slug($from->name),
            'item_type_id' => $to->id,
        ]);
    }

    public static function forgetMatching(string $name, ?string $slug = null): void
    {
        $name = trim($name);
        $slug = $slug !== null && $slug !== '' ? $slug : Str::slug($name);

        static::query()
            ->where(function ($query) use ($name, $slug): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)])
                    ->orWhere('former_slug', $slug);
            })
            ->delete();
    }

    public static function resolveItemType(string $name): ?ItemType
    {
        $name = trim($name);
        $slug = Str::slug($name);

        $alias = static::query()
            ->with('itemType')
            ->where(function ($query) use ($name, $slug): void {
                $query->where('former_name', $name)
                    ->orWhereRaw('LOWER(former_name) = ?', [Str::lower($name)])
                    ->orWhere('former_slug', $slug);
            })
            ->first();

        return $alias?->itemType;
    }
}
