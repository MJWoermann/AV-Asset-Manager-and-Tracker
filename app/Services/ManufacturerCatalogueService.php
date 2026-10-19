<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Manufacturer;
use App\Models\ManufacturerAlias;
use App\Models\ManufacturerModel;
use App\Models\ManufacturerModelAlias;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            $manufacturerName = '(Unspecified)';
        }

        $manufacturer = $this->resolveManufacturer($manufacturerName);
        $model = $this->resolveModel($manufacturer, $modelName);

        return [
            'manufacturer_id' => $manufacturer?->id,
            'manufacturer_model_id' => $model?->id,
        ];
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

    protected function findOrCreateModelUnder(Manufacturer $manufacturer, string $name): ManufacturerModel
    {
        $existing = ManufacturerModel::query()
            ->where('manufacturer_id', $manufacturer->id)
            ->where(function ($query) use ($name) {
                $query->where('name', $name)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($name)]);
            })
            ->first();

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
