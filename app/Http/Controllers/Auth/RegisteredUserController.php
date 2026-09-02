<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CompanyVerification\CompanyVerifier;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', ['roles' => UserRole::registerable()]);
    }

    public function store(
        Request $request,
        EmailVerificationService $verification,
        CompanyVerifier $companyVerifier,
    ): RedirectResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(array_column(UserRole::registerable(), 'value'))],
            'company_name' => ['required_if:role,'.UserRole::Company->value, 'nullable', 'string', 'max:150'],
            'company_number' => ['required_if:role,'.UserRole::Company->value, 'nullable', 'string', 'max:32', 'regex:/^[0-9\- ]+$/'],
            'company_phone' => ['required_if:role,'.UserRole::Company->value, 'nullable', 'string', 'max:40', 'regex:/^\+?[0-9\-\s()]{7,}$/'],
        ]);

        $isCompany = $data['role'] === UserRole::Company->value;
        $companyVerifiedAt = null;

        if ($isCompany) {
            $result = $companyVerifier->verify($data['company_number'], $data['company_name']);

            if ($result->isInvalid()) {
                return back()
                    ->withInput()
                    ->withErrors(['company_number' => __('auth.company_invalid')]);
            }

            if ($result->isValid()) {
                $companyVerifiedAt = now();
            }
        }

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => app()->getLocale(),
            'company_name' => $isCompany ? $data['company_name'] : null,
            'company_number' => $isCompany ? preg_replace('/\D/', '', $data['company_number']) : null,
            'company_phone' => $isCompany ? preg_replace('/[^0-9+]/', '', $data['company_phone']) : null,
        ]);

        if ($companyVerifiedAt) {
            $user->forceFill(['company_verified_at' => $companyVerifiedAt])->save();
        }

        $user->assignRole($data['role']);

        Auth::login($user);
        $verification->issue($user);

        return redirect()->route('verification.notice');
    }
}
