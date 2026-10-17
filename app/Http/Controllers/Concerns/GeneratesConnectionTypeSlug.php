<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ConnectionType;
use Illuminate\Support\Str;

trait GeneratesConnectionTypeSlug
{
    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'connection-type';
        $slug = $base;
        $i = 2;

        while (ConnectionType::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
