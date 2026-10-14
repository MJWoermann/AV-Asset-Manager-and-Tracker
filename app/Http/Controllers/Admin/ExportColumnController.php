<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AssetExportColumns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExportColumnController extends Controller
{
    public function edit(): View
    {
        return view('admin.export-columns.edit', [
            'availableColumns' => AssetExportColumns::definitions(),
            'assetColumns' => AssetExportColumns::forAssets(),
            'listColumns' => AssetExportColumns::forLists(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $allowed = implode(',', AssetExportColumns::keys());

        $data = $request->validate([
            'asset_columns' => ['required', 'array', 'min:1'],
            'asset_columns.*' => ['string', 'in:'.$allowed],
            'list_columns' => ['required', 'array', 'min:1'],
            'list_columns.*' => ['string', 'in:'.$allowed],
        ]);

        AssetExportColumns::putAssets($data['asset_columns']);
        AssetExportColumns::putLists($data['list_columns']);

        return redirect()
            ->route('admin.export-columns.edit')
            ->with('status', 'Export columns saved.');
    }
}
