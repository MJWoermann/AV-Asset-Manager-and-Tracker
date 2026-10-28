<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\CheckoutPurposeBehavior;
use App\Models\Asset;
use App\Models\AssetCheckout;
use App\Models\CheckoutPurpose;
use App\Models\Department;
use App\Models\Manufacturer;
use App\Models\ManufacturerModel;
use App\Models\Repair;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_from_checkout_holdings(): void
    {
        $this->get(route('reports.checkouts'))->assertRedirect(route('login'));
    }

    public function test_scanner_and_external_repairer_cannot_open_the_report_or_add_options(): void
    {
        $scanner = User::factory()->create();
        $scanner->assignRole('scanner');
        $repairer = User::factory()->create();
        $repairer->assignRole('external_repairer');

        $this->actingAs($scanner)->get(route('reports.checkouts'))->assertForbidden();
        $this->actingAs($scanner)->post(route('reports.checkouts.purposes.store'), [
            'name' => 'Loan',
        ])->assertForbidden();

        $this->actingAs($repairer)->get(route('reports.checkouts'))->assertForbidden();
        $this->actingAs($repairer)->post(route('reports.checkouts.purposes.store'), [
            'name' => 'Loan',
        ])->assertForbidden();
    }

    public function test_manager_sees_checkout_holdings_on_the_reports_hub(): void
    {
        $this->actingAs($this->manager())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Checkout holdings');
    }

    public function test_manager_can_add_a_purpose_and_check_out_without_a_repair(): void
    {
        $user = $this->manager();
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

        $this->actingAs($user)->post(route('reports.checkouts.purposes.store'), [
            'name' => 'Long term loan',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $purpose = CheckoutPurpose::query()->where('name', 'Long term loan')->first();
        $this->assertNotNull($purpose);
        $this->assertSame('long-term-loan', $purpose->slug);
        $this->assertSame(CheckoutPurposeBehavior::OnEvent, $purpose->behavior);
        $this->assertFalse($purpose->is_system);

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'checkout']))
            ->assertOk()
            ->assertSee('Long term loan');

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => $purpose->slug,
            'checked_out_to_name' => 'Loan Guest',
        ])->assertRedirect(route('assets.show', [$asset, 'tab' => 'checkout']));

        $asset->refresh();
        $checkout = AssetCheckout::query()->where('asset_id', $asset->id)->first();

        $this->assertNotNull($checkout);
        $this->assertSame($purpose->slug, $checkout->purpose);
        $this->assertNull($checkout->repair_id);
        $this->assertSame(AssetStatus::OnEvent, $asset->status);
        $this->assertSame(0, Repair::query()->count());
    }

    public function test_inactive_purpose_is_rejected_and_built_in_options_cannot_be_removed(): void
    {
        $user = $this->manager();
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);
        $purpose = CheckoutPurpose::factory()->create([
            'name' => 'Event loan',
            'slug' => 'event-loan',
        ]);

        $this->actingAs($user)->patch(route('reports.checkouts.purposes.update', $purpose), [
            'name' => 'Event loan',
            'is_active' => '0',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('assets.show', [$asset, 'tab' => 'checkout']))
            ->assertOk()
            ->assertDontSee('Event loan');

        $this->actingAs($user)->post(route('assets.checkouts.store', $asset), [
            'purpose' => 'event-loan',
            'checked_out_to_name' => 'Guest',
        ])->assertSessionHasErrors([
            'purpose' => 'Choose an active checkout option.',
        ]);

        $hire = CheckoutPurpose::query()->where('slug', 'hire')->firstOrFail();

        $this->actingAs($user)
            ->from(route('reports.checkouts'))
            ->patch(route('reports.checkouts.purposes.update', $hire), [
                'name' => 'Hire',
                'is_active' => '0',
            ])
            ->assertRedirect(route('reports.checkouts'))
            ->assertSessionHasErrors([
                'is_active' => 'Hire and Repair stay in the checkout list.',
            ]);

        $this->assertTrue($hire->refresh()->is_active);

        $this->actingAs($user)
            ->from(route('reports.checkouts'))
            ->delete(route('reports.checkouts.purposes.destroy', $hire))
            ->assertRedirect(route('reports.checkouts'))
            ->assertSessionHasErrors([
                'purpose' => 'Built-in checkout options cannot be deleted.',
            ]);

        $this->assertModelExists($hire);

        $this->actingAs($user)
            ->delete(route('reports.checkouts.purposes.destroy', $purpose))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($purpose);
    }

    public function test_used_custom_purpose_cannot_be_deleted(): void
    {
        $user = $this->manager();
        $asset = Asset::factory()->create([
            'status' => AssetStatus::OnEvent,
        ]);
        $purpose = CheckoutPurpose::factory()->create([
            'name' => 'Demo loan',
            'slug' => 'demo-loan',
        ]);

        AssetCheckout::factory()->create([
            'asset_id' => $asset->id,
            'department_id' => $asset->department_id,
            'checked_out_by_user_id' => $user->id,
            'purpose' => $purpose->slug,
            'checked_out_to_name' => 'Demo Guest',
        ]);

        $this->actingAs($user)
            ->from(route('reports.checkouts'))
            ->delete(route('reports.checkouts.purposes.destroy', $purpose))
            ->assertRedirect(route('reports.checkouts'))
            ->assertSessionHasErrors([
                'purpose' => 'This option is used on existing checkouts. Deactivate it instead.',
            ]);

        $this->assertModelExists($purpose);
    }

    public function test_model_summary_counts_available_and_checked_out_stock(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Widget-X',
        ]);

        Asset::factory()->count(2)->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::Available,
        ]);

        $checkedOut = Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::OnEvent,
            'name' => 'Widget on hire',
        ]);
        $this->openCheckout($checkedOut, $user, 'hire');

        Asset::factory()->create([
            'status' => AssetStatus::Available,
            'name' => 'Loose available',
        ]);
        $loose = Asset::factory()->create([
            'status' => AssetStatus::OnEvent,
            'name' => 'Loose checked out',
        ]);
        $this->openCheckout($loose, $user, 'hire');

        $otherDepartment = Department::factory()->create();
        $secret = Asset::factory()->create([
            'department_id' => $otherDepartment->id,
            'name' => 'Secret projector',
            'status' => AssetStatus::OnEvent,
        ]);
        AssetCheckout::factory()->create([
            'asset_id' => $secret->id,
            'department_id' => $otherDepartment->id,
            'checked_out_by_user_id' => $user->id,
            'purpose' => 'hire',
            'checked_out_to_name' => 'Other Dept',
        ]);

        $response = $this->actingAs($user)->get(route('reports.checkouts'));

        $response->assertOk()
            ->assertSee('Widget-X')
            ->assertSee('Export', false)
            ->assertSee('PDF', false)
            ->assertDontSee('Secret projector');
        $response->assertViewHas('modelRows', function (array $rows): bool {
            $widget = collect($rows)->firstWhere('model', 'Widget-X');
            $uncatalogued = collect($rows)->firstWhere('manufacturer', 'Uncatalogued');

            return $widget !== null
                && $widget['available'] === 2
                && $widget['checked_out'] === 1
                && $widget['in_register'] === 3
                && $uncatalogued !== null
                && $uncatalogued['available'] === 1
                && $uncatalogued['checked_out'] === 1;
        });
    }

    public function test_model_view_hides_models_with_nothing_checked_out(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme']);
        $idle = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Shelf-Idle',
        ]);
        $out = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Shelf-Out',
        ]);

        Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $idle->id,
            'status' => AssetStatus::Available,
        ]);
        $checkedOut = Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $out->id,
            'status' => AssetStatus::OnEvent,
        ]);
        $this->openCheckout($checkedOut, $user, 'hire');

        $hidden = $this->actingAs($user)->get(route('reports.checkouts'));

        $hidden->assertOk()
            ->assertSee('Expand all')
            ->assertSee('Collapse all')
            ->assertSee('>Shelf-Out</span>', false)
            ->assertDontSee('>Shelf-Idle</span>', false);
        $hidden->assertViewHas('modelGroups', function (array $groups): bool {
            $models = collect($groups)
                ->flatMap(fn (array $group): array => array_column($group['models'], 'model'))
                ->all();

            return $models === ['Shelf-Out'];
        });

        $shown = $this->actingAs($user)->get(route('reports.checkouts', ['checked_out_only' => '0']));

        $shown->assertOk()
            ->assertSee('>Shelf-Idle</span>', false)
            ->assertSee('>Shelf-Out</span>', false);
        $shown->assertViewHas('modelGroups', function (array $groups): bool {
            $models = collect($groups)
                ->flatMap(fn (array $group): array => array_column($group['models'], 'model'))
                ->sort()
                ->values()
                ->all();

            return count($groups) === 1
                && $groups[0]['name'] === 'Acme'
                && $models === ['Shelf-Idle', 'Shelf-Out'];
        });
    }

    public function test_purpose_filter_changes_only_the_checked_out_count(): void
    {
        $user = $this->manager();
        $purpose = CheckoutPurpose::factory()->create([
            'name' => 'Road case',
            'slug' => 'road-case',
        ]);
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Widget-Y',
        ]);

        Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::Available,
        ]);

        $hire = Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::OnEvent,
        ]);
        $custom = Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::OnEvent,
        ]);
        $this->openCheckout($hire, $user, 'hire');
        $this->openCheckout($custom, $user, $purpose->slug);

        $response = $this->actingAs($user)->get(route('reports.checkouts', [
            'purpose' => 'road-case',
        ]));

        $response->assertOk();
        $response->assertViewHas('modelRows', function (array $rows): bool {
            $widget = collect($rows)->firstWhere('model', 'Widget-Y');

            return $widget !== null
                && $widget['available'] === 1
                && $widget['checked_out'] === 1
                && $widget['in_register'] === 3;
        });
    }

    public function test_overdue_filter_limits_the_checked_out_count(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Widget-Due',
        ]);
        $overdue = Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::OnEvent,
        ]);
        $future = Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::OnEvent,
        ]);

        $this->openCheckout($overdue, $user, 'hire')->update([
            'expected_return_at' => now()->subDay(),
        ]);
        $this->openCheckout($future, $user, 'hire')->update([
            'expected_return_at' => now()->addWeek(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.checkouts', [
            'overdue' => '1',
        ]));

        $response->assertOk();
        $response->assertViewHas('modelRows', function (array $rows): bool {
            $widget = collect($rows)->firstWhere('model', 'Widget-Due');

            return $widget !== null
                && $widget['available'] === 0
                && $widget['checked_out'] === 1
                && $widget['in_register'] === 2;
        });
    }

    public function test_holder_view_groups_users_and_guests(): void
    {
        $user = $this->manager();
        $holder = User::factory()->create([
            'name' => 'Dana Holder',
            'email' => 'dana@example.com',
        ]);
        $userAsset = Asset::factory()->create([
            'name' => 'Desk phone',
            'status' => AssetStatus::OnEvent,
            'serial_number' => 'SN-DESK-1',
        ]);
        $guestAsset = Asset::factory()->create([
            'name' => 'Loan laptop',
            'status' => AssetStatus::OnEvent,
        ]);

        AssetCheckout::factory()->forUser($holder)->create([
            'asset_id' => $userAsset->id,
            'department_id' => $userAsset->department_id,
            'checked_out_by_user_id' => $user->id,
            'purpose' => 'hire',
        ]);
        AssetCheckout::factory()->create([
            'asset_id' => $guestAsset->id,
            'department_id' => $guestAsset->department_id,
            'checked_out_by_user_id' => $user->id,
            'purpose' => 'other',
            'checked_out_to_name' => 'Alex Guest',
            'checked_out_to_company' => 'North Hire',
            'checked_out_to_email' => 'alex@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('reports.checkouts', ['view' => 'holders']))
            ->assertOk()
            ->assertSee('Dana Holder')
            ->assertSee('dana@example.com')
            ->assertSee('Desk phone')
            ->assertSee('SN-DESK-1')
            ->assertSee('Alex Guest')
            ->assertSee('Loan laptop')
            ->assertSee('North Hire');
    }

    public function test_manager_can_export_the_model_summary(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Widget-Z',
        ]);
        Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('reports.checkouts.export', [
            'format' => 'csv',
            'view' => 'models',
        ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'checkouts-by-models',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString('Widget-Z', $response->streamedContent());
    }

    public function test_manager_can_export_the_model_summary_as_pdf(): void
    {
        $user = $this->manager();
        $manufacturer = Manufacturer::factory()->create(['name' => 'Acme']);
        $model = ManufacturerModel::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'name' => 'Widget-Pdf',
        ]);
        Asset::factory()->create([
            'manufacturer_id' => $manufacturer->id,
            'manufacturer_model_id' => $model->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('reports.checkouts.export', [
            'format' => 'pdf',
            'view' => 'models',
        ]));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString(
            'checkouts-by-models',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    protected function openCheckout(Asset $asset, User $by, string $purpose): AssetCheckout
    {
        return AssetCheckout::factory()->create([
            'asset_id' => $asset->id,
            'department_id' => $asset->department_id,
            'checked_out_by_user_id' => $by->id,
            'purpose' => $purpose,
            'checked_out_to_name' => 'Checkout Guest',
            'checked_out_to_email' => null,
            'checked_out_to_company' => null,
        ]);
    }
}
