<?php

namespace App\Services;

use App\Enums\CheckoutPurposeBehavior;
use App\Models\AssetCheckout;
use App\Models\CheckoutPurpose;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutPurposeService
{
    public function create(string $name): CheckoutPurpose
    {
        $name = trim($name);
        $sortOrder = (int) CheckoutPurpose::query()->max('sort_order') + 1;

        return CheckoutPurpose::query()->create([
            'slug' => $this->uniqueSlug($name),
            'name' => $name,
            'behavior' => CheckoutPurposeBehavior::OnEvent,
            'is_system' => false,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    public function update(CheckoutPurpose $purpose, string $name, bool $active): CheckoutPurpose
    {
        $name = trim($name);

        if (! $active && ! $purpose->canDeactivate()) {
            throw ValidationException::withMessages([
                'is_active' => 'Hire and Repair stay in the checkout list.',
            ]);
        }

        $purpose->update([
            'name' => $name,
            'is_active' => $purpose->canDeactivate() ? $active : true,
        ]);

        return $purpose->refresh();
    }

    public function delete(CheckoutPurpose $purpose): void
    {
        if (! $purpose->canDelete()) {
            throw ValidationException::withMessages([
                'purpose' => 'Built-in checkout options cannot be deleted.',
            ]);
        }

        $used = AssetCheckout::withoutDepartmentScope()
            ->where('purpose', $purpose->slug)
            ->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'purpose' => 'This option is used on existing checkouts. Deactivate it instead.',
            ]);
        }

        $purpose->delete();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $base = $base === '' ? '' : substr($base, 0, 32);

        if ($base === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a name using letters or numbers.',
            ]);
        }

        $slug = $base;
        $suffix = 2;

        while (CheckoutPurpose::query()->where('slug', $slug)->exists()) {
            $extra = '-'.$suffix;
            $slug = substr($base, 0, 32 - strlen($extra)).$extra;
            $suffix++;

            if ($suffix > 99) {
                throw ValidationException::withMessages([
                    'name' => 'Choose a different name. This one is too similar to an existing option.',
                ]);
            }
        }

        return $slug;
    }
}
