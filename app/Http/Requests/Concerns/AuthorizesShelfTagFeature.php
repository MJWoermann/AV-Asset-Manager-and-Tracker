<?php

namespace App\Http\Requests\Concerns;

trait AuthorizesShelfTagFeature
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->hasFeature('shelf_tag_generator')
            && $user->hasAnyRole(['admin', 'inventory_manager']);
    }
}
