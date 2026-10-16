<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\FileCategory;
use App\Enums\LocationType;
use App\Models\Asset;
use App\Models\AssetList;
use App\Models\AssetListItem;
use App\Models\CustomFieldDefinition;
use App\Models\CustomFieldSet;
use App\Models\File;
use App\Models\ItemType;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\User;
use App\Services\ImportService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $response->assertSee('first sheet is used');
    }

    public function test_upload_rejects_unsupported_file_type_with_clear_message(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->manager())
            ->from(route('import.create'))
            ->post(route('import.upload'), [
                'file' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('import.create'));
        $response->assertSessionHasErrors([
            'file' => 'That file type is not supported. Upload a CSV or Excel file (.csv, .xlsx, or .xls).',
        ]);
    }

    public function test_upload_rejects_oversized_file_with_clear_message(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->manager())
            ->from(route('import.create'))
            ->post(route('import.upload'), [
                'file' => UploadedFile::fake()->create('huge.csv', 25601, 'text/csv'),
            ]);

        $response->assertRedirect(route('import.create'));
        $response->assertSessionHasErrors([
            'file' => 'The file is too large. Maximum size is 25MB.',
        ]);
    }

    public function test_upload_uses_first_sheet_when_workbook_has_multiple_worksheets(): void
    {
        Storage::fake('local');
        $path = tempnam(sys_get_temp_dir(), 'import');
        $this->assertNotFalse($path);
        $xlsxPath = $path.'.xlsx';
        rename($path, $xlsxPath);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Assets');
        $spreadsheet->getActiveSheet()->fromArray(['Name', 'Type'], null, 'A1');
        $second = $spreadsheet->createSheet();
        $second->setTitle('Extra');
        $second->fromArray(['Other', 'Cols'], null, 'A1');
        (new Xlsx($spreadsheet))->save($xlsxPath);

        $file = new UploadedFile(
            $xlsxPath,
            'multi.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->actingAs($this->manager())
            ->post(route('import.upload'), ['file' => $file]);

        @unlink($xlsxPath);

        $response->assertOk();
        $response->assertSee('Name');
        $response->assertSee('Type');
        $response->assertDontSee('Unable to process files with multiple worksheets');
    }

    public function test_user_facing_read_error_explains_unrecognised_file_type(): void
    {
        $message = app(ImportService::class)->userFacingReadError(new NoTypeDetectedException);

        $this->assertSame(
            'That file type is not supported or could not be recognised. Upload a CSV or Excel file (.csv, .xlsx, or .xls).',
            $message,
        );
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
        $response->assertSee('Description');
        $response->assertSee('Item Type');
        $response->assertSee('Location: Level');
        $response->assertSee('Location: Room');
        $response->assertSee('Location: Rack');
        $response->assertSee('data-import-mapper', false);
        $response->assertSee('importMappingSelect', false);
        $response->assertSee('custom field sets are enabled on item types', false);
    }

    public function test_mapping_step_shows_column_examples_and_marks_empty_columns(): void
    {
        Storage::fake('local');
        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Notes,Unused',
            'Speaker A,Audio,,',
            ',Lighting,Backline note,',
        ]));

        $response = $this->actingAs($this->manager())
            ->post(route('import.upload'), ['file' => $csv]);

        $response->assertOk();
        $response->assertSee('e.g. Speaker A', false);
        $response->assertSee('e.g. Audio', false);
        $response->assertSee('e.g. Backline note', false);
        $response->assertSee('empty column', false);

        /** @var array<string, string|null> $examples */
        $examples = $response->viewData('columnExamples');
        $this->assertSame('Speaker A', $examples['name']);
        $this->assertSame('Audio', $examples['type']);
        $this->assertSame('Backline note', $examples['notes']);
        $this->assertArrayHasKey('unused', $examples);
        $this->assertNull($examples['unused']);
    }

    public function test_mapping_step_exposes_existing_custom_field_options(): void
    {
        Storage::fake('local');

        $fieldSet = CustomFieldSet::create([
            'name' => 'Network Details',
            'slug' => 'network-details',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Host Colour',
            'slug' => 'host-colour',
            'type' => 'text',
            'sort_order' => 0,
        ]);
        $spacer = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Layout spacer',
            'slug' => 'layout-spacer',
            'type' => 'spacer',
            'sort_order' => 1,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', "Name,Type,Colour\nA,Audio,Red\n");

        $response = $this->actingAs($this->manager())
            ->post(route('import.upload'), ['file' => $csv]);

        $response->assertOk();
        $response->assertSee('Network Details: Host Colour', false);

        /** @var list<array{value: string, label: string}> $fieldOptions */
        $fieldOptions = $response->viewData('fieldOptions');
        $values = collect($fieldOptions)->pluck('value')->all();
        $this->assertContains(ImportService::CUSTOM_FIELD_PREFIX.$definition->id, $values);
        $this->assertNotContains(ImportService::CUSTOM_FIELD_PREFIX.$spacer->id, $values);
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

    public function test_prepare_rejects_duplicate_custom_field_mapping(): void
    {
        Storage::fake('local');
        $user = $this->manager();

        $fieldSet = CustomFieldSet::create([
            'name' => 'Looks',
            'slug' => 'looks',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Colour',
            'slug' => 'colour',
            'type' => 'text',
            'sort_order' => 0,
        ]);
        $mappingKey = ImportService::CUSTOM_FIELD_PREFIX.$definition->id;

        $csv = UploadedFile::fake()->createWithContent('assets.csv', "Name,Type,Colour,AltColour\nA,Audio,Red,Blue\n");

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $response = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Colour' => $mappingKey,
                'AltColour' => $mappingKey,
            ],
        ]);

        $response->assertSessionHasErrors('mapping.AltColour');
    }

    public function test_import_writes_mapped_custom_field_and_attaches_missing_set(): void
    {
        Storage::fake('local');
        $user = $this->manager();

        $fieldSet = CustomFieldSet::create([
            'name' => 'Looks',
            'slug' => 'looks',
        ]);
        $definition = CustomFieldDefinition::create([
            'custom_field_set_id' => $fieldSet->id,
            'name' => 'Colour',
            'slug' => 'colour',
            'type' => 'text',
            'sort_order' => 0,
        ]);
        $itemType = ItemType::create([
            'name' => 'Audio Equipment',
            'slug' => 'audio-equipment',
        ]);
        $this->assertFalse($itemType->fieldSets()->where('custom_field_sets.id', $fieldSet->id)->exists());

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Colour',
            'Console One,Audio Equipment,Red',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $prepare = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Colour' => ImportService::CUSTOM_FIELD_PREFIX.$definition->id,
            ],
        ]);
        $prepare->assertOk();

        $process = $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
        ]);
        $process->assertRedirect(route('assets.index'));

        $asset = Asset::query()->where('name', 'Console One')->first();
        $this->assertNotNull($asset);
        $this->assertDatabaseHas('asset_custom_field_values', [
            'asset_id' => $asset->id,
            'custom_field_definition_id' => $definition->id,
            'value' => 'Red',
        ]);
        $this->assertTrue(
            $itemType->fresh()->fieldSets()->where('custom_field_sets.id', $fieldSet->id)->exists()
        );
    }

    public function test_subsequent_upload_prefers_previous_column_mappings(): void
    {
        Storage::fake('local');
        $user = $this->manager();

        $first = UploadedFile::fake()->createWithContent('first.csv', implode("\n", [
            'Asset Title,Category,Weird Col',
            'Mic,Audio Equipment,Keep',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $first]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Asset Title' => 'name',
                'Category' => 'item_type',
                'Weird Col' => ImportService::CREATE_NEW_FIELD,
            ],
        ])->assertOk();

        $second = UploadedFile::fake()->createWithContent('second.csv', implode("\n", [
            'asset title,category,weird col,Notes',
            'Desk,Audio Equipment,X,Hello',
        ]));

        $response = $this->actingAs($user)->post(route('import.upload'), ['file' => $second]);
        $response->assertOk();
        $response->assertSee('Suggestions include mappings from your previous import', false);

        /** @var array<string, string> $suggestions */
        $suggestions = $response->viewData('suggestedMappings');
        $byNormalized = [];
        foreach ($suggestions as $column => $field) {
            $byNormalized[Str::slug(Str::lower($column), '_')] = $field;
        }

        $this->assertSame('name', $byNormalized['asset_title'] ?? null);
        $this->assertSame('item_type', $byNormalized['category'] ?? null);
        $this->assertSame(ImportService::CREATE_NEW_FIELD, $byNormalized['weird_col'] ?? null);
    }

    public function test_suggest_mappings_prefers_remembered_over_fuzzy_guess(): void
    {
        $service = app(ImportService::class);

        $suggestions = $service->suggestMappings(
            ['Serial Number', 'Name', 'Type'],
            [
                'Serial Number' => 'tp_barcode',
                'Name' => 'name',
                'Type' => 'item_type',
            ]
        );

        $this->assertSame('tp_barcode', $suggestions['Serial Number']);
        $this->assertSame('name', $suggestions['Name']);
        $this->assertSame('item_type', $suggestions['Type']);
    }

    public function test_prepare_lists_all_missing_required_mappings(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $csv = UploadedFile::fake()->createWithContent('assets.csv', "ColA,ColB\nA,B\n");

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $response = $this->actingAs($user)->from(route('import.create'))->post(route('import.prepare'), [
            'mapping' => [
                'ColA' => '',
                'ColB' => '',
            ],
        ]);

        $response->assertSessionHasErrors('mapping');
        $messages = session('errors')->get('mapping');
        $this->assertContains('Map a column to Name before importing.', $messages);
        $this->assertContains('Map a column to Item Type before importing.', $messages);
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

    public function test_import_reuses_existing_locations_under_site_hierarchy(): void
    {
        Storage::fake('local');
        $user = $this->manager();

        $site = Location::create([
            'name' => 'Main Warehouse',
            'type' => LocationType::Site,
        ]);
        $level = Location::create([
            'name' => 'Ground Floor',
            'type' => LocationType::Level,
            'parent_id' => $site->id,
        ]);
        $room = Location::create([
            'name' => 'Store A',
            'type' => LocationType::Room,
            'parent_id' => $level->id,
        ]);
        $rack = Location::create([
            'name' => 'Rack 01',
            'type' => LocationType::Rack,
            'parent_id' => $room->id,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Level,Room,Rack',
            'Console One,Mixer,Ground Floor,Store A,Rack 01',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $prepare = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Level' => 'location_level',
                'Room' => 'location_room',
                'Rack' => 'location_rack',
            ],
        ]);
        $prepare->assertOk();

        $process = $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
        ]);
        $process->assertRedirect(route('assets.index'));

        $this->assertSame(1, Location::query()->where('name', 'Ground Floor')->count());
        $this->assertSame(1, Location::query()->where('name', 'Store A')->count());
        $this->assertSame(1, Location::query()->where('name', 'Rack 01')->count());

        $asset = Asset::query()->where('name', 'Console One')->first();
        $this->assertNotNull($asset);
        $this->assertSame($rack->id, $asset->location_id);
    }

    public function test_duplicate_detection_supports_update_replace_skip_and_create(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $itemType = ItemType::create(['name' => 'Audio Equipment', 'slug' => 'audio-equipment']);
        $oldCo = Manufacturer::factory()->create(['name' => 'OldCo']);

        $updateMe = Asset::create([
            'name' => 'Old Update',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'DUP-UPDATE',
            'notes' => 'keep-me',
            'manufacturer_id' => $oldCo->id,
        ]);
        $replaceMe = Asset::create([
            'name' => 'Old Replace',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'fmi_ast' => 'FMI-1',
            'notes' => 'should-clear',
            'manufacturer_id' => $oldCo->id,
        ]);
        $skipMe = Asset::create([
            'name' => 'Old Skip',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-9',
            'notes' => 'untouched',
        ]);
        $falseMatchMe = Asset::create([
            'name' => 'Old False Match',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'mac_address' => 'AA:BB:CC:DD:EE:11',
            'notes' => 'leave-alone',
            'manufacturer_id' => $oldCo->id,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Serial,FMI,Barcode,MAC,Notes,Manufacturer',
            'New Update,Audio Equipment,DUP-UPDATE,,,,Updated notes,NewCo',
            'New Replace,Audio Equipment,,FMI-1,,,Replaced notes,NewCo',
            'New Skip,Audio Equipment,,,TP-9,,Skipped notes,NewCo',
            'Brand New Asset,Audio Equipment,,,,AA:BB:CC:DD:EE:11,New notes,NewCo',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);

        $prepare = $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
                'FMI' => 'fmi_ast',
                'Barcode' => 'tp_barcode',
                'MAC' => 'mac_address',
                'Notes' => 'notes',
                'Manufacturer' => 'manufacturer',
            ],
        ]);
        $prepare->assertOk();
        $prepare->assertSee('Resolve import duplicates');
        $prepare->assertSee('DUP-UPDATE');
        $prepare->assertSee('Create new (false match)');

        $prepared = session('import.prepared');
        $this->assertCount(4, $prepared);

        $actions = [];
        foreach ($prepared as $row) {
            $actions[$row['index']] = match ($row['existing_id']) {
                $updateMe->id => 'update',
                $replaceMe->id => 'replace',
                $skipMe->id => 'skip',
                $falseMatchMe->id => 'create',
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
        $this->assertSame('NewCo', $updateMe->manufacturer?->name);

        $replaceMe->refresh();
        $this->assertSame('New Replace', $replaceMe->name);
        $this->assertSame('Replaced notes', $replaceMe->notes);
        $this->assertSame('NewCo', $replaceMe->manufacturer?->name);

        $skipMe->refresh();
        $this->assertSame('Old Skip', $skipMe->name);
        $this->assertSame('untouched', $skipMe->notes);

        $falseMatchMe->refresh();
        $this->assertSame('Old False Match', $falseMatchMe->name);
        $this->assertSame('leave-alone', $falseMatchMe->notes);
        $this->assertSame('OldCo', $falseMatchMe->manufacturer?->name);

        $created = Asset::query()->where('name', 'Brand New Asset')->first();
        $this->assertNotNull($created);
        $this->assertSame('AA:BB:CC:DD:EE:11', $created->mac_address);
        $this->assertSame('New notes', $created->notes);
        $this->assertNotSame($falseMatchMe->id, $created->id);
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

    public function test_import_can_create_new_list_and_add_created_assets(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Serial',
            'List Speaker,Audio Equipment,SN-LIST-1',
            'List Mic,Audio Equipment,SN-LIST-2',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
            ],
        ])->assertOk()->assertSee('Add imported assets to a list');

        $response = $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
            'list_destination' => 'new',
            'new_list_name' => 'Import Event List',
            'new_list_type' => 'event',
        ]);

        $list = AssetList::query()->where('name', 'Import Event List')->first();
        $this->assertNotNull($list);
        $this->assertSame('event', $list->type);
        $this->assertSame($user->id, $list->created_by);
        $response->assertRedirect(route('lists.show', $list));

        $this->assertSame(2, AssetListItem::query()->where('asset_list_id', $list->id)->count());
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => Asset::query()->where('serial_number', 'SN-LIST-1')->value('id'),
        ]);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => Asset::query()->where('serial_number', 'SN-LIST-2')->value('id'),
        ]);
    }

    public function test_import_adds_updated_assets_to_existing_list_when_requested(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $itemType = ItemType::create(['name' => 'Audio Equipment', 'slug' => 'audio-equipment']);
        $existing = Asset::create([
            'name' => 'Old Name',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-EXISTING',
        ]);
        $list = AssetList::create([
            'name' => 'Warehouse Inventory',
            'type' => 'inventory',
            'created_by' => $user->id,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Serial',
            'Updated Existing,Audio Equipment,SN-EXISTING',
            'Brand New,Audio Equipment,SN-BRAND-NEW',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
            ],
        ])->assertOk();

        $prepared = session('import.prepared');
        $actions = [];
        foreach ($prepared as $row) {
            if ($row['existing_id'] === $existing->id) {
                $actions[$row['index']] = 'update';
            }
        }

        $response = $this->actingAs($user)->post(route('import.process'), [
            'actions' => $actions,
            'bulk_action' => 'update',
            'list_destination' => 'existing',
            'asset_list_id' => $list->id,
            'include_updated_in_list' => '1',
        ]);

        $response->assertRedirect(route('lists.show', $list));
        $this->assertSame(2, AssetListItem::query()->where('asset_list_id', $list->id)->count());
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $existing->id,
        ]);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => Asset::query()->where('serial_number', 'SN-BRAND-NEW')->value('id'),
        ]);
    }

    public function test_import_excludes_updated_assets_from_list_unless_requested(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $itemType = ItemType::create(['name' => 'Audio Equipment', 'slug' => 'audio-equipment']);
        $existing = Asset::create([
            'name' => 'Old Name',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-EXISTING-2',
        ]);
        $list = AssetList::create([
            'name' => 'Event Only New',
            'type' => 'event',
            'created_by' => $user->id,
        ]);

        $csv = UploadedFile::fake()->createWithContent('assets.csv', implode("\n", [
            'Name,Type,Serial',
            'Updated Existing,Audio Equipment,SN-EXISTING-2',
            'Brand New Only,Audio Equipment,SN-ONLY-NEW',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
            ],
        ])->assertOk();

        $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
            'list_destination' => 'existing',
            'asset_list_id' => $list->id,
        ])->assertRedirect(route('lists.show', $list));

        $this->assertSame(1, AssetListItem::query()->where('asset_list_id', $list->id)->count());
        $this->assertDatabaseMissing('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => $existing->id,
        ]);
        $this->assertDatabaseHas('asset_list_items', [
            'asset_list_id' => $list->id,
            'asset_id' => Asset::query()->where('serial_number', 'SN-ONLY-NEW')->value('id'),
        ]);
    }

    public function test_successful_import_archives_file_in_library(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $csv = UploadedFile::fake()->createWithContent('warehouse-stock.csv', implode("\n", [
            'Name,Type,Serial',
            'Archive Me,Audio Equipment,SN-ARCHIVE-1',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
            ],
        ])->assertOk();

        $response = $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
        ]);

        $response->assertRedirect(route('assets.index'));
        $this->assertStringContainsString('Import file saved to the file library.', session('status'));

        $file = File::query()->where('original_filename', 'warehouse-stock.csv')->first();
        $this->assertNotNull($file);
        $this->assertSame(FileCategory::Import, $file->category);
        $this->assertSame('Asset import: warehouse-stock.csv', $file->title);
        $this->assertSame($user->id, $file->uploaded_by);
        $this->assertTrue(Storage::disk($file->disk)->exists($file->path));
        $this->assertFalse(session()->has('import.path'));
    }

    public function test_dry_run_does_not_archive_import_file(): void
    {
        Storage::fake('local');
        $user = $this->manager();
        $csv = UploadedFile::fake()->createWithContent('dry-run.csv', implode("\n", [
            'Name,Type,Serial',
            'Dry Run Asset,Audio Equipment,SN-DRY-1',
        ]));

        $this->actingAs($user)->post(route('import.upload'), ['file' => $csv]);
        $this->actingAs($user)->post(route('import.prepare'), [
            'mapping' => [
                'Name' => 'name',
                'Type' => 'item_type',
                'Serial' => 'serial_number',
            ],
        ])->assertOk();

        $this->actingAs($user)->post(route('import.process'), [
            'bulk_action' => 'update',
            'dry_run' => '1',
        ])->assertRedirect(route('assets.index'));

        $this->assertSame(0, File::query()->count());
        $this->assertTrue(session()->has('import.path'));
        $this->assertDatabaseMissing('assets', ['serial_number' => 'SN-DRY-1']);
    }
}
