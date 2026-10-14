<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\AuthorizesShelfTagFeature;
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
            'elements' => ['required'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
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

    /** @return array<string, mixed> */
    public function layoutAttributes(): array
    {
        return [
            'name' => $this->validated('name'),
            'paper_preset' => $this->validated('paper_preset'),
            'width_mm' => (float) $this->validated('width_mm'),
            'height_mm' => (float) $this->validated('height_mm'),
            'orientation' => $this->validated('orientation'),
            'elements' => $this->decodedElements() ?? [],
        ];
    }
}
