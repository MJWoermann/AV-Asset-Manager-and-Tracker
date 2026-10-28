<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\ItemType;
use App\Models\Repair;
use App\Models\User;
use App\Support\AssetTableColumns;
use App\Support\ScannableIdentifierFields;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Systematic page audit: scannable identifier fields and identifier search boxes
 * must expose fieldBarcodeScanner / x-scannable-* chrome. See
 * .cursor/skills/scannable-identifier-fields/SKILL.md.
 */
class ScannableIdentifierFieldsComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @param  array<string, bool>  $features
     */
    protected function withFeatures(User $user, array $features): User
    {
        $preferences = $user->preferences ?? [];
        $preferences['features'] = array_merge($preferences['features'] ?? [], $features);
        $user->preferences = $preferences;
        $user->save();

        return $user->fresh();
    }

    /**
     * @param  array<string, bool>  $features
     */
    protected function manager(array $features = []): User
    {
        $user = User::factory()->create(['theme' => 'light']);
        $user->assignRole('inventory_manager');

        return $features === [] ? $user : $this->withFeatures($user, $features);
    }

    protected function itemType(): ItemType
    {
        return ItemType::query()->create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture-'.fake()->unique()->numerify('###'),
        ]);
    }

    /**
     * @param  list<string>  $mustSee
     * @param  list<string>  $mustNotSee
     */
    protected function assertHtmlContains(string $html, array $mustSee, array $mustNotSee = []): void
    {
        foreach ($mustSee as $needle) {
            $this->assertStringContainsString(
                $needle,
                $html,
                "Expected page HTML to include [{$needle}]"
            );
        }

        foreach ($mustNotSee as $needle) {
            $this->assertStringNotContainsString(
                $needle,
                $html,
                "Expected page HTML not to include [{$needle}]"
            );
        }
    }

    public function test_policy_lists_only_physical_barcode_fields(): void
    {
        $this->assertSame(
            ['serial_number', 'tp_barcode', 'mac_address'],
            ScannableIdentifierFields::fields()
        );
        $this->assertTrue(ScannableIdentifierFields::isScannable('tp_barcode'));
        $this->assertFalse(ScannableIdentifierFields::isScannable('fmi_ast'));
        $this->assertTrue(ScannableIdentifierFields::isTypedOnly('rig_tag'));
    }

    public function test_asset_create_and_edit_forms_offer_scan_only_on_scannable_fields(): void
    {
        $user = $this->manager();
        $type = $this->itemType();
        $asset = Asset::query()->create([
            'item_type_id' => $type->id,
            'name' => 'Scan Form Asset',
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-100',
            'tp_barcode' => 'TP-100',
            'mac_address' => '00:11:22:33:44:55',
        ]);

        foreach ([route('assets.create'), route('assets.edit', $asset)] as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertOk();
            $html = $response->getContent();

            foreach (ScannableIdentifierFields::fields() as $field) {
                $this->assertStringContainsString(
                    "fieldBarcodeScanner('{$field}'",
                    $html,
                    "Missing Scan on {$field} for {$url}"
                );
                $this->assertStringContainsString("id=\"field-qr-{$field}\"", $html);
            }

            foreach (ScannableIdentifierFields::TYPED_ONLY as $field) {
                $this->assertStringNotContainsString(
                    "fieldBarcodeScanner('{$field}'",
                    $html,
                    "Typed-only field {$field} must not be scannable on {$url}"
                );
            }
        }
    }

    public function test_identifier_search_surfaces_offer_scan_into_search(): void
    {
        $user = $this->manager([
            'shelf_tag_generator' => true,
            'asset_connections' => true,
        ]);
        $type = $this->itemType();
        $asset = Asset::query()->create([
            'item_type_id' => $type->id,
            'name' => 'Listed Asset',
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-SEARCH-1',
            'serial_number' => 'SN-SEARCH-1',
            'mac_address' => '11:22:33:44:55:66',
        ]);

        $eventList = AssetList::query()->create([
            'name' => 'Event list',
            'type' => 'event',
            'created_by' => $user->id,
        ]);
        AssetListItem::query()->create([
            'asset_list_id' => $eventList->id,
            'asset_id' => $asset->id,
            'scanned_code' => 'TP-SEARCH-1',
        ]);

        $inventory = AssetList::query()->create([
            'name' => 'Inventory list',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);
        AssetListItem::query()->create([
            'asset_list_id' => $inventory->id,
            'asset_id' => $asset->id,
        ]);

        $cases = [
            [
                'url' => route('assets.index'),
                'needles' => [
                    "fieldBarcodeScanner('asset-search-q'",
                    'id="field-qr-asset-search-q"',
                ],
            ],
            [
                'url' => route('lists.show', $eventList),
                'needles' => [
                    "fieldBarcodeScanner('list-search-q'",
                    'id="field-qr-list-search-q"',
                ],
            ],
            [
                'url' => route('repairs.index'),
                'needles' => [
                    "fieldBarcodeScanner('repairs-search-q'",
                    'id="field-qr-repairs-search-q"',
                ],
            ],
            [
                'url' => route('reports.checkouts'),
                'needles' => [
                    "fieldBarcodeScanner('checkouts-q'",
                    'id="field-qr-checkouts-q"',
                ],
            ],
            [
                'url' => route('reports.unknown-scans'),
                'needles' => [
                    "fieldBarcodeScanner('unknown-scans-q'",
                    'id="field-qr-unknown-scans-q"',
                ],
            ],
            [
                'url' => route('reports.connection-map'),
                'needles' => [
                    "fieldBarcodeScanner('connection-map-search'",
                    'id="field-qr-connection-map-search"',
                ],
            ],
            [
                'url' => route('labels.print.create', ['asset_list_id' => $eventList->id]),
                'needles' => [
                    "fieldBarcodeScanner('shelf-tag-visible-filter'",
                    'id="field-qr-shelf-tag-visible-filter"',
                    'serial_number',
                    'mac_address',
                ],
            ],
        ];

        foreach ($cases as $case) {
            $response = $this->actingAs($user)->get($case['url']);
            $response->assertOk();
            $this->assertHtmlContains($response->getContent(), $case['needles']);
        }

        $compare = $this->actingAs($user)->post(route('reports.compare.run'), [
            'scanned_list_id' => $eventList->id,
            'inventory_list_id' => $inventory->id,
        ]);
        $compare->assertOk();
        $this->assertHtmlContains($compare->getContent(), [
            "fieldBarcodeScanner('comparison-filter-q'",
            'id="field-qr-comparison-filter-q"',
        ]);
    }

    public function test_asset_column_filters_are_scannable_for_barcode_columns(): void
    {
        $user = $this->manager();
        $preferences = $user->preferences ?? [];
        $preferences['asset_table_columns'] = array_values(array_unique(array_merge(
            AssetTableColumns::defaults(),
            ScannableIdentifierFields::fields(),
            ScannableIdentifierFields::TYPED_ONLY,
        )));
        $user->preferences = $preferences;
        $user->save();

        $response = $this->actingAs($user->fresh())->get(route('assets.index'));
        $response->assertOk();
        $html = $response->getContent();

        foreach (ScannableIdentifierFields::fields() as $field) {
            $this->assertStringContainsString(
                "fieldBarcodeScanner('filter-{$field}'",
                $html,
                "Column filter for {$field} must be scannable"
            );
        }

        foreach (ScannableIdentifierFields::TYPED_ONLY as $field) {
            $this->assertStringNotContainsString(
                "fieldBarcodeScanner('filter-{$field}'",
                $html,
                "Column filter for typed-only {$field} must not be scannable"
            );
        }
    }

    public function test_repairs_search_matches_scannable_identifiers(): void
    {
        $user = $this->manager();
        $type = $this->itemType();
        $asset = Asset::query()->create([
            'item_type_id' => $type->id,
            'name' => 'Repair Target',
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-REPAIR-99',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

        Repair::factory()->create([
            'asset_id' => $asset->id,
            'summary' => 'Broken fan',
        ]);

        $bySerial = $this->actingAs($user)->get(route('repairs.index', ['q' => 'SN-REPAIR-99']));
        $bySerial->assertOk();
        $bySerial->assertSee('Broken fan', false);
        $bySerial->assertSee('Repair Target', false);

        $byMac = $this->actingAs($user)->get(route('repairs.index', ['q' => 'AA:BB:CC:DD:EE:FF']));
        $byMac->assertOk();
        $byMac->assertSee('Broken fan', false);
    }
}
