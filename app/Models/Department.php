<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Department $department): void {
            if ($department->slug === null || $department->slug === '') {
                $department->slug = Str::slug($department->name);
            }
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function assetLists(): HasMany
    {
        return $this->hasMany(AssetList::class);
    }

    public function isGeneral(): bool
    {
        return $this->slug === 'general';
    }

    public function hasOperationalData(): bool
    {
        return Asset::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || AssetList::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || ItemType::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || SparePart::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || SpareGroup::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || File::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || ScanSession::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || UnknownScan::withoutDepartmentScope()->where('department_id', $this->id)->exists()
            || RigEvent::withoutDepartmentScope()->where('department_id', $this->id)->exists();
    }

    public function itemTypes(): HasMany
    {
        return $this->hasMany(ItemType::class);
    }
}
