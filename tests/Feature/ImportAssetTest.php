<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\CustomFieldDefinition;
use App\Models\ItemType;
use App\Models\User;
use App\Services\ImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportAssetTest extends TestCase
{
    use RefreshDatabase;

    protected function manager(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('inventory_manager');

        return $user;
    }

    public function test_upload_screen_does_not_require_item_type(): void
    {
        $response = $this->actingAs($this->manager())->get(route('import.create'));

        $response->assertOk();
        $response->assertDontSee('Item type for imported rows');
        $response->assertSee('Upload & map columns');
    }

    public function test_mapping_step_exposes_type_location_and_create_field_options(): void
    {
        Storage::fake('local');
        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Level,Room,Rack,Serial,Extra',
            'Speaker A,Audio Equipment,L1,Studio,R1,SN-1,Blue',
        ]));

        $response = $this->actingAs($this->manager())
            ->post(route('import.upload'), ['file' => $csv]);

        $response->assertOk();
        $response->assertSee('Item Type');
        $response->assertSee('Location: Level');
        $response->assertSee('Location: Room');
        $response->assertSee('Location: Rack');
        $response->assertSee('Create new field (Imported-Field:');
    }

    public function test_prepare_rejects_duplicate_target_field_mapping(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $csv = UploadedFile::fake()->createWithContent('assets.csv', "Name,AltName,Type\nA,B,Audio\n");

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $response = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'AltName' => 'name',
                'Type' => 'item_type',
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_import_creates_missing_item_types_locations_and_imported_fields(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Level,Room,Rack,Serial,Colour',
            'Console One,New Console Type,Ground,Control Room,Rack A,SN-100,Red',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $prepare = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Level' => 'location_level',
                'Room' => 'location_room',
                'Rack' => 'location_rack',
                'Serial' => 'serial_number',
                'Colour' => ImportService::CREATE_NEW_FIELD,
            ],
        ]);
        $prepare->assertOk();
        $prepare->assertSee('No duplicates found');

        $process = $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
        ]);
        $process->assertRedirect(route('assets.index'));

        $this->assertDatabaseHas('item_types', ['name' => 'New Console Type']);
        $this->assertDatabaseHas('locations', ['name' => 'Ground', 'type' => LocationType::Level->value]);
        $this->assertDatabaseHas('locations', ['name' => 'Control Room', 'type' => LocationType::Room->value]);
        $this->assertDatabaseHas('locations', ['name' => 'Rack A', 'type' => LocationType::Rack->value]);

        $asset = Asset::query()->where('name', 'Console One')->first();
        $this->assertNotNull($asset);
        $this->assertSame('SN-100', $asset->serial_number);
        $this->assertNotNull($asset->location_id);

        $definition = CustomFieldDefinition::query()
            ->where('name', 'Imported-Field:Colour')
            ->first();
        $this->assertNotNull($definition);
        $this->assertDatabaseHas('asset_custom_field_values', [
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'Red',
        ]);
    }

    public function test_duplicate_detection_supports_update_replace_and_skip(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $itemType = ItemType::create(['name' => 'Audio Equipment', 'slug' => 'audio-equipment']);

        $updateMe = Asset::create([
            'name' => 'Old Update',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'DUP-UPDATE',
            'notes' => 'keep-me',
            'manufacturer' => 'OldCo',
        ]);
        $replaceMe = Asset::create([
            'name' => 'Old Replace',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'fmi_ast' => 'FMI-1',
            'notes' => 'should-clear',
            'manufacturer' => 'OldCo',
        ]);
        $skipMe = Asset::create([
            'name' => 'Old Skip',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-9',
            'notes' => 'untouched',
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Serial,FMI,Barcode,Notes,Manufacturer',
            'New Update,Audio Equipment,DUP-UPDATE,,,Updated notes,NewCo',
            'New Replace,Audio Equipment,,FMI-1,,Replaced notes,NewCo',
            'New Skip,Audio Equipment,,,TP-9,Skipped notes,NewCo',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $prepare = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
                'FMI' => 'fmi_ast',
                'Barcode' => 'tp_barcode',
                'Notes' => 'notes',
                'Manufacturer' => 'manufacturer',
            ],
        ]);
        $prepare->assertOk();
        $prepare->assertSee('Resolve import duplicates');
        $prepare->assertSee('DUP-UPDATE');

        $prepared = session('import.prepared');
        $this->assertCount(3, $prepared);

        $actions = [];
        foreach ($prepared as $row) {
            $actions[$row['index']] = match ($row['existing_id']) {
                $updateMe->id => 'update',
                $replaceMe->id => 'replace',
                $skipMe->id => 'skip',
                default => 'skip',
            };
        }

        $this->actingAs($user)->post(route('import.process'), [
            'actions' => $actions,
            'bulk_action' => 'update',
        ])->assertRedirect(route('assets.index'));

        $updateMe->refresh();
        $this->assertSame('New Update', $updateMe->name);
        $this->assertSame('Updated notes', $updateMe->notes);
        $this->assertSame('NewCo', $updateMe->manufacturer);

        $replaceMe->refresh();
        $this->assertSame('New Replace', $replaceMe->name);
        $this->assertSame('Replaced notes', $replaceMe->notes);
        $this->assertSame('NewCo', $replaceMe->manufacturer);

        $skipMe->refresh();
        $this->assertSame('Old Skip', $skipMe->name);
        $this->assertSame('untouched', $skipMe->notes);
    }

    public function test_service_matches_duplicates_on_mac_and_rig_tag(): void
    {
        $itemType = ItemType::create(['name' => 'Vision Item', 'slug' => 'vision-item']);
        Asset::create([
            'name' => 'Existing',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'rig_tag' => 'RIG-22',
        ]);

        $service = app(ImportService::class);
        $rows = $service->prepareRows(collect([
            collect([
                'name' => 'Incoming',
                'type' => 'Vision Item',
                'mac' => 'AA:BB:CC:DD:EE:FF',
            ]),
            collect([
                'name' => 'Incoming Two',
                'type' => 'Vision Item',
                'rig' => 'RIG-22',
            ]),
        ]), [
            'name' => 'name',
            'type' => 'item_type',
            'mac' => 'mac_address',
            'rig' => 'rig_tag',
        ]);

        $this->assertNotNull($rows[0]['existing_id']);
        $this->assertContains('mac_address', $rows[0]['matched_on']);
        $this->assertNotNull($rows[1]['existing_id']);
        $this->assertContains('rig_tag', $rows[1]['matched_on']);
    }
}
