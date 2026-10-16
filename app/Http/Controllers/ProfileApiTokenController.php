<?php

namespace App\Http\Controllers;

use App\Support\ApiTokenAbilities;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;

class ProfileApiTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'current_password'],
            'abilities' => ['sometimes', 'nullable', 'array'],
            'abilities.*' => ['string', Rule::in(ApiTokenAbilities::all())],
        ]);

        $abilities = ApiTokenAbilities::normalize($data['abilities'] ?? null);

        $newToken = $request->user()->createToken($data['name'], $abilities);

        return redirect()
            ->route('profile.edit')
            ->with('status', 'api-token-created')
            ->with('api_token_plain', $newToken->plainTextToken)
            ->with('api_token_name', $data['name']);
    }

    public function destroy(Request $request, PersonalAccessToken $token): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $token->tokenable_id === $user->id
            && $token->tokenable_type === $user->getMorphClass(),
            404,
        );

        $token->delete();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'api-token-revoked');
    }
}
