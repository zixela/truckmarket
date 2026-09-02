<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()
                ->route('verification.notice')
                ->with('status', __('auth.verify_required'));
        }

        if ($user && $user->needs_role_selection) {
            return redirect()->route('auth.role.create');
        }

        if ($user && $user->needsPhoneVerification()) {
            return redirect()->route('verification.phone.notice');
        }

        return $next($request);
    }
}
