<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEmailSettingsRequest;
use App\Services\Mail\MailTransportManager;
use App\Support\EmailIntegrationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Throwable;

class EmailSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.email.edit', [
            'settings' => EmailIntegrationSettings::get(),
            'credentials' => EmailIntegrationSettings::credentialsStatus(),
            'drivers' => EmailIntegrationSettings::DRIVERS,
            'digestsAvailable' => EmailIntegrationSettings::digestsAvailable(),
        ]);
    }

    public function update(UpdateEmailSettingsRequest $request): RedirectResponse
    {
        EmailIntegrationSettings::put($request->validatedSettings());

        return redirect()
            ->route('admin.email.edit')
            ->with('status', 'Email integration settings saved.');
    }

    public function test(MailTransportManager $transports): RedirectResponse
    {
        $settings = EmailIntegrationSettings::get();
        $to = $settings['from_address'] ?: config('mail.from.address');

        if (! filled($to)) {
            return back()->withErrors(['test' => 'Set a from address (settings or MAIL_FROM_ADDRESS) before sending a test.']);
        }

        try {
            $transports->driver()->send(
                [(string) $to],
                'AV Asset Manager email test',
                '<p>This is a test message from AV Asset Manager email integration.</p>',
                $settings['from_address'],
                $settings['from_name'],
            );
        } catch (Throwable $e) {
            return back()->withErrors(['test' => 'Test send failed: '.$e->getMessage()]);
        }

        return back()->with('status', 'Test email sent to '.$to.'.');
    }
}
