<?php

namespace Tests\Feature;

use App\Enums\NotificationEventKey;
use App\Mail\NotificationTemplateMail;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\NotificationRecipient;
use App\Models\NotificationTemplate;
use App\Models\User;
use App\Support\EmailIntegrationSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailDigestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        config([
            'mail.default' => 'array',
            'mail.from.address' => 'hello@example.com',
            'mail.from.name' => 'AV Asset Manager',
        ]);
    }

    protected function withEmailFeature(User $user): User
    {
        return $this->enableFeature($user, 'email_integration');
    }

    protected function admin(): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('admin');

        return $this->withEmailFeature($user);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function enableMailSettings(array $overrides = []): void
    {
        EmailIntegrationSettings::put(array_merge([
            'enabled' => true,
            'driver' => 'smtp',
            'from_name' => 'AV Assets',
            'from_address' => 'noreply@example.com',
            'poll_interval_minutes' => 5,
            'inbound_folder' => 'INBOX',
        ], $overrides));
    }

    protected function seedDigestTemplate(NotificationEventKey $event, string $subject, string $body, array $selected = ['asset.name', 'asset.url']): NotificationTemplate
    {
        $recipient = NotificationRecipient::factory()->helpdesk()->create();

        $template = NotificationTemplate::factory()->create([
            'event_key' => $event->value,
            'name' => $event->label(),
            'subject' => $subject,
            'body' => $body,
            'is_active' => true,
            'selected_fields' => $selected,
        ]);

        $template->recipients()->sync([
            $recipient->id => ['required_fields' => ['asset.name']],
        ]);

        return $template->fresh('recipients');
    }

    public function test_digest_section_hidden_when_email_integration_disabled(): void
    {
        $user = $this->admin();
        EmailIntegrationSettings::put([
            'enabled' => false,
            'driver' => 'smtp',
            'from_name' => 'AV',
            'from_address' => 'av@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('admin.email.edit'))
            ->assertOk()
            ->assertDontSee('Common notifications / Digests', false);
    }

    public function test_digest_flags_not_saved_when_email_disabled(): void
    {
        $user = $this->admin();
        EmailIntegrationSettings::put([
            'enabled' => false,
            'driver' => 'smtp',
            'from_name' => 'AV',
            'from_address' => 'av@example.com',
            'digest_warranty_enabled' => false,
            'digest_days_ahead' => 30,
        ]);

        $this->actingAs($user)
            ->put(route('admin.email.update'), [
                'enabled' => '0',
                'driver' => 'smtp',
                'from_name' => 'AV',
                'from_address' => 'av@example.com',
                'inbound_folder' => 'INBOX',
                'poll_interval_minutes' => 5,
                'digest_warranty_enabled' => '1',
                'digest_test_tag_enabled' => '1',
                'digest_low_stock_enabled' => '1',
                'digest_days_ahead' => 14,
            ])
            ->assertRedirect(route('admin.email.edit'));

        $settings = EmailIntegrationSettings::get();
        $this->assertFalse($settings['enabled']);
        $this->assertFalse($settings['digest_warranty_enabled']);
        $this->assertFalse($settings['digest_test_tag_enabled']);
        $this->assertFalse($settings['digest_low_stock_enabled']);
        $this->assertSame(30, $settings['digest_days_ahead']);
    }

    public function test_digest_settings_visible_and_persist_when_email_enabled(): void
    {
        $user = $this->admin();
        $this->enableMailSettings();

        $this->actingAs($user)
            ->get(route('admin.email.edit'))
            ->assertOk()
            ->assertSee('Common notifications / Digests', false)
            ->assertSee('Warranty expiry digest', false)
            ->assertSee('Test &amp; Tag expiry digest', false)
            ->assertSee('Low stock digest', false);

        $this->actingAs($user)
            ->put(route('admin.email.update'), [
                'enabled' => '1',
                'driver' => 'smtp',
                'from_name' => 'AV',
                'from_address' => 'av@example.com',
                'inbound_folder' => 'INBOX',
                'poll_interval_minutes' => 5,
                'digest_warranty_enabled' => '1',
                'digest_test_tag_enabled' => '1',
                'digest_low_stock_enabled' => '0',
                'digest_days_ahead' => 21,
            ])
            ->assertRedirect(route('admin.email.edit'));

        $settings = EmailIntegrationSettings::get();
        $this->assertTrue($settings['digest_warranty_enabled']);
        $this->assertTrue($settings['digest_test_tag_enabled']);
        $this->assertFalse($settings['digest_low_stock_enabled']);
        $this->assertSame(21, $settings['digest_days_ahead']);
    }

    public function test_send_digests_command_sends_warranty_and_test_tag_mail(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::parse('2026-10-17'));

        $this->enableMailSettings([
            'digest_warranty_enabled' => true,
            'digest_test_tag_enabled' => true,
            'digest_low_stock_enabled' => false,
            'digest_days_ahead' => 30,
        ]);

        $this->seedDigestTemplate(
            NotificationEventKey::WarrantyExpiryDigest,
            'Warranty: {{asset.name}}',
            "Expires {{asset.warranty_expiry}}\n{{asset.url}}",
            ['asset.name', 'asset.warranty_expiry', 'asset.url']
        );
        $this->seedDigestTemplate(
            NotificationEventKey::TestTagExpiryDigest,
            'TestTag: {{asset.name}}',
            "Expires {{asset.test_tag_expiry}}\n{{asset.url}}",
            ['asset.name', 'asset.test_tag_expiry', 'asset.url']
        );

        $itemType = ItemType::factory()->create();
        $warrantyAsset = Asset::factory()->create([
            'name' => 'Warranty Soon',
            'item_type_id' => $itemType->id,
            'warranty_expiry' => Carbon::parse('2026-10-30'),
        ]);
        $testTagAsset = Asset::factory()->create([
            'name' => 'Tag Soon',
            'item_type_id' => $itemType->id,
            'test_tag_expiry' => Carbon::parse('2026-10-20'),
        ]);
        Asset::factory()->create([
            'name' => 'Warranty Far',
            'item_type_id' => $itemType->id,
            'warranty_expiry' => Carbon::parse('2027-01-01'),
        ]);

        Artisan::call('email:send-digests');

        Mail::assertSent(NotificationTemplateMail::class, 2);
        Mail::assertSent(NotificationTemplateMail::class, function (NotificationTemplateMail $mail) use ($warrantyAsset): bool {
            return str_contains($mail->emailSubject, $warrantyAsset->name)
                && str_contains($mail->bodyHtml, '2026-10-30');
        });
        Mail::assertSent(NotificationTemplateMail::class, function (NotificationTemplateMail $mail) use ($testTagAsset): bool {
            return str_contains($mail->emailSubject, $testTagAsset->name)
                && str_contains($mail->bodyHtml, '2026-10-20');
        });

        $this->assertDatabaseHas('outbound_email_threads', [
            'asset_id' => $warrantyAsset->id,
            'event_key' => NotificationEventKey::WarrantyExpiryDigest->value,
        ]);
        $this->assertDatabaseHas('outbound_email_threads', [
            'asset_id' => $testTagAsset->id,
            'event_key' => NotificationEventKey::TestTagExpiryDigest->value,
        ]);

        Carbon::setTestNow();
    }

    public function test_send_digests_command_sends_low_stock_mail(): void
    {
        Mail::fake();

        $this->enableMailSettings([
            'digest_warranty_enabled' => false,
            'digest_test_tag_enabled' => false,
            'digest_low_stock_enabled' => true,
        ]);

        $this->seedDigestTemplate(
            NotificationEventKey::LowStockDigest,
            'Low stock: {{asset.name}}',
            "{{asset.name}} qty {{asset.quantity}} min {{asset.min_stock_level}}\n{{asset.url}}",
            ['asset.name', 'asset.quantity', 'asset.min_stock_level', 'asset.url']
        );

        $tracked = ItemType::factory()->stockTracked()->create();
        $untracked = ItemType::factory()->create();

        $low = Asset::factory()->create([
            'name' => 'Cable Ties',
            'item_type_id' => $tracked->id,
            'quantity' => 2,
            'min_stock_level' => 5,
        ]);
        Asset::factory()->create([
            'name' => 'Plenty In Stock',
            'item_type_id' => $tracked->id,
            'quantity' => 20,
            'min_stock_level' => 5,
        ]);
        Asset::factory()->create([
            'name' => 'Not Stock Tracked',
            'item_type_id' => $untracked->id,
            'quantity' => 0,
            'min_stock_level' => 5,
        ]);

        Artisan::call('email:send-digests');

        Mail::assertSent(NotificationTemplateMail::class, 1);
        Mail::assertSent(NotificationTemplateMail::class, function (NotificationTemplateMail $mail) use ($low): bool {
            return str_contains($mail->emailSubject, $low->name)
                && str_contains($mail->bodyHtml, 'qty 2')
                && str_contains($mail->bodyHtml, 'min 5');
        });

        $this->assertDatabaseHas('outbound_email_threads', [
            'asset_id' => $low->id,
            'event_key' => NotificationEventKey::LowStockDigest->value,
        ]);
    }

    public function test_send_digests_command_noop_when_email_disabled(): void
    {
        Mail::fake();

        EmailIntegrationSettings::put([
            'enabled' => false,
            'driver' => 'smtp',
            'digest_warranty_enabled' => true,
            'digest_days_ahead' => 30,
        ]);

        Asset::factory()->create([
            'warranty_expiry' => now()->addDays(5),
        ]);

        Artisan::call('email:send-digests');

        Mail::assertNothingSent();
    }
}
