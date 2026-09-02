<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class VerificationCodeController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.listings.index');
        }

        return view('auth.verify-code');
    }

    public function verify(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $key = 'verify:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['code' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        RateLimiter::hit($key, 300);

        if (! $verification->verify($request->user(), $data['code'])) {
            return back()->withErrors(['code' => __('auth.verify_invalid')]);
        }

        RateLimiter::clear($key);

        return redirect()
            ->route('account.listings.index')
            ->with('status', __('auth.verify_success'));
    }

    public function resend(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.listings.index');
        }

        $key = 'verify-resend:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => __('auth.verify_throttle')]);
        }

        RateLimiter::hit($key, 3600);
        $verification->issue($request->user());

        return back()->with('status', __('auth.verify_sent'));
    }
}
