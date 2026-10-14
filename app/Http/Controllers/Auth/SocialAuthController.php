<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialAuthController extends Controller
{
    public function __construct(
        private SocialAuthService $socialAuth,
    ) {}

    public function redirect(string $provider): SymfonyRedirectResponse|RedirectResponse
    {
        return $this->socialAuth->redirectToProvider($provider);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        return $this->socialAuth->handleCallback($provider, $request);
    }
}
