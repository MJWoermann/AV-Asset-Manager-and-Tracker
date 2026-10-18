<?php

namespace App\Support;

use Illuminate\Http\Request;

class RequestDates
{
    public static function ymd(Request $request, string $key): ?string
    {
        $value = trim($request->string($key)->toString());

        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
