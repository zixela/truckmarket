<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.settings', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'residency' => ['nullable', 'string', 'max:150'],
            'zip' => ['nullable', 'string', 'max:16'],
            'locale' => ['required', Rule::in(SetLocale::SUPPORTED)],
            'notify_by_email' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->fill([
            'name' => $data['name'],
            'company_name' => $data['company_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'residency' => $data['residency'] ?? null,
            'zip' => $data['zip'] ?? null,
            'locale' => $data['locale'],
            'notify_by_email' => $request->boolean('notify_by_email'),
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('account.settings.edit', ['locale' => $data['locale']])
            ->with('status', __('account.saved'));
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $request->user()
            ->addMediaFromRequest('avatar')
            ->toMediaCollection('avatar');

        return back()->with('status', __('account.avatar_updated'));
    }
}
