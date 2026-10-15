<?php

namespace Database\Factories;

use App\Models\ShelfTagLayout;
use App\Models\User;
use App\Support\ShelfTagPaperPresets;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShelfTagLayout>
 */
class ShelfTagLayoutFactory extends Factory
{
    protected $model = ShelfTagLayout::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' label',
            'paper_preset' => ShelfTagPaperPresets::DEFAULT,
            'width_mm' => 62.0,
            'height_mm' => 40.0,
            'orientation' => 'portrait',
            'length_mode' => ShelfTagPaperPresets::LENGTH_MODE_FIXED,
            'elements' => self::sampleElements(),
            'editor_settings' => ShelfTagPaperPresets::defaultEditorSettings(),
            'logo_path' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Sample 62 mm layout: name + barcode from tp_barcode + FMI.
     *
     * @return list<array<string, mixed>>
     */
    public static function sampleElements(): array
    {
        return [
            [
                'id' => 'el-name',
                'type' => 'field',
                'x' => 2,
                'y' => 2,
                'width' => 58,
                'height' => 8,
                'rotation' => 0,
                'z' => 1,
                'props' => [
                    'fieldKey' => 'name',
                    'fontSize' => 11,
                    'fontWeight' => 'bold',
                    'align' => 'left',
                    'color' => '#000000',
                ],
            ],
            [
                'id' => 'el-barcode',
                'type' => 'barcode',
                'x' => 4,
                'y' => 12,
                'width' => 54,
                'height' => 16,
                'rotation' => 0,
                'z' => 2,
                'props' => [
                    'fieldKey' => 'tp_barcode',
                    'symbology' => 'code128',
                    'showText' => true,
                ],
            ],
            [
                'id' => 'el-fmi',
                'type' => 'field',
                'x' => 2,
                'y' => 30,
                'width' => 58,
                'height' => 7,
                'rotation' => 0,
                'z' => 3,
                'props' => [
                    'fieldKey' => 'fmi_ast',
                    'fontSize' => 10,
                    'fontWeight' => 'normal',
                    'align' => 'left',
                    'color' => '#000000',
                ],
            ],
        ];
    }
}
