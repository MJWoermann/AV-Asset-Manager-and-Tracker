<?php

namespace Tests\Unit;

use App\Models\ItemType;
use App\Services\Rigging\ContinuousBeamUdl;
use App\Services\Rigging\RiggingCalculatorRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RiggingCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private RiggingCalculatorRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new RiggingCalculatorRegistry(new ContinuousBeamUdl);
    }

    public function test_two_point_hang_reactions(): void
    {
        $result = $this->registry->twoPointHang(['W' => 100, 'L' => 10, 'x' => 4]);

        $this->assertEqualsWithDelta(60.0, $result['results']['RA'], 0.001);
        $this->assertEqualsWithDelta(40.0, $result['results']['RB'], 0.001);
    }

    public function test_lever_class_i_ii_iii(): void
    {
        $i = $this->registry->lever(['class' => 'I', 'Fr' => 100, 'dr' => 1, 'de' => 2]);
        $this->assertEqualsWithDelta(50.0, $i['results']['Fe_kg'], 0.001);
        $this->assertEqualsWithDelta(150.0, $i['results']['fulcrum_kg'], 0.001);
        $this->assertEqualsWithDelta(2.0, $i['results']['mechanical_advantage'], 0.001);

        $ii = $this->registry->lever(['class' => 'II', 'Fr' => 100, 'dr' => 1, 'de' => 2]);
        $this->assertEqualsWithDelta(50.0, $ii['results']['Fe_kg'], 0.001);
        $this->assertEqualsWithDelta(50.0, $ii['results']['fulcrum_kg'], 0.001);

        $iii = $this->registry->lever(['class' => 'III', 'Fr' => 50, 'dr' => 2, 'de' => 1]);
        $this->assertEqualsWithDelta(100.0, $iii['results']['Fe_kg'], 0.001);
        $this->assertEqualsWithDelta(50.0, $iii['results']['fulcrum_kg'], 0.001);
    }

    public function test_bridle_two_leg_symmetric(): void
    {
        $result = $this->registry->bridleTwoLeg([
            'W' => 200,
            'span' => 4,
            'drop' => 2,
            'offset' => 2,
        ]);

        $this->assertEqualsWithDelta($result['results']['T1'], $result['results']['T2'], 0.01);
        $this->assertGreaterThan(100, $result['results']['T1']);
    }

    public function test_bridle_multi_leg_and_sling_factor(): void
    {
        $multi = $this->registry->bridleMultiLeg([
            'W' => 200,
            'legs' => 4,
            'angle_from_vertical_deg' => 0,
        ]);
        $this->assertEqualsWithDelta(50.0, $multi['results']['tension_per_leg'], 0.001);

        $sling = $this->registry->slingAngleFactor([
            'W' => 100,
            'angle_from_vertical_deg' => 60,
            'wll' => 300,
        ]);
        $this->assertEqualsWithDelta(2.0, $sling['results']['angle_factor'], 0.001);
        $this->assertEqualsWithDelta(200.0, $sling['results']['tension'], 0.001);
        $this->assertTrue($sling['results']['within_wll']);
    }

    public function test_dead_hang_and_beam_point_load(): void
    {
        $dead = $this->registry->deadHang(['W' => 80, 'wll' => 100]);
        $this->assertEqualsWithDelta(80.0, $dead['results']['utilization_pct'], 0.01);
        $this->assertTrue($dead['results']['within_wll']);

        $beam = $this->registry->beamPointLoad([
            'L' => 10,
            'loads' => [
                ['W' => 100, 'x' => 4],
                ['W' => 50, 'x' => 8],
            ],
        ]);
        $this->assertEqualsWithDelta(150.0, $beam['results']['total_W'], 0.001);
        $this->assertEqualsWithDelta($beam['results']['RA'] + $beam['results']['RB'], 150.0, 0.001);
    }

    public function test_udl_equal_and_custom_and_item_type(): void
    {
        $equal = $this->registry->udlMultiPoint([
            'point_count' => 4,
            'distribution_mode' => 'equal',
            'hoist_self_weight_kg' => 10,
            'weight_lines' => [
                ['label' => 'Truss', 'weight_kg' => 200, 'quantity' => 1],
            ],
        ]);
        $this->assertEqualsWithDelta(200.0, $equal['results']['total_load_kg'], 0.001);
        $this->assertCount(4, $equal['results']['points']);
        $this->assertEqualsWithDelta(50.0, $equal['results']['points'][0]['share_kg'], 0.001);
        $this->assertEqualsWithDelta(60.0, $equal['results']['points'][0]['total_kg'], 0.001);

        $custom = $this->registry->udlMultiPoint([
            'point_count' => 2,
            'distribution_mode' => 'custom',
            'custom_percents' => [70, 30],
            'weight_lines' => [
                ['label' => 'Load', 'weight_kg' => 100, 'quantity' => 1],
            ],
        ]);
        $this->assertEqualsWithDelta(70.0, $custom['results']['points'][0]['share_kg'], 0.001);
        $this->assertEqualsWithDelta(30.0, $custom['results']['points'][1]['share_kg'], 0.001);

        $type = ItemType::factory()->riggingCatalogue(25)->create();
        $fromType = $this->registry->udlMultiPoint([
            'point_count' => 2,
            'distribution_mode' => 'equal',
            'weight_lines' => [
                ['item_type_id' => $type->id, 'quantity' => 2],
            ],
        ]);
        $this->assertEqualsWithDelta(50.0, $fromType['results']['total_load_kg'], 0.001);
        $this->assertSame($type->id, $fromType['inputs']['weight_lines'][0]['item_type_id']);
    }

    public function test_udl_rejects_disabled_item_type(): void
    {
        $type = ItemType::factory()->create(['supports_rigging_calculator' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->registry->udlMultiPoint([
            'point_count' => 1,
            'distribution_mode' => 'equal',
            'weight_lines' => [
                ['item_type_id' => $type->id, 'weight_kg' => 10, 'quantity' => 1],
            ],
        ]);
    }

    public function test_udl_custom_percents_must_sum_100(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->registry->udlMultiPoint([
            'point_count' => 2,
            'distribution_mode' => 'custom',
            'custom_percents' => [60, 30],
            'weight_lines' => [
                ['label' => 'L', 'weight_kg' => 10, 'quantity' => 1],
            ],
        ]);
    }

    public function test_continuous_beam_coefficients_sum_to_one_for_n_1_to_8(): void
    {
        $solver = new ContinuousBeamUdl;
        for ($n = 1; $n <= 8; $n++) {
            $coeffs = $solver->coefficients($n);
            $this->assertCount($n, $coeffs);
            $this->assertEqualsWithDelta(1.0, array_sum($coeffs), 0.0001, "N={$n}");
        }

        // Classic 2 equal spans (3 supports) under UDL: ends 3/16, mid 10/16 of total load.
        $n3 = $solver->coefficients(3);
        $this->assertEqualsWithDelta(0.1875, $n3[0], 0.001);
        $this->assertEqualsWithDelta(0.625, $n3[1], 0.001);
        $this->assertEqualsWithDelta(0.1875, $n3[2], 0.001);
    }

    public function test_projector_lens_modes_and_unit_convert(): void
    {
        $width = $this->registry->projectorLens([
            'mode' => 'width_from_ratio_distance',
            'aspect' => '16:9',
            'throw_ratio' => 1.5,
            'throw_distance' => 6,
        ]);
        $this->assertEqualsWithDelta(4.0, $width['results']['image_width'], 0.001);
        $this->assertEqualsWithDelta(4.0 * 9 / 16, $width['results']['image_height'], 0.001);

        $dist = $this->registry->projectorLens([
            'mode' => 'distance_from_ratio_width',
            'aspect' => '16:9',
            'throw_ratio' => 2,
            'image_width' => 3,
        ]);
        $this->assertEqualsWithDelta(6.0, $dist['results']['throw_distance'], 0.001);

        $ratio = $this->registry->projectorLens([
            'mode' => 'ratio_from_distance_width',
            'aspect' => '4:3',
            'throw_distance' => 8,
            'image_width' => 4,
        ]);
        $this->assertEqualsWithDelta(2.0, $ratio['results']['throw_ratio'], 0.001);

        $kgToLb = $this->registry->unitConvert(['value' => 1, 'from' => 'kg', 'to' => 'lb']);
        $this->assertEqualsWithDelta(2.204622, $kgToLb['results']['value'], 0.001);
    }
}
