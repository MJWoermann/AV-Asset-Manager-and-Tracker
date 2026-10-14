<?php

namespace Database\Factories;

use App\Enums\InboundEmailActionStatus;
use App\Enums\MaintenanceLogType;
use App\Models\Asset;
use App\Models\InboundEmailAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InboundEmailAction>
 */
class InboundEmailActionFactory extends Factory
{
    protected $model = InboundEmailAction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'status' => InboundEmailActionStatus::Pending,
            'source_message_id' => fake()->uuid(),
            'from_address' => fake()->safeEmail(),
            'subject' => 'Fault report',
            'snippet' => 'Device is offline',
            'raw_headers' => [],
            'asset_id' => Asset::factory(),
            'proposed_payload' => [
                'type' => MaintenanceLogType::Note->value,
                'summary' => 'Email: Fault report',
                'body' => 'Device is offline',
                'source' => 'email',
            ],
        ];
    }
}
