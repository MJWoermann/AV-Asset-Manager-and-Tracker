<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DashboardModules;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardModulePreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function scanner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('scanner');

        return $user;
    }

    public function test_guest_cannot_update_dashboard_modules(): void
    {
        $this->patch(route('preferences.dashboard-modules'), [
            'order' => DashboardModules::keys(),
            'enabled' => [DashboardModules::QUICK_LINKS],
        ])->assertRedirect(route('login'));
    }

    public function test_user_can_save_dashboard_module_order_and_visibility(): void
    {
        $user = $this->scanner();
        $available = DashboardModules::availableFor($user);

        $order = [
            DashboardModules::TEST_TAG_EXPIRY,
            DashboardModules::WARRANTY_EXPIRY,
            DashboardModules::QUICK_LINKS,
            ...array_values(array_diff($available, [
                DashboardModules::TEST_TAG_EXPIRY,
                DashboardModules::WARRANTY_EXPIRY,
                DashboardModules::QUICK_LINKS,
            ])),
        ];

        $response = $this->actingAs($user)->from(route('dashboard'))->patch(route('preferences.dashboard-modules'), [
            'order' => $order,
            'enabled' => [
                DashboardModules::TEST_TAG_EXPIRY,
                DashboardModules::WARRANTY_EXPIRY,
            ],
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status', 'Dashboard modules saved.');

        $user->refresh();

        $this->assertSame(
            [
                'order' => $order,
                'enabled' => [
                    DashboardModules::TEST_TAG_EXPIRY,
                    DashboardModules::WARRANTY_EXPIRY,
                ],
            ],
            $user->preferences[DashboardModules::PREFERENCE_KEY]
        );

        $modules = $user->dashboardModules();
        $this->assertSame(DashboardModules::TEST_TAG_EXPIRY, $modules[0]['id']);
        $this->assertTrue($modules[0]['enabled']);
        $this->assertSame(DashboardModules::WARRANTY_EXPIRY, $modules[1]['id']);
        $this->assertTrue($modules[1]['enabled']);

        $quickLinks = collect($modules)->firstWhere('id', DashboardModules::QUICK_LINKS);
        $this->assertNotNull($quickLinks);
        $this->assertFalse($quickLinks['enabled']);
    }

    public function test_dashboard_hides_disabled_modules_and_respects_order(): void
    {
        $user = $this->scanner();
        $available = DashboardModules::availableFor($user);

        $user->preferences = [
            DashboardModules::PREFERENCE_KEY => [
                'order' => [
                    DashboardModules::WARRANTY_EXPIRY,
                    DashboardModules::ASSETS_BY_STATUS,
                    ...array_values(array_diff($available, [
                        DashboardModules::WARRANTY_EXPIRY,
                        DashboardModules::ASSETS_BY_STATUS,
                    ])),
                ],
                'enabled' => [
                    DashboardModules::WARRANTY_EXPIRY,
                    DashboardModules::ASSETS_BY_STATUS,
                ],
            ],
        ];
        $user->save();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Customize modules');
        $response->assertDontSee('Scan to event', false);
        $response->assertDontSee('No recent changes.', false);

        $enabled = collect($response->viewData('modules'))
            ->filter(fn (array $module): bool => $module['enabled'])
            ->pluck('id')
            ->all();

        $this->assertSame(
            [DashboardModules::WARRANTY_EXPIRY, DashboardModules::ASSETS_BY_STATUS],
            $enabled
        );
        $this->assertNotEmpty($response->viewData('warrantyExpiry'));
        $this->assertNotEmpty($response->viewData('byStatus'));
        $this->assertSame([], $response->viewData('byType'));
        $this->assertTrue($response->viewData('latestChanges')->isEmpty());
    }

    public function test_scanner_cannot_enable_inbound_emails_module(): void
    {
        $user = $this->scanner();

        $response = $this->actingAs($user)->from(route('dashboard'))->patch(route('preferences.dashboard-modules'), [
            'order' => [DashboardModules::INBOUND_EMAILS, DashboardModules::QUICK_LINKS],
            'enabled' => [DashboardModules::INBOUND_EMAILS],
        ]);

        $response->assertSessionHasErrors('order.0');
    }

    public function test_consumables_module_not_available_without_stock_feature(): void
    {
        $user = $this->scanner();
        $available = DashboardModules::availableFor($user);

        $this->assertNotContains(DashboardModules::CONSUMABLES_STATS, $available);

        $user->preferences = [
            'features' => [
                'stock_consumables' => true,
            ],
        ];
        $user->save();

        $availableWithFeature = DashboardModules::availableFor($user->fresh());
        $this->assertContains(DashboardModules::CONSUMABLES_STATS, $availableWithFeature);
    }
}
