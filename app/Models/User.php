<?php

namespace App\Models;

use App\Support\AssetTableColumns;
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
     * @return list<string>
     */
    public function assetTableColumns(): array
    {
        $selected = ($this->preferences ?? [])[AssetTableColumns::PREFERENCE_KEY] ?? null;

        return AssetTableColumns::resolve(is_array($selected) ? $selected : null);
    }
}
