<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\FileLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileLink>
 */
class FileLinkFactory extends Factory
{
    protected $model = FileLink::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'file_id' => File::factory(),
            'note' => null,
        ];
    }
}
