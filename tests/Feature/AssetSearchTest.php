<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_matches_partial_identifier_fields(): void
    {
        $user = User::factory()->create();
        $itemType = $this->itemType();

        Asset::create([
            'name' => 'Partial Barcode Asset',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'TP-ZX9-4411',
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'ZX9']));

        $response->assertOk();
        $response->assertSee('Partial Barcode Asset');
        $response->assertSee('<mark class="bg-brand/30 text-inherit rounded-sm px-0.5">ZX9</mark>', false);
    }

    public function test_exact_identifier_match_ranks_above_partial_name_match(): void
    {
        $user = User::factory()->create();
        $itemType = $this->itemType();

        $exact = Asset::create([
            'name' => 'Other Fixture',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'MATCHME',
        ]);

        $partial = Asset::create([
            'name' => 'MATCHME wash light',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'tp_barcode' => 'OTHER-1',
        ]);

        $orderedIds = Asset::query()
            ->search('MATCHME')
            ->orderBySearchRelevance('MATCHME')
            ->pluck('id')
            ->all();

        $this->assertSame([$exact->id, $partial->id], $orderedIds);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'MATCHME']));

        $response->assertOk();
        $response->assertSeeInOrder(['Other Fixture', 'MATCHME wash light']);
    }

    public function test_multi_token_search_matches_across_different_fields(): void
    {
        $user = User::factory()->create();
        $itemType = $this->itemType();

        Asset::create([
            'name' => 'FOH Console',
            'manufacturer' => 'Yamaha',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
            'serial_number' => 'SN-9001',
        ]);

        Asset::create([
            'name' => 'Yamaha Speaker',
            'manufacturer' => 'Yamaha',
            'item_type_id' => $itemType->id,
            'status' => AssetStatus::Available,
        ]);

        $response = $this->actingAs($user)->get(route('assets.index', ['q' => 'Yamaha 9001']));

        $response->assertOk();
        $response->assertSee('FOH Console');
        $response->assertDontSee('Yamaha Speaker');
    }

    private function itemType(): ItemType
    {
        return ItemType::create([
            'name' => 'LX Fixture',
            'slug' => 'lx-fixture',
        ]);
    }
}
