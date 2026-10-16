<?php

namespace Database\Seeders;

use App\Enums\NotificationEventKey;
use App\Models\CustomFieldDefinition;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Services\NotificationFieldCatalog;
use Illuminate\Database\Seeder;

class EmailIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $helpdesk = NotificationRecipient::query()->firstOrCreate(
            ['email' => 'helpdesk@example.com'],
            [
                'name' => 'IT Helpdesk',
                'is_active' => true,
                'role_tag' => 'it_helpdesk',
                'notes' => 'Default IT helpdesk recipient — update the email address before enabling mail.',
            ]
        );

        $catalog = app(NotificationFieldCatalog::class);
        $selected = ['asset.name', 'asset.mac_address', 'asset.url'];
        $required = ['asset.name'];

        $macCustom = CustomFieldDefinition::query()
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->get()
            ->filter(fn (CustomFieldDefinition $definition): bool => $catalog->looksLikeMac(
                'custom.'.$definition->slug,
                $definition->name
            ));

        foreach ($macCustom as $definition) {
            $key = 'custom.'.$definition->slug;
            $selected[] = $key;
        }

        $selected = array_values(array_unique($selected));

        // Require asset.name plus at least one MAC-bearing field (OR group at dispatch time).
        $macKeys = array_values(array_filter(
            $selected,
            fn (string $key): bool => $key === 'asset.mac_address' || str_starts_with($key, 'custom.')
        ));
        $required = array_values(array_unique(array_merge(
            $required,
            $macKeys === [] ? ['asset.mac_address'] : $macKeys
        )));

        $macBody = "Please register MAC address details for {{asset.name}}.\n\nCore MAC: {{asset.mac_address}}\n";
        foreach ($macCustom as $definition) {
            $macBody .= $definition->name.': {{custom.'.$definition->slug."}}\n";
        }
        $macBody .= "\nAsset link: {{asset.url}}\n";

        $template = NotificationTemplate::query()->updateOrCreate(
            ['event_key' => NotificationEventKey::AssetMacOnboarding->value],
            [
                'name' => 'MAC onboarding',
                'subject' => 'MAC registration: {{asset.name}}',
                'body' => $macBody,
                'is_active' => true,
                'selected_fields' => $selected,
            ]
        );

        $template->recipients()->sync([
            $helpdesk->id => ['required_fields' => $required],
        ]);

        $defaults = [
            NotificationEventKey::MaintenanceLogCreated->value => [
                'name' => 'Maintenance log created',
                'subject' => 'Maintenance log: {{asset.name}}',
                'body' => "A maintenance log was added for {{asset.name}}.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.url'],
            ],
            NotificationEventKey::MaintenanceFaultStatusChanged->value => [
                'name' => 'Fault status changed',
                'subject' => 'Fault status update: {{asset.name}}',
                'body' => "Asset {{asset.name}} status changed via a Fault log.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.status', 'asset.url'],
            ],
            NotificationEventKey::InboundMatchPending->value => [
                'name' => 'Inbound match pending',
                'subject' => 'Inbound email pending review: {{asset.name}}',
                'body' => "An inbound email matched asset {{asset.name}} and is waiting for review.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.url'],
            ],
            NotificationEventKey::InboundMatchApplied->value => [
                'name' => 'Inbound match applied',
                'subject' => 'Inbound email applied: {{asset.name}}',
                'body' => "An inbound email was applied to the maintenance log for {{asset.name}}.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.url'],
            ],
            NotificationEventKey::InboundMatchRejected->value => [
                'name' => 'Inbound match rejected',
                'subject' => 'Inbound email rejected: {{asset.name}}',
                'body' => "An inbound email match for {{asset.name}} was rejected.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.url'],
            ],
            NotificationEventKey::RepairStarted->value => [
                'name' => 'Repair started',
                'subject' => 'Repair started: {{asset.name}}',
                'body' => "A repair was started for {{asset.name}}.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.url'],
            ],
            NotificationEventKey::RepairCompleted->value => [
                'name' => 'Repair completed',
                'subject' => 'Repair completed: {{asset.name}}',
                'body' => "A repair was completed for {{asset.name}}.\n\nOpen: {{asset.url}}",
                'selected_fields' => ['asset.name', 'asset.url'],
            ],
        ];

        foreach ($defaults as $eventKey => $payload) {
            $row = NotificationTemplate::query()->firstOrCreate(
                ['event_key' => $eventKey],
                array_merge($payload, ['is_active' => false])
            );

            if ($row->recipients()->count() === 0) {
                $row->recipients()->sync([
                    $helpdesk->id => ['required_fields' => ['asset.name']],
                ]);
            }
        }
    }
}
