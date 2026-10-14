<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SsoSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SsoSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = SsoSettings::get();

        return view('admin.sso.edit', [
            'settings' => $settings,
            'credentials' => SsoSettings::credentialsStatus(),
            'allowedDomainsText' => implode("\n", $settings['allowed_domains']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'google_enabled' => ['sometimes', 'boolean'],
            'azure_enabled' => ['sometimes', 'boolean'],
            'allowed_domains' => ['nullable', 'string', 'max:5000'],
        ]);

        $domains = SsoSettings::parseDomains($data['allowed_domains'] ?? '');

        foreach ($domains as $index => $domain) {
            if (! preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)*[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/i', $domain)) {
                return back()
                    ->withErrors(['allowed_domains' => 'Invalid domain on line '.($index + 1).': '.$domain])
                    ->withInput();
            }
        }

        SsoSettings::put(
            $request->boolean('google_enabled'),
            $request->boolean('azure_enabled'),
            $domains,
        );

        return redirect()
            ->route('admin.sso.edit')
            ->with('status', 'SSO settings saved.');
    }
}
