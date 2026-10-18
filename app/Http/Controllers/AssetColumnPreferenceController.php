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
        $target = AssetBrowseFilters::urlWithoutHiddenColumnFilters($previous, $columns);

        return redirect()->to($target)->with('status', 'Column preferences saved.');
    }
}
