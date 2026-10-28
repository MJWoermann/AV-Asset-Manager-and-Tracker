<?php

namespace App\Http\Controllers;

use App\Support\AssetBrowseFilters;
use App\Support\AssetTableColumns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetColumnPreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string', 'in:'.implode(',', AssetTableColumns::keys())],
        ]);

        $user = $request->user();
        $columns = AssetTableColumns::resolve($data['columns']);
        $preferences = $user->preferences ?? [];
        $preferences[AssetTableColumns::PREFERENCE_KEY] = $columns;
        $user->preferences = $preferences;
        $user->save();

        $previous = url()->previous();
        $assetsIndexPath = parse_url(route('assets.index'), PHP_URL_PATH);
        $previousPath = parse_url($previous, PHP_URL_PATH);
        $returningToAssets = is_string($assetsIndexPath)
            && is_string($previousPath)
            && rtrim($previousPath, '/') === rtrim($assetsIndexPath, '/');

        // Assets keeps URL filters for hidden columns. Other browse pages drop them.
        $target = $returningToAssets
            ? $previous
            : AssetBrowseFilters::urlWithoutHiddenColumnFilters($previous, $columns);

        return redirect()->to($target)->with('status', 'Column preferences saved.');
    }
}
