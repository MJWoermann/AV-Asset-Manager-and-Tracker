<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Ensure Spatie roles exist on upgraded installs (migrate without re-seeding).
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['admin', 'inventory_manager', 'scanner', 'external_repairer'] as $role) {
            Role::findOrCreate($role);
        }
    }

    public function down(): void
    {
        // Roles may be assigned to users; do not remove on rollback.
    }
};
