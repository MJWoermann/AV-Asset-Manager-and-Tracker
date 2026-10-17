<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ExportRowLimiter
{
    /**
     * Ensure a query will not exceed the configured export row cap.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function constrain(Builder $query, string $label = 'export'): Builder
    {
        $max = PerformanceLimits::int('export_max_rows');
        $count = (clone $query)->count();

        if ($count > $max) {
            throw ValidationException::withMessages([
                'export' => sprintf(
                    'This %s has %d rows; the export limit is %d. Narrow the selection or raise the limit (%s).',
                    $label,
                    $count,
                    $max,
                    PerformanceLimits::raiseHint('export_max_rows'),
                ),
            ]);
        }

        return $query;
    }
}
