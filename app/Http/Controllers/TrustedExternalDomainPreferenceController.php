<?php

namespace App\Http\Controllers;

use App\Support\TrustedExternalDomains;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrustedExternalDomainPreferenceController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->preferences = TrustedExternalDomains::mergeRemove(
            $user->preferences,
            $data['host']
        );
        $user->save();

        return back()->with('status', 'Trusted domain removed.');
    }
}
