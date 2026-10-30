<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesShelfTagFeature;
use App\Models\File;
use App\Models\ShelfTagLayout;
use App\Support\ShelfTagFieldKeys;
use App\Support\ShelfTagPaperPresets;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreShelfTagLayoutRequest extends FormRequest
{
    use AuthorizesShelfTagFeature;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'paper_preset' => ['required', 'string', Rule::in(ShelfTagPaperPresets::keys())],
            'width_mm' => ['required', 'numeric', 'min:10', 'max:200'],
            'height_mm' => ['required', 'numeric', 'min:10', 'max:300'],
            'orientation' => ['required', 'string', Rule::in(['portrait', 'landscape'])],
            'length_mode' => ['required', 'string', Rule::in(ShelfTagPaperPresets::lengthModes())],
            'elements' => ['required'],
            'editor_settings' => ['nullable'],
            'show_grid' => ['sometimes', 'boolean'],
            'snap_to_grid' => ['sometimes', 'boolean'],
            'grid_size_mm' => ['sometimes', 'numeric', Rule::in([2.5, 5, 10])],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'logo_file_id' => ['nullable', 'integer'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'length_mode' => $this->input('length_mode', ShelfTagPaperPresets::LENGTH_MODE_FIXED),
        ];

        if ($this->has('show_grid')) {
            $merge['show_grid'] = filter_var($this->input('show_grid'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->has('snap_to_grid')) {
            $merge['snap_to_grid'] = filter_var($this->input('snap_to_grid'), FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge($merge);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $preset = (string) $this->input('paper_preset', '');
            if (ShelfTagPaperPresets::isValid($preset) && ! ShelfTagPaperPresets::isContinuous($preset)) {
                $expectedW = ShelfTagPaperPresets::defaultWidthMm($preset);
                $expectedH = ShelfTagPaperPresets::defaultHeightMm($preset);
                $width = (float) $this->input('width_mm');
                $height = (float) $this->input('height_mm');

                if (abs($width - $expectedW) > 0.5 || abs($height - $expectedH) > 0.5) {
                    $validator->errors()->add(
                        'paper_preset',
                        'Die-cut size must match the selected Brother DK preset.'
                    );
                }

                if ($this->input('length_mode') === ShelfTagPaperPresets::LENGTH_MODE_AUTO) {
                    $validator->errors()->add(
                        'length_mode',
                        'Auto length is only available for continuous paper presets.'
                    );
                }
            }

            $logoFileId = $this->input('logo_file_id');
            if ($logoFileId !== null && $logoFileId !== '') {
                $logoFile = File::query()->find((int) $logoFileId);
                if (! ShelfTagLayout::isUsableLogoFile($logoFile)) {
                    $validator->errors()->add(
                        'logo_file_id',
                        'Choose a JPEG or PNG image that is already in Files.'
                    );
                }
            }

            $elements = $this->decodedElements();
            if ($elements === null) {
                $validator->errors()->add('elements', 'Layout elements must be valid JSON.');

                return;
            }

            if (count($elements) > 100) {
                $validator->errors()->add('elements', 'A layout may have at most 100 elements.');
            }

            foreach ($elements as $index => $element) {
                if (! is_array($element)) {
                    $validator->errors()->add('elements', "Element #{$index} is invalid.");

                    continue;
                }

                $type = (string) ($element['type'] ?? '');
                if (! in_array($type, ['text', 'field', 'rect', 'line', 'barcode', 'qr', 'logo'], true)) {
                    $validator->errors()->add('elements', "Element #{$index} has an invalid type.");
                }

                $props = is_array($element['props'] ?? null) ? $element['props'] : [];
                $fieldKey = (string) ($props['fieldKey'] ?? '');
                if ($fieldKey !== '' && ! ShelfTagFieldKeys::isValidKey($fieldKey)) {
                    $validator->errors()->add('elements', "Element #{$index} has an invalid field key.");
                }

                if ($type === 'barcode') {
                    $symbology = (string) ($props['symbology'] ?? 'code128');
                    if (! in_array($symbology, ['code128', 'code39'], true)) {
                        $validator->errors()->add('elements', "Element #{$index} has an invalid barcode symbology.");
                    }
                }
            }
        });
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function decodedElements(): ?array
    {
        $raw = $this->input('elements');

        if (is_array($raw)) {
            return array_values($raw);
        }

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        return array_values($decoded);
    }

    /**
     * @return array{show_grid: bool, snap_to_grid: bool, grid_size_mm: float, show_bleed: bool, bleed_top_mm: float, bleed_bottom_mm: float, bleed_left_mm: float, bleed_right_mm: float}
     */
    public function resolvedEditorSettings(): array
    {
        $raw = $this->input('editor_settings');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return ShelfTagPaperPresets::normalizeEditorSettings($decoded);
            }
        }

        if (is_array($raw)) {
            return ShelfTagPaperPresets::normalizeEditorSettings($raw);
        }

        return ShelfTagPaperPresets::normalizeEditorSettings([
            'show_grid' => $this->boolean('show_grid'),
            'snap_to_grid' => $this->boolean('snap_to_grid'),
            'grid_size_mm' => (float) $this->input('grid_size_mm', 5),
        ]);
    }

    /** @return array<string, mixed> */
    public function layoutAttributes(): array
    {
        $preset = (string) $this->validated('paper_preset');
        $lengthMode = (string) $this->validated('length_mode');
        $heightMm = (float) $this->validated('height_mm');

        if (ShelfTagPaperPresets::isContinuous($preset)) {
            $heightMm = ShelfTagPaperPresets::clampContinuousLength($heightMm);
        }

        if (! ShelfTagPaperPresets::isContinuous($preset)) {
            $lengthMode = ShelfTagPaperPresets::LENGTH_MODE_FIXED;
        }

        return [
            'name' => $this->validated('name'),
            'paper_preset' => $preset,
            'width_mm' => (float) $this->validated('width_mm'),
            'height_mm' => $heightMm,
            'orientation' => $this->validated('orientation'),
            'length_mode' => $lengthMode,
            'elements' => $this->decodedElements() ?? [],
            'editor_settings' => $this->resolvedEditorSettings(),
        ];
    }
}
