<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Manufacturer;
use App\Models\ManufacturerAlias;
use App\Models\ManufacturerModel;
use App\Models\ManufacturerModelAlias;
use App\Models\RepairProcedure;
use App\Models\SparePart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ManufacturerCatalogueService
{
    /** @var array<string, Manufacturer> */
    protected array $manufacturerCache = [];

    /** @var array<string, ManufacturerModel> */
    protected array $modelCache = [];

    public function resolveManufacturer(?string $name): ?Manufacturer
    {
        $name = $this->nullableTrim($name);
        if ($name === null) {
            return null;
        }

        $cacheKey = Str::lower($name);
        if (isset($this->manufacturerCache[$cacheKey])) {
            return $this->manufacturerCache[$cacheKey];
        }

        $existing = Manufacturer::query()
            ->where(function ($query) use ($name) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($name)]);
            })
            ->first();

        if ($existing) {
            return $this->manufacturerCache[$cacheKey] = $existing;
        }

        $aliased = ManufacturerAlias::resolveManufacturer($name);
        if ($aliased) {
            return $this->manufacturerCache[$cacheKey] = $aliased;
        }

        return $this->manufacturerCache[$cacheKey] = Manufacturer::create(['name' => $name]);
    }

    public function resolveModel(?Manufacturer $manufacturer, ?string $name): ?ManufacturerModel
    {
        $name = $this->nullableTrim($name);
        if ($name === null || $manufacturer === null) {
            return null;
        }

        $cacheKey = $manufacturer->id.'|'.Str::lower($name);
        if (isset($this->modelCache[$cacheKey])) {
            return $this->modelCache[$cacheKey];
        }

        $existing = ManufacturerModel::query()
            ->where('manufacturer_id', $manufacturer->id)
            ->where(function ($query) use ($name) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($name)]);
            })
            ->first();

        if ($existing) {
            return $this->modelCache[$cacheKey] = $existing;
        }

        $aliased = ManufacturerModelAlias::resolveModel($manufacturer, $name);
        if ($aliased) {
            return $this->modelCache[$cacheKey] = $aliased;
        }

        return $this->modelCache[$cacheKey] = ManufacturerModel::create([
            'manufacturer_id' => $manufacturer->id,
            'name' => $name,
        ]);
    }

    /**
     * Resolve free-text manufacturer/model into catalogue IDs (creating as needed).
     *
     * @return array{manufacturer_id: int|null, manufacturer_model_id: int|null}
     */
    public function resolveFromNames(?string $manufacturerName, ?string $modelName): array
    {
        $manufacturerName = $this->nullableTrim($manufacturerName);
        $modelName = $this->nullableTrim($modelName);

        if ($manufacturerName === null && $modelName === null) {
            return [
                'manufacturer_id' => null,
                'manufacturer_model_id' => null,
            ];
        }

        if ($manufacturerName === null) {
            $manufacturerName = Manufacturer::UNSPECIFIED_NAME;
        }

        $manufacturer = $this->resolveManufacturer($manufacturerName);
        $model = $this->resolveModel($manufacturer, $modelName);

        return [
            'manufacturer_id' => $manufacturer?->id,
            'manufacturer_model_id' => $model?->id,
        ];
    }

    public function hasAssets(Manufacturer $manufacturer): bool
    {
        return Asset::withoutDepartmentScope()
            ->where('manufacturer_id', $manufacturer->id)
            ->exists();
    }

    public function deleteWithoutReassignment(Manufacturer $manufacturer): void
    {
        if ($this->hasAssets($manufacturer)) {
            throw ValidationException::withMessages([
                'replacement_manufacturer_id' => 'Choose a replacement manufacturer for models and assets.',
            ]);
        }

        $manufacturer->delete();
    }

    public function deleteManufacturer(Manufacturer $manufacturer, Manufacturer $replacement): void
    {
        if ($manufacturer->is($replacement)) {
            throw new InvalidArgumentException('Replacement manufacturer must be different.');
        }

        DB::transaction(function () use ($manufacturer, $replacement): void {
            $models = ManufacturerModel::query()
                ->where('manufacturer_id', $manufacturer->id)
                ->get();

            foreach ($models as $model) {
                $target = $this->findOrCreateModelUnder($replacement, $model->name);

                Asset::query()
                    ->where('manufacturer_model_id', $model->id)
                    ->update([
                        'manufacturer_id' => $replacement->id,
                        'manufacturer_model_id' => $target->id,
                    ]);

                if ($target->isNot($model)) {
                    ManufacturerModelAlias::recordMerge($model, $target);
                    $model->delete();
                }
            }

            Asset::query()
                ->where('manufacturer_id', $manufacturer->id)
                ->update([
                    'manufacturer_id' => $replacement->id,
                ]);

            ManufacturerAlias::recordMerge($manufacturer, $replacement);
            $manufacturer->delete();
        });
    }

    /**
     * Drop a manufacturer and/or model once no asset, spare, or repair procedure still uses it.
     * Asset and spare counts ignore the acting user's department scope.
     */
    public function releaseUnusedAssignments(?int $manufacturerId, ?int $manufacturerModelId): void
    {
        if ($manufacturerModelId !== null) {
            $this->deleteModelIfUnassigned($manufacturerModelId);
        }

        if ($manufacturerId !== null) {
            $this->deleteManufacturerIfUnassigned($manufacturerId);
        }
    }

    public function deleteModel(ManufacturerModel $model, ManufacturerModel $replacement): void
    {
        if ($model->is($replacement)) {
            throw new InvalidArgumentException('Replacement model must be different.');
        }

        DB::transaction(function () use ($model, $replacement): void {
            Asset::query()
                ->where('manufacturer_model_id', $model->id)
                ->update([
                    'manufacturer_id' => $replacement->manufacturer_id,
                    'manufacturer_model_id' => $replacement->id,
                ]);

            ManufacturerModelAlias::recordMerge($model, $replacement);
            $model->delete();
        });
    }

    /**
     * Turn a catalogue assignment into a manufacturer, or null when the model should stay unspecified.
     */
    public function manufacturerForModelAssignment(?string $manufacturerId, ?string $newName): ?Manufacturer
    {
        $manufacturerId = $this->nullableTrim($manufacturerId);

        if ($manufacturerId === null) {
            return null;
        }

        if ($manufacturerId === '__new__') {
            $manufacturer = $this->resolveManufacturer($newName);

            if ($manufacturer === null || $manufacturer->isUnspecified()) {
                throw ValidationException::withMessages([
                    'new_manufacturer' => 'Enter a manufacturer name.',
                ]);
            }

            return $manufacturer;
        }

        $manufacturer = Manufacturer::query()->find((int) $manufacturerId);

        if ($manufacturer === null || $manufacturer->isUnspecified()) {
            throw ValidationException::withMessages([
                'manufacturer_id' => 'Choose or add a manufacturer.',
            ]);
        }

        return $manufacturer;
    }

    /**
     * Move a model off (Unspecified) onto another manufacturer.
     * A matching model name under the target is kept, and this model is merged into it.
     */
    public function assignManufacturer(ManufacturerModel $model, Manufacturer $target, string $name): ManufacturerModel
    {
        $model->loadMissing('manufacturer');

        if ($model->manufacturer === null || ! $model->manufacturer->isUnspecified()) {
            throw ValidationException::withMessages([
                'manufacturer_id' => 'Only a model under (Unspecified) can be assigned a manufacturer.',
            ]);
        }

        if ($target->isUnspecified()) {
            throw ValidationException::withMessages([
                'manufacturer_id' => 'Choose or add a manufacturer.',
            ]);
        }

        $name = trim($name);
        $previousManufacturerId = (int) $model->manufacturer_id;

        return DB::transaction(function () use ($model, $target, $name, $previousManufacturerId): ManufacturerModel {
            $existing = $this->findModelUnder($target, $name);

            if ($existing !== null && $existing->isNot($model)) {
                $this->repointModelRecords($model, $target, $existing);
                ManufacturerModelAlias::recordMerge($model, $existing);
                $model->delete();
                $this->deleteUnspecifiedManufacturerIfEmpty($previousManufacturerId);

                return $existing;
            }

            $model->update([
                'name' => $name,
                'manufacturer_id' => $target->id,
            ]);
            $this->moveModelAliases($model, $target);
            $this->repointModelRecords($model, $target, $model);
            $this->deleteUnspecifiedManufacturerIfEmpty($previousManufacturerId);

            return $model;
        });
    }

    protected function findModelUnder(Manufacturer $manufacturer, string $name): ?ManufacturerModel
    {
        return ManufacturerModel::query()
            ->where('manufacturer_id', $manufacturer->id)
            ->where(function ($query) use ($name): void {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($name)]);
            })
            ->first();
    }

    protected function repointModelRecords(ManufacturerModel $from, Manufacturer $target, ManufacturerModel $to): void
    {
        Asset::withoutDepartmentScope()
            ->where('manufacturer_model_id', $from->id)
            ->update([
                'manufacturer_id' => $target->id,
                'manufacturer_model_id' => $to->id,
            ]);

        SparePart::withoutDepartmentScope()
            ->where('manufacturer_model_id', $from->id)
            ->update([
                'manufacturer_id' => $target->id,
                'manufacturer_model_id' => $to->id,
            ]);

        if ($from->is($to)) {
            return;
        }

        $this->repointCompatibleSpares($from, $to);

        RepairProcedure::query()
            ->where('manufacturer_model_id', $from->id)
            ->update([
                'manufacturer_model_id' => $to->id,
            ]);
    }

    protected function repointCompatibleSpares(ManufacturerModel $from, ManufacturerModel $to): void
    {
        $rows = DB::table('spare_part_models')
            ->where('manufacturer_model_id', $from->id)
            ->get();

        foreach ($rows as $row) {
            $alreadyLinked = DB::table('spare_part_models')
                ->where('spare_part_id', $row->spare_part_id)
                ->where('manufacturer_model_id', $to->id)
                ->exists();

            if ($alreadyLinked) {
                DB::table('spare_part_models')->where('id', $row->id)->delete();

                continue;
            }

            DB::table('spare_part_models')->where('id', $row->id)->update([
                'manufacturer_model_id' => $to->id,
                'updated_at' => now(),
            ]);
        }
    }

    protected function moveModelAliases(ManufacturerModel $model, Manufacturer $target): void
    {
        $aliases = ManufacturerModelAlias::query()
            ->where('manufacturer_model_id', $model->id)
            ->get();

        foreach ($aliases as $alias) {
            $conflict = ManufacturerModelAlias::query()
                ->where('manufacturer_id', $target->id)
                ->where('former_name', $alias->former_name)
                ->whereKeyNot($alias->id)
                ->exists();

            if ($conflict) {
                $alias->delete();

                continue;
            }

            $alias->update(['manufacturer_id' => $target->id]);
        }
    }

    protected function deleteUnspecifiedManufacturerIfEmpty(int $manufacturerId): void
    {
        $manufacturer = Manufacturer::query()->withCount('models')->find($manufacturerId);

        if ($manufacturer === null || ! $manufacturer->isUnspecified() || $manufacturer->models_count > 0) {
            return;
        }

        $this->deleteManufacturerIfUnassigned($manufacturerId);
    }

    protected function deleteModelIfUnassigned(int $modelId): void
    {
        $model = ManufacturerModel::query()->find($modelId);

        if ($model === null || $this->modelStillReferenced($model)) {
            return;
        }

        $manufacturerId = (int) $model->manufacturer_id;
        $model->delete();
        $this->deleteManufacturerIfUnassigned($manufacturerId);
    }

    protected function deleteManufacturerIfUnassigned(int $manufacturerId): void
    {
        $manufacturer = Manufacturer::query()->find($manufacturerId);

        if ($manufacturer === null || $this->manufacturerStillReferenced($manufacturer)) {
            return;
        }

        $manufacturer->delete();
    }

    protected function modelStillReferenced(ManufacturerModel $model): bool
    {
        if (Asset::withoutDepartmentScope()->where('manufacturer_model_id', $model->id)->exists()) {
            return true;
        }

        if (SparePart::withoutDepartmentScope()->where('manufacturer_model_id', $model->id)->exists()) {
            return true;
        }

        if (DB::table('spare_part_models')->where('manufacturer_model_id', $model->id)->exists()) {
            return true;
        }

        return RepairProcedure::query()->where('manufacturer_model_id', $model->id)->exists();
    }

    protected function manufacturerStillReferenced(Manufacturer $manufacturer): bool
    {
        if (Asset::withoutDepartmentScope()->where('manufacturer_id', $manufacturer->id)->exists()) {
            return true;
        }

        if (SparePart::withoutDepartmentScope()->where('manufacturer_id', $manufacturer->id)->exists()) {
            return true;
        }

        $modelIds = $manufacturer->models()->pluck('id');

        if ($modelIds->isEmpty()) {
            return false;
        }

        if (Asset::withoutDepartmentScope()->whereIn('manufacturer_model_id', $modelIds)->exists()) {
            return true;
        }

        if (SparePart::withoutDepartmentScope()->whereIn('manufacturer_model_id', $modelIds)->exists()) {
            return true;
        }

        if (DB::table('spare_part_models')->whereIn('manufacturer_model_id', $modelIds)->exists()) {
            return true;
        }

        return RepairProcedure::query()->whereIn('manufacturer_model_id', $modelIds)->exists();
    }

    protected function findOrCreateModelUnder(Manufacturer $manufacturer, string $name): ManufacturerModel
    {
        $existing = $this->findModelUnder($manufacturer, $name);

        if ($existing) {
            return $existing;
        }

        return ManufacturerModel::create([
            'manufacturer_id' => $manufacturer->id,
            'name' => $name,
        ]);
    }

    protected function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
