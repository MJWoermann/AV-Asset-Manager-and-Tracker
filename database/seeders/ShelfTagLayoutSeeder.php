<?php

namespace Database\Seeders;

use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Support\ShelfTagPaperPresets;
use Database\Factories\ShelfTagLayoutFactory;
use Illuminate\Database\Seeder;

class ShelfTagLayoutSeeder extends Seeder
{
    public function run(): void
    {
        if (ShelfTagLayout::query()->exists()) {
            return;
        }

        $creator = User::query()->orderBy('id')->first();

        ShelfTagLayout::query()->create([
            'name' => '62 mm name + barcode + FMI',
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => 62.0,
            'height_mm' => 40.0,
            'orientation' => 'portrait',
            'elements' => ShelfTagLayoutFactory::sampleElements(),
            'logo_path' => null,
            'created_by' => $creator?->id,
        ]);
    }
}
