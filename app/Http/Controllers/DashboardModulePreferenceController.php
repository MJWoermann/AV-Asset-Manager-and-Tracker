<?php

namespace App\Http\Controllers;

use App\Support\DashboardModules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardModulePreferenceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $available = DashboardModules::availableFor($request->user());

        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['string', Rule::in($available)],
            'enabled' => ['nullable', 'array'],
            'enabled.*' => ['string', Rule::in($available)],
        ]);

        $user = $request->user();
        $preferences = $user->preferences ?? [];
        $preferences[DashboardModules::PREFERENCE_KEY] = DashboardModules::normalize(
            $data['order'],
            $data['enabled'] ?? [],
            $available
        );
        $user->preferences = $preferences;
        $user->save();

        return back()->with('status', 'Dashboard modules saved.');
    }
}
