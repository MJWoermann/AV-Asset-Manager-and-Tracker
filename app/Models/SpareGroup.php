<?php

namespace App\Models;

use App\Models\Concerns\BelongsToDepartment;
use Database\Factories\SpareGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpareGroup extends Model
{
    /** @use HasFactory<SpareGroupFactory> */
    use BelongsToDepartment, HasFactory;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'location_id',
        'asset_id',
        'notes',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function linkedSystem(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpareGroupItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
