<?php

namespace Database\Factories;

use App\Enums\NotificationEventKey;
use App\Models\NotificationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationTemplate>
 */
class NotificationTemplateFactory extends Factory
{
    protected $model = NotificationTemplate::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_key' => NotificationEventKey::MaintenanceLogCreated->value,
            'name' => 'Maintenance log created',
            'subject' => 'Maintenance log for {{asset.name}}',
            'body' => "Asset: {{asset.name}}\nSummary: see maintenance tab.",
            'is_active' => true,
            'selected_fields' => ['asset.name', 'asset.status'],
        ];
    }

    public function macOnboarding(): static
    {
        return $this->state(fn (): array => [
            'event_key' => NotificationEventKey::AssetMacOnboarding->value,
            'name' => 'MAC onboarding',
            'subject' => 'MAC registration: {{asset.name}}',
            'body' => "Please register the following MAC address(es) for {{asset.name}}.\n\nCore MAC: {{asset.mac_address}}\nAsset: {{asset.url}}",
            'selected_fields' => ['asset.name', 'asset.mac_address', 'asset.url'],
        ]);
    }
}
