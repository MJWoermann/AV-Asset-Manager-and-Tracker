<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergeAssetsRequest;
use App\Http\Requests\PreviewAssetMergeRequest;
use App\Models\Asset;
use App\Services\AssetDuplicateService;
use App\Services\AssetMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetDuplicateController extends Controller
{
    public function index(AssetDuplicateService $duplicates): View
    {
        $groups = $duplicates->findGroups();

        return view('assets.duplicates.index', [
            'groups' => $groups,
            'groupCount' => count($groups),
            'assetCount' => collect($groups)->sum('count'),
            'matchKeyLabels' => AssetDuplicateService::MATCH_KEY_LABELS,
        ]);
    }

    public function mergeForm(PreviewAssetMergeRequest $request, AssetMergeService $merge): View|RedirectResponse
    {
        $ids = collect($request->validated('asset_ids'))->map(fn ($id) => (int) $id)->values();

        $assets = Asset::query()
            ->with([
                'itemType',
                'location',
                'manufacturer',
                'manufacturerModel',
                'parent',
                'customFieldValues.definition',
            ])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        $defaults = $merge->defaultFieldSources($assets, (int) $ids->first());
        $rows = $merge->mergeableFieldRows($assets);

        $valueMatrix = [];
        foreach ($rows as $row) {
            $key = $row['key'];
            foreach ($assets as $asset) {
                if (($row['type'] ?? '') === 'custom') {
                    $definitionId = (int) ($row['definition_id'] ?? 0);
                    $cf = $asset->customFieldValues->firstWhere('custom_field_definition_id', $definitionId);
                    $display = $cf?->value ?? '';
                    $blank = $display === null || trim((string) $display) === '';
                } else {
                    $display = $merge->formatAttributeForDisplay($asset, $key);
                    $blank = $display === '';
                }

                $valueMatrix[$key][$asset->id] = [
                    'display' => $blank ? '' : (string) $display,
                    'blank' => $blank,
                ];
            }

            $nonBlank = collect($valueMatrix[$key] ?? [])
                ->filter(fn (array $cell) => ! $cell['blank'])
                ->map(fn (array $cell) => $cell['display'])
                ->unique()
                ->values();

            $valueMatrix[$key]['_conflict'] = $nonBlank->count() > 1;
            $valueMatrix[$key]['_all_blank'] = $nonBlank->count() === 0;
        }

        return view('assets.duplicates.merge', [
            'assets' => $assets,
            'rows' => $rows,
            'valueMatrix' => $valueMatrix,
            'defaultFields' => $defaults['fields'],
            'defaultCustomFields' => $defaults['custom_fields'],
            'emptySource' => AssetMergeService::EMPTY_SOURCE,
            'maxAssets' => AssetMergeService::MAX_ASSETS,
        ]);
    }

    public function merge(MergeAssetsRequest $request, AssetMergeService $merge): RedirectResponse
    {
        $payload = $request->mergePayload();

        $result = $merge->mergeWithFieldSelection(
            $payload['asset_ids'],
            $payload['survivor_id'],
            $payload['fields'],
            $payload['custom_fields'],
            (int) $request->user()->id,
        );

        $deleted = $result['deleted'];

        return redirect()
            ->route('assets.show', $result['survivor'])
            ->with(
                'status',
                'Merged '.$deleted.' duplicate '.($deleted === 1 ? 'asset' : 'assets').' into #'.$result['survivor']->id.'.'
            );
    }

    /**
     * Entry from assets index bulk selection: redirect into the merge review form.
     */
    public function startFromSelection(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'inventory_manager']), 403);

        $ids = collect($request->input('asset_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->count() < 2) {
            return redirect()
                ->route('assets.index')
                ->withErrors(['asset_ids' => 'Select at least two assets to merge.']);
        }

        if ($ids->count() > AssetMergeService::MAX_ASSETS) {
            return redirect()
                ->route('assets.index')
                ->withErrors([
                    'asset_ids' => 'Select at most '.AssetMergeService::MAX_ASSETS.' assets for side-by-side merge.',
                ]);
        }

        return redirect()->route('assets.duplicates.merge', [
            'asset_ids' => $ids->all(),
        ]);
    }
}
