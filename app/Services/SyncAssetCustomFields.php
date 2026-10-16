<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetCustomFieldValue;
use App\Models\CustomFieldDefinition;
use Illuminate\Http\Request;

class SyncAssetCustomFields
{
    public function sync(Asset $asset, Request $request): void
    {
        $values = $request->input('custom_fields', []);
        if (! is_array($values) || $values === []) {
            return;
        }

        $asset->loadMissing('itemType');
        $itemType = $asset->itemType;
        if ($itemType === null) {
            return;
        }

        $allowedIds = CustomFieldDefinition::query()
            ->whereIn('custom_field_set_id', $itemType->fieldSets()->pluck('custom_field_sets.id'))
            ->whereNotIn('type', CustomFieldDefinition::LAYOUT_TYPES)
            ->whereIn('id', array_keys($values))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $allowedLookup = array_fill_keys($allowedIds, true);

        foreach ($values as $definitionId => $value) {
            $definitionId = (int) $definitionId;
            if (! isset($allowedLookup[$definitionId])) {
                continue;
            }

            if ($value === null || $value === '') {
                AssetCustomFieldValue::where('asset_id', $asset->id)
                    ->where('custom_field_definition_id', $definitionId)
                    ->delete();

                continue;
            }

            AssetCustomFieldValue::updateOrCreate(
                [
                    'asset_id' => $asset->id,
                    'custom_field_definition_id' => $definitionId,
                ],
                ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
            );
        }
    }
}
