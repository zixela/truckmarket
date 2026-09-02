<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use App\Models\Setting;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    /**
     * The parent authenticate() calls $this->rateLimit(5) (5 attempts / 60s).
     * Override the limiter with admin-managed values from Settings:
     * lock after `admin_login_max_attempts` failures for `admin_login_lockout_minutes`.
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
    {
        $attempts = max(1, (int) Setting::get('admin_login_max_attempts', '4'));
        $lockoutSeconds = max(1, (int) Setting::get('admin_login_lockout_minutes', '5')) * 60;

        // Pass the method explicitly: the trait resolves it via debug_backtrace,
        // which would point at this override instead of the caller.
        return parent::rateLimit($attempts, $lockoutSeconds, $method ?? 'authenticate', $component);
    }

    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        // A successful login must not count towards the failed-attempt limit.
        if ($response !== null) {
            $this->clearRateLimiter('authenticate');
        }

        return $response;
    }
}
