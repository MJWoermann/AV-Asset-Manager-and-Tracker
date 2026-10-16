<?php

namespace App\Models;

use App\Support\AssetTableColumns;
use App\Support\DashboardModules;
use App\Support\DepartmentVisibility;
use App\Support\DeveloperFeatures;
use App\Support\ScanNoise;
use App\Support\ScanVibrate;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
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
     * Whether haptic vibration should fire on scan match/fail feedback.
     */
    public function wantsScanVibrate(): bool
    {
        return ScanVibrate::enabled($this->preferences);
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

    /**
     * Department ids this user may see on operational queries.
     * Null = unrestricted (admin with empty visibility preference).
     *
     * @return list<int>|null
     */
    public function departmentScopeIds(): ?array
    {
        if ($this->hasRole('admin')) {
            return DepartmentVisibility::scopeIdsFromPreferences($this->preferences);
        }

        return $this->departments()
            ->pluck('departments.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function visibleDepartmentIdsPreference(): array
    {
        return DepartmentVisibility::normalizeIds(
            ($this->preferences ?? [])[DepartmentVisibility::PREFERENCE_KEY] ?? null
        );
    }

    public function canUseDepartment(int $departmentId): bool
    {
        if ($this->hasRole('admin')) {
            return Department::query()->whereKey($departmentId)->exists();
        }

        return $this->departments()->where('departments.id', $departmentId)->exists();
    }

    /**
     * Preferred department for create stamping when session is unset/invalid.
     */
    public function defaultDepartmentIdForCreate(): ?int
    {
        if ($this->hasRole('admin')) {
            $visible = $this->visibleDepartmentIdsPreference();

            if ($visible !== []) {
                return $visible[0];
            }

            return Department::query()->where('is_active', true)->orderBy('name')->value('id');
        }

        return $this->departments()->orderBy('departments.name')->value('departments.id');
    }

    /**
     * Resolve (and optionally persist) the session active department.
     */
    public function resolveActiveDepartmentId(bool $persist = true): ?int
    {
        $sessionId = session(DepartmentVisibility::SESSION_KEY);

        if ($sessionId !== null && $this->canUseDepartment((int) $sessionId)) {
            return (int) $sessionId;
        }

        $fallback = $this->defaultDepartmentIdForCreate();

        if ($persist && $fallback !== null) {
            session([DepartmentVisibility::SESSION_KEY => $fallback]);
        }

        return $fallback !== null ? (int) $fallback : null;
    }
}
