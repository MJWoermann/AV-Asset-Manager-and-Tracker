<?php

namespace Database\Factories;

use App\Enums\NotificationEventKey;
use App\Models\Asset;
use App\Models\OutboundEmailThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutboundEmailThread>
 */
class OutboundEmailThreadFactory extends Factory
{
    protected $model = OutboundEmailThread::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'event_key' => NotificationEventKey::AssetMacOnboarding->value,
            'provider_message_id' => '<'.fake()->uuid().'@example.test>',
            'conversation_id' => null,
            'subject' => 'MAC registration',
            'to_addresses' => ['helpdesk@example.com'],
            'sent_by' => null,
        ];
    }
}
