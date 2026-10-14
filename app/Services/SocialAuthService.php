<?php

namespace App\Services;

use App\Models\User;
use App\Support\SsoSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthService
{
    public function redirectToProvider(string $provider): SymfonyRedirectResponse|RedirectResponse
    {
        if (! $this->providerIsAvailable($provider)) {
            return $this->deny('Single sign-on is not available.');
        }

        $driver = Socialite::driver($provider);

        if ($provider === 'google') {
            $domains = SsoSettings::allowedDomains();
            if (count($domains) === 1) {
                $driver = $driver->with(['hd' => $domains[0]]);
            }
        }

        return $driver->redirect();
    }

    public function handleCallback(string $provider, Request $request): RedirectResponse
    {
        if (! $this->providerIsAvailable($provider)) {
            return $this->deny('Single sign-on is not available.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return $this->deny('Could not sign in with your organisation account. Please try again or use your password.');
        }

        $email = $socialUser->getEmail();

        if (! is_string($email) || trim($email) === '') {
            return $this->deny('Your sign-in provider did not return an email address.');
        }

        if (! $this->emailIsVerifiedByProvider($provider, $socialUser)) {
            return $this->deny('Your email address must be verified by the sign-in provider.');
        }

        if (! SsoSettings::isDomainAllowed($email)) {
            return $this->deny('Your email domain is not allowed to sign in here.');
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower($email)])
            ->first();

        if ($user === null) {
            return $this->deny('No account exists for this email. Contact an administrator.');
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function providerIsAvailable(string $provider): bool
    {
        return SsoSettings::isVisible($provider);
    }

    private function emailIsVerifiedByProvider(string $provider, SocialiteUser $socialUser): bool
    {
        if ($provider === 'google') {
            $raw = $socialUser->getRaw();

            return (bool) ($raw['email_verified'] ?? false);
        }

        return filled($socialUser->getEmail());
    }

    private function deny(string $message): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }
}
