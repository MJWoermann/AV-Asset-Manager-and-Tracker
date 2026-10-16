<?php

namespace App\Models\Concerns;

use App\Models\Department;
use App\Support\DepartmentVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @mixin Model
 */
trait BelongsToDepartment
{
    public static function bootBelongsToDepartment(): void
    {
        static::addGlobalScope('department', function (Builder $builder): void {
            $user = Auth::user();

            if ($user === null) {
                return;
            }

            $ids = $user->departmentScopeIds();

            if ($ids === null) {
                return;
            }

            $column = $builder->getModel()->getTable().'.department_id';

            if ($ids === []) {
                $builder->whereRaw('0 = 1');

                return;
            }

            $builder->whereIn($column, $ids);
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('department_id') !== null) {
                return;
            }

            $user = Auth::user();

            if ($user === null) {
                $defaultId = Department::query()->where('slug', 'general')->value('id');

                if ($defaultId !== null) {
                    $model->setAttribute('department_id', $defaultId);
                }

                return;
            }

            $activeId = session(DepartmentVisibility::SESSION_KEY);

            if ($activeId === null || ! $user->canUseDepartment((int) $activeId)) {
                $activeId = $user->defaultDepartmentIdForCreate();
            }

            if ($activeId === null) {
                throw ValidationException::withMessages([
                    'department_id' => 'Select an active department before creating records.',
                ]);
            }

            $model->setAttribute('department_id', (int) $activeId);
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return Builder<static>
     */
    public static function withoutDepartmentScope(): Builder
    {
        return static::withoutGlobalScope('department');
    }
}
