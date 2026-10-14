<?php

namespace Database\Factories;

use App\Enums\FileCategory;
use App\Enums\FileSourceType;
use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'category' => FileCategory::Manual,
            'source_type' => FileSourceType::Upload,
            'disk' => 'local',
            'path' => 'attachments/test/'.fake()->uuid().'.pdf',
            'content_hash' => hash('sha256', fake()->uuid()),
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'original_filename' => 'manual.pdf',
            'external_url' => null,
            'thumbnail_path' => null,
            'uploaded_by' => User::factory(),
        ];
    }

    public function url(): static
    {
        return $this->state(fn (): array => [
            'source_type' => FileSourceType::Url,
            'disk' => null,
            'path' => null,
            'content_hash' => null,
            'mime_type' => null,
            'size_bytes' => null,
            'original_filename' => null,
            'external_url' => 'https://example.com/manual.pdf',
            'thumbnail_path' => null,
        ]);
    }
}
