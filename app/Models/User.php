<?php

namespace App\Models;

use App\Support\AssetTableColumns;
use App\Support\DashboardModules;
use App\Support\DeveloperFeatures;
use App\Support\ScanNoise;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'theme', 'preferences'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
        ];
    }

    public function prefersDarkMode(): bool
    {
        return $this->theme === 'dark';
    }

    /**
     * Whether the user has opted into a developer feature via preferences.features.{feature}.
     */
    public function hasFeature(string $feature): bool
    {
        return DeveloperFeatures::enabled($this->preferences, $feature);
    }

    /**
     * Whether scan success/fail tones should play during barcode scanning.
     */
    public function wantsScanNoise(): bool
    {
        return ScanNoise::enabled($this->preferences);
    }

    /**
     * Alias for hasFeature() (RU Visualiser / plan naming).
     */
    public function hasDeveloperFeature(string $key): bool
    {
        return $this->hasFeature($key);
    }

    /**
     * @return list<string>
     */
    public function assetTableColumns(): array
    {
        $selected = ($this->preferences ?? [])[AssetTableColumns::PREFERENCE_KEY] ?? null;

        return AssetTableColumns::resolve(is_array($selected) ? $selected : null);
    }

    /**
     * @return list<array{id: string, label: string, enabled: bool}>
     */
    public function dashboardModules(): array
    {
        $stored = ($this->preferences ?? [])[DashboardModules::PREFERENCE_KEY] ?? null;

        return DashboardModules::resolve(
            is_array($stored) ? $stored : null,
            DashboardModules::availableFor($this)
        );
    }
}
