<?php

namespace Tests;

use App\Models\Department;
use App\Models\User;
use App\Support\AccessPermissions;
use App\Support\DepartmentVisibility;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ensure non-admin actors have a department membership for operational tests,
     * unless the test already assigned departments explicitly.
     *
     * @param  Authenticatable  $user
     */
    public function actingAs($user, $guard = null)
    {
        if ($user instanceof User) {
            $this->ensureDepartmentContext($user);
        }

        return parent::actingAs($user, $guard);
    }

    protected function ensureDepartmentContext(User $user): void
    {
        if (! $user->hasRole('admin') && $user->departments()->count() === 0) {
            $generalId = Department::query()->where('slug', 'general')->value('id');

            if ($generalId !== null) {
                $user->departments()->attach($generalId);
            }
        }

        if (! session()->has(DepartmentVisibility::SESSION_KEY)) {
            $user->resolveActiveDepartmentId(true);
        }
    }

    protected function generalDepartment(): Department
    {
        return Department::query()->where('slug', 'general')->firstOrFail();
    }

    /**
     * Keep the preference (admins) and grant the role permission (everyone else).
     *
     * @param  array<string, bool>|string  $features
     */
    protected function enableFeature(User $user, array|string $features): User
    {
        if (is_string($features)) {
            $features = [$features => true];
        }

        $normalized = [];

        foreach ($features as $key => $enabled) {
            if (is_int($key)) {
                $normalized[$enabled] = true;

                continue;
            }

            $normalized[(string) $key] = (bool) $enabled;
        }

        $preferences = $user->preferences ?? [];
        $preferences['features'] = array_merge($preferences['features'] ?? [], $normalized);
        $user->preferences = $preferences;

        foreach ($normalized as $key => $enabled) {
            if ($enabled) {
                $user->givePermissionTo(AccessPermissions::featureName($key));
            }
        }

        $user->save();

        return $user->fresh();
    }
}
