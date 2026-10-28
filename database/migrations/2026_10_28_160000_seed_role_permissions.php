<?php

use App\Support\AccessPermissions;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Create permission rows and seed built-in roles that do not have permissions yet.
     */
    public function up(): void
    {
        AccessPermissions::sync();
    }

    public function down(): void
    {
        // Permissions may be assigned to roles; do not remove on rollback.
    }
};
