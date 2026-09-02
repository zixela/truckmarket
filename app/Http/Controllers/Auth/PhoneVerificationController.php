<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PhoneVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class PhoneVerificationController extends Controller
{
    public function notice(Request $request, PhoneVerificationService $verification): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->needsPhoneVerification()) {
            return redirect()->route('account.listings.index');
        }

        // Auto-issue the first code when the user lands here.
        $issueKey = 'phone-issue:'.$user->id;

        if (! $user->verificationCodes()->where('channel', 'phone')->whereNull('used_at')->where('expires_at', '>', now())->exists()
            && ! RateLimiter::tooManyAttempts($issueKey, 3)) {
            RateLimiter::hit($issueKey, 3600);
            $verification->issue($user);
        }

        return view('auth.verify-phone');
    }

    public function verify(Request $request, PhoneVerificationService $verification): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $key = 'phone-verify:'.$request->user()->id;

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
            ->with('status', __('auth.phone_verified'));
    }

    public function resend(Request $request, PhoneVerificationService $verification): RedirectResponse
    {
        if (! $request->user()->needsPhoneVerification()) {
            return redirect()->route('account.listings.index');
        }

        $key = 'phone-resend:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => __('auth.verify_throttle')]);
        }

        RateLimiter::hit($key, 3600);
        $verification->issue($request->user());

        return back()->with('status', __('auth.sms_sent'));
    }
}
