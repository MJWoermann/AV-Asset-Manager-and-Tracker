<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();

            $table->unique('name');
        });

        Schema::create('manufacturer_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['manufacturer_id', 'name']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('manufacturer_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->foreignId('manufacturer_model_id')->nullable()->after('manufacturer_id')->constrained('manufacturer_models')->nullOnDelete();
        });

        $this->backfillCatalogueFromFreeText();

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['manufacturer', 'model']);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('manufacturer')->nullable()->after('status');
            $table->string('model')->nullable()->after('manufacturer');
        });

        $pairs = DB::table('assets')
            ->leftJoin('manufacturers', 'manufacturers.id', '=', 'assets.manufacturer_id')
            ->leftJoin('manufacturer_models', 'manufacturer_models.id', '=', 'assets.manufacturer_model_id')
            ->select([
                'assets.id',
                'manufacturers.name as manufacturer_name',
                'manufacturer_models.name as model_name',
            ])
            ->get();

        foreach ($pairs as $row) {
            DB::table('assets')->where('id', $row->id)->update([
                'manufacturer' => $row->manufacturer_name,
                'model' => $row->model_name,
            ]);
        }

        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manufacturer_model_id');
            $table->dropConstrainedForeignId('manufacturer_id');
        });

        Schema::dropIfExists('manufacturer_models');
        Schema::dropIfExists('manufacturers');
    }

    protected function backfillCatalogueFromFreeText(): void
    {
        $rows = DB::table('assets')
            ->select(['id', 'manufacturer', 'model'])
            ->where(function ($query) {
                $query->whereNotNull('manufacturer')
                    ->where('manufacturer', '!=', '')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('model')->where('model', '!=', '');
                    });
            })
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        /** @var array<string, int> $manufacturerIds */
        $manufacturerIds = [];
        /** @var array<string, int> $modelIds */
        $modelIds = [];

        foreach ($rows as $row) {
            $manufacturerName = $this->normalizedName($row->manufacturer);
            $modelName = $this->normalizedName($row->model);

            if ($manufacturerName === null && $modelName === null) {
                continue;
            }

            if ($manufacturerName === null) {
                $manufacturerName = '(Unspecified)';
            }

            $manufacturerKey = mb_strtolower($manufacturerName);
            if (! isset($manufacturerIds[$manufacturerKey])) {
                $existingId = DB::table('manufacturers')
                    ->whereRaw('LOWER(name) = ?', [$manufacturerKey])
                    ->value('id');

                if ($existingId) {
                    $manufacturerIds[$manufacturerKey] = (int) $existingId;
                } else {
                    $manufacturerIds[$manufacturerKey] = (int) DB::table('manufacturers')->insertGetId([
                        'name' => $manufacturerName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $manufacturerId = $manufacturerIds[$manufacturerKey];
            $manufacturerModelId = null;

            if ($modelName !== null) {
                $modelKey = $manufacturerId.'|'.mb_strtolower($modelName);
                if (! isset($modelIds[$modelKey])) {
                    $existingModelId = DB::table('manufacturer_models')
                        ->where('manufacturer_id', $manufacturerId)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($modelName)])
                        ->value('id');

                    if ($existingModelId) {
                        $modelIds[$modelKey] = (int) $existingModelId;
                    } else {
                        $modelIds[$modelKey] = (int) DB::table('manufacturer_models')->insertGetId([
                            'manufacturer_id' => $manufacturerId,
                            'name' => $modelName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $manufacturerModelId = $modelIds[$modelKey];
            }

            DB::table('assets')->where('id', $row->id)->update([
                'manufacturer_id' => $manufacturerId,
                'manufacturer_model_id' => $manufacturerModelId,
            ]);
        }
    }

    protected function normalizedName(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
};
