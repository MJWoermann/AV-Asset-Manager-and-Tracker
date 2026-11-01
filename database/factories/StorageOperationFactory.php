<?php

namespace Database\Factories;

use App\Enums\StorageOperationStatus;
use App\Models\StorageOperation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageOperation>
 */
class StorageOperationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'command' => 'app:backup --disk=qnap --keep=7',
            'status' => StorageOperationStatus::Queued,
            'detail' => null,
            'user_id' => null,
            'started_at' => null,
            'finished_at' => null,
        ];
    }
}
