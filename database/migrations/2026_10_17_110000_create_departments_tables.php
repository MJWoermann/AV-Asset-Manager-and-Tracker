<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $operationalTables = [
        'assets',
        'asset_lists',
        'scan_sessions',
        'files',
        'spare_parts',
        'spare_groups',
        'unknown_scans',
        'rig_events',
    ];

    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id');
            $table->foreign('department_id', 'dept_user_dept_id_fk')
                ->references('id')->on('departments')->cascadeOnDelete();
            $table->foreignId('user_id');
            $table->foreign('user_id', 'dept_user_user_id_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['department_id', 'user_id'], 'dept_user_pair_uq');
        });

        $now = now();
        $generalId = DB::table('departments')->insertGetId([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($this->operationalTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $generalId) {
                $table->unsignedBigInteger('department_id')->default($generalId);
                $table->foreign('department_id', $this->fkName($tableName))
                    ->references('id')->on('departments')->restrictOnDelete();
                $table->index('department_id', $this->idxName($tableName));
            });

            DB::table($tableName)->whereNull('department_id')->update(['department_id' => $generalId]);
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('user_id');
                $table->foreign('department_id', 'audit_logs_dept_id_fk')
                    ->references('id')->on('departments')->nullOnDelete();
                $table->index('department_id', 'audit_logs_dept_id_idx');
            });
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
        $adminUserIds = collect();

        if ($adminRoleId !== null) {
            $adminUserIds = DB::table('model_has_roles')
                ->where('role_id', $adminRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');
        }

        $nonAdminUserIds = DB::table('users')
            ->when($adminUserIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $adminUserIds))
            ->pluck('id');

        foreach ($nonAdminUserIds as $userId) {
            DB::table('department_user')->insert([
                'department_id' => $generalId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'department_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign('audit_logs_dept_id_fk');
                $table->dropIndex('audit_logs_dept_id_idx');
                $table->dropColumn('department_id');
            });
        }

        foreach (array_reverse($this->operationalTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'department_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign($this->fkName($tableName));
                $table->dropIndex($this->idxName($tableName));
                $table->dropColumn('department_id');
            });
        }

        Schema::dropIfExists('department_user');
        Schema::dropIfExists('departments');
    }

    private function fkName(string $table): string
    {
        return Str::limit($table, 20, '').'_dept_id_fk';
    }

    private function idxName(string $table): string
    {
        return Str::limit($table, 20, '').'_dept_id_idx';
    }
};
