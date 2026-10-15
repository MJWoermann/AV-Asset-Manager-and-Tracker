<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AssetExportColumns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExportColumnController extends Controller
{
    public function edit(): View
    {
        $customDefinitions = AssetExportColumns::customFieldDefinitions();

        return view('admin.export-columns.edit', [
            'availableColumns' => AssetExportColumns::definitions(),
            'customFieldSets' => AssetExportColumns::customFieldsBySet($customDefinitions),
            'assetColumns' => AssetExportColumns::forAssets(),
            'listColumns' => AssetExportColumns::forLists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowed = AssetExportColumns::allowedKeys();

        $data = $request->validate([
            'asset_columns' => ['required', 'array', 'min:1'],
            'asset_columns.*' => ['string', Rule::in($allowed)],
            'list_columns' => ['required', 'array', 'min:1'],
            'list_columns.*' => ['string', Rule::in($allowed)],
        ]);

        AssetExportColumns::putAssets($data['asset_columns']);
        AssetExportColumns::putLists($data['list_columns']);

        return redirect()
            ->route('admin.export-columns.edit')
            ->with('status', 'Export columns saved.');
    }
}
