<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\InboundEmailActionStatus;
use App\Enums\MaintenanceLogType;
use App\Enums\NotificationEventKey;
use App\Mail\NotificationTemplateMail;
use App\Models\Asset;
use App\Models\AssetMaintenanceLog;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\InboundEmailAction;
use App\Models\InboundEmailRule;
use App\Models\ItemType;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Models\OutboundEmailThread;
use App\Models\User;
use App\Services\InboundEmailService;
use App\Support\EmailIntegrationSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailIntegrationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        config(['mail.default' => 'array']);
    }

    protected function withEmailFeature(User $user): User
    {
        $user->preferences = [
            'features' => [
                'email_integration' => true,
            ],
        ];
        $user->save();

        return $user->fresh();
    }

    protected function admin(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('admin');

        return $feature ? $this->withEmailFeature($user) : $user;
    }

    protected function manager(bool $feature = true): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $feature ? $this->withEmailFeature($user) : $user;
    }

    protected function enableMailSettings(): void
    {
        EmailIntegrationSettings::put([
            'enabled' => true,
            'driver' => 'smtp',
            'from_name' => 'AV Assets',
            'from_address' => 'noreply@example.com',
            'poll_interval_minutes' => 5,
            'inbound_folder' => 'INBOX',
        ]);
    }

    /**
     * @param  list<string>  $selected
     * @param  list<string>  $required
     */
    protected function seedMacTemplate(array $selected = ['asset.name', 'asset.mac_address', 'asset.url'], array $required = ['asset.name', 'asset.mac_address']): NotificationTemplate
    {
        $recipient = NotificationRecipient::factory()->helpdesk()->create();

        $template = NotificationTemplate::factory()->macOnboarding()->create([
            'selected_fields' => $selected,
            'body' => "Name: {{asset.name}}\nMAC: {{asset.mac_address}}\nCustom: {{custom.mac_address_2}}\nURL: {{asset.url}}",
        ]);

        $template->recipients()->sync([
            $recipient->id => ['required_fields' => $required],
        ]);

        return $template->fresh('recipients');
    }

    public function test_flag_off_returns_404_for_email_routes_and_hides_mac_checkbox(): void
    {
        $user = $this->admin(feature: false);
        $this->enableMailSettings();
        $itemType = ItemType::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.email.edit'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('admin.email.inbound.index'))
            ->assertNotFound();

        $this->actingAs($user)
            ->get(route('assets.create'))
            ->assertOk()
            ->assertDontSee('Email IT helpdesk with MAC details', false);

        $asset = Asset::factory()->create(['item_type_id' => $itemType->id]);

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'maintenance']))
            ->assertOk()
            ->assertSee('Maintenance', false);
    }

    public function test_mac_onboarding_send_creates_thread_and_note(): void
    {
        Mail::fake();

        $user = $this->admin();
        $this->enableMailSettings();
        $this->seedMacTemplate();
        $itemType = ItemType::factory()->create();

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Switch A',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'notify_mac_onboarding' => '1',
        ]);

        $asset = Asset::query()->where('name', 'Switch A')->first();
        $this->assertNotNull($asset);

        $response->assertRedirect(route('assets.show', $asset));

        Mail::assertSent(NotificationTemplateMail::class, function (NotificationTemplateMail $mail): bool {
            return str_contains($mail->emailSubject, 'Switch A')
                && str_contains($mail->bodyHtml, 'AA:BB:CC:DD:EE:FF');
        });

        $this->assertDatabaseHas('outbound_email_threads', [
            'asset_id' => $asset->id,
            'event_key' => NotificationEventKey::AssetMacOnboarding->value,
        ]);

        $this->assertTrue(
            AssetMaintenanceLog::query()
                ->where('asset_id', $asset->id)
                ->where('type', MaintenanceLogType::Note->value)
                ->where('summary', 'like', 'MAC onboarding email sent%')
                ->exists()
        );
    }

    public function test_mac_onboarding_includes_custom_mac_field_value(): void
    {
        Mail::fake();

        $user = $this->admin();
        $this->enableMailSettings();

        $itemType = ItemType::factory()->create();
        $set = CustomFieldSet::query()->create([
            'name' => 'Network',
            'slug' => 'network',
        ]);
        $itemType->fieldSets()->attach($set->id);
        $definition = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $set->id,
            'name' => 'MAC Address 2',
            'slug' => 'mac_address_2',
            'type' => 'text',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        $this->seedMacTemplate(
            ['asset.name', 'asset.mac_address', 'custom.mac_address_2', 'asset.url'],
            ['asset.name', 'asset.mac_address', 'custom.mac_address_2']
        );

        $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Dual NIC',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'mac_address' => '11:22:33:44:55:66',
            'custom_fields' => [
                $definition->id => '77:88:99:AA:BB:CC',
            ],
            'notify_mac_onboarding' => '1',
        ])->assertRedirect();

        Mail::assertSent(NotificationTemplateMail::class, function (NotificationTemplateMail $mail): bool {
            return str_contains($mail->bodyHtml, '77:88:99:AA:BB:CC');
        });

        $asset = Asset::query()->where('name', 'Dual NIC')->first();
        $this->assertNotNull($asset);
        $this->assertDatabaseHas('asset_custom_field_values', [
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => '77:88:99:AA:BB:CC',
        ]);
    }

    public function test_mac_onboarding_skips_when_required_mac_missing(): void
    {
        Mail::fake();

        $user = $this->admin();
        $this->enableMailSettings();
        $this->seedMacTemplate();
        $itemType = ItemType::factory()->create();

        $response = $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'No MAC',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'notify_mac_onboarding' => '1',
        ]);

        $asset = Asset::query()->where('name', 'No MAC')->first();
        $this->assertNotNull($asset);
        $response->assertRedirect(route('assets.show', $asset));
        $response->assertSessionHas('warning');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('outbound_email_threads', 0);
    }

    public function test_inbound_thread_reply_auto_appends_note_and_dedupes(): void
    {
        $this->enableMailSettings();
        $asset = Asset::factory()->create();

        $thread = OutboundEmailThread::factory()->create([
            'asset_id' => $asset->id,
            'provider_message_id' => '<outbound-1@example.test>',
            'event_key' => NotificationEventKey::AssetMacOnboarding->value,
        ]);

        $service = app(InboundEmailService::class);

        $message = [
            'id' => '<reply-1@example.test>',
            'subject' => 'Re: MAC registration',
            'from' => 'helpdesk@example.com',
            'body' => 'Registered on DHCP.',
            'snippet' => 'Registered on DHCP.',
            'in_reply_to' => '<outbound-1@example.test>',
            'references' => ['<outbound-1@example.test>'],
            'conversation_id' => null,
            'headers' => [],
        ];

        $this->assertTrue($service->processMessage($message));
        $this->assertFalse($service->processMessage($message));

        $this->assertDatabaseHas('inbound_email_actions', [
            'source_message_id' => '<reply-1@example.test>',
            'status' => InboundEmailActionStatus::AutoApplied->value,
            'outbound_email_thread_id' => $thread->id,
            'asset_id' => $asset->id,
        ]);

        $this->assertEquals(1, AssetMaintenanceLog::query()
            ->where('asset_id', $asset->id)
            ->where('type', MaintenanceLogType::Note->value)
            ->where('summary', 'Email reply from helpdesk@example.com')
            ->count());
    }

    public function test_reviewed_inbound_rule_approve_creates_log(): void
    {
        $user = $this->manager();
        $this->enableMailSettings();
        $asset = Asset::factory()->create();

        $rule = InboundEmailRule::factory()->create([
            'require_review' => true,
            'log_type' => MaintenanceLogType::Fault,
            'match_conditions' => ['subject_contains' => 'outage'],
        ]);

        $action = InboundEmailAction::factory()->create([
            'status' => InboundEmailActionStatus::Pending,
            'asset_id' => $asset->id,
            'inbound_email_rule_id' => $rule->id,
            'proposed_payload' => [
                'type' => MaintenanceLogType::Fault->value,
                'summary' => 'Email: outage',
                'body' => 'Device offline',
                'source' => 'email',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('admin.email.inbound.apply', $action))
            ->assertRedirect();

        $action->refresh();
        $this->assertSame(InboundEmailActionStatus::Applied, $action->status);
        $this->assertNotNull($action->asset_maintenance_log_id);
        $this->assertDatabaseHas('asset_maintenance_logs', [
            'id' => $action->asset_maintenance_log_id,
            'type' => MaintenanceLogType::Fault->value,
            'summary' => 'Email: outage',
        ]);
    }

    public function test_template_required_fields_enforced_for_helpdesk_recipient(): void
    {
        Mail::fake();

        $user = $this->admin();
        $this->enableMailSettings();
        $this->seedMacTemplate(
            ['asset.name', 'custom.mac_address_2', 'asset.url'],
            ['asset.name', 'custom.mac_address_2']
        );

        $itemType = ItemType::factory()->create();
        $set = CustomFieldSet::query()->create(['name' => 'Net', 'slug' => 'net']);
        $itemType->fieldSets()->attach($set->id);
        $definition = CustomFieldDefinition::query()->create([
            'custom_field_set_id' => $set->id,
            'name' => 'MAC Address 2',
            'slug' => 'mac_address_2',
            'type' => 'text',
            'is_required' => false,
            'sort_order' => 1,
        ]);

        // Core MAC present but required custom MAC empty → no send.
        $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Needs custom MAC',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'mac_address' => 'AA:BB:CC:DD:EE:01',
            'custom_fields' => [$definition->id => ''],
            'notify_mac_onboarding' => '1',
        ])->assertSessionHas('warning');

        Mail::assertNothingSent();

        // Custom MAC provided → send.
        $this->actingAs($user)->post(route('assets.store'), [
            'name' => 'Has custom MAC',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available->value,
            'custom_fields' => [$definition->id => 'AA:BB:CC:DD:EE:02'],
            'notify_mac_onboarding' => '1',
        ])->assertSessionHasNoErrors();

        Mail::assertSent(NotificationTemplateMail::class);
    }

    public function test_admin_can_save_email_settings_when_flag_on(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->put(route('admin.email.update'), [
                'enabled' => '1',
                'driver' => 'gmail',
                'from_name' => 'AV',
                'from_address' => 'av@example.com',
                'inbound_folder' => 'INBOX',
                'poll_interval_minutes' => 10,
            ])
            ->assertRedirect(route('admin.email.edit'));

        $settings = EmailIntegrationSettings::get();
        $this->assertTrue($settings['enabled']);
        $this->assertSame('gmail', $settings['driver']);
        $this->assertSame('imap.gmail.com', $settings['imap_host']);
    }
}
