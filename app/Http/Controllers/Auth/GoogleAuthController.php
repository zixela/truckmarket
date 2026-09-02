<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => __('auth.google_failed')]);
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $googleUser->getEmail())->first();

        if (! $user) {
            $user = User::query()->create([
                'name' => $googleUser->getName() ?: Str::before((string) $googleUser->getEmail(), '@'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'locale' => app()->getLocale(),
                'needs_role_selection' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        } elseif (! $user->google_id) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        if ($user->is_blocked) {
            return redirect()->route('login')->withErrors(['email' => __('auth.account_blocked')]);
        }

        Auth::login($user, true);

        return $user->needs_role_selection
            ? redirect()->route('auth.role.create')
            : redirect()->route('account.listings.index');
    }

    public function createRole(Request $request): View|RedirectResponse
    {
        if (! $request->user()->needs_role_selection) {
            return redirect()->route('account.listings.index');
        }

        return view('auth.choose-role', ['roles' => UserRole::registerable()]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_column(UserRole::registerable(), 'value'))],
        ]);

        $user = $request->user();
        $user->syncRoles([$data['role']]);
        $user->forceFill(['needs_role_selection' => false])->save();

        return redirect()->route('account.listings.index');
    }
}
