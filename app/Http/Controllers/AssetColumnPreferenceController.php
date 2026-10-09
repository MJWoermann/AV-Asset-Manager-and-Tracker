<?php

namespace App\Http\Controllers;

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
        $preferences = $user->preferences ?? [];
        $preferences[AssetTableColumns::PREFERENCE_KEY] = AssetTableColumns::resolve($data['columns']);
        $user->preferences = $preferences;
        $user->save();

        return back()->with('status', 'Column preferences saved.');
    }
}
