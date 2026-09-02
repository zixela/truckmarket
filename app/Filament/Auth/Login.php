<?php

declare(strict_types=1);

namespace App\Filament\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;

class Login extends BaseLogin
{
    private const MAX_ATTEMPTS = 4;

    private const DECAY_SECONDS = 300;

    /**
     * The parent authenticate() calls $this->rateLimit(5) (5 attempts / 60s).
     * Override the limiter itself: block for 5 minutes after 4 failed attempts.
     */
    protected function rateLimit($maxAttempts, $decaySeconds = 60, $method = null, $component = null)
    {
        // Pass the method explicitly: the trait resolves it via debug_backtrace,
        // which would point at this override instead of the caller.
        return parent::rateLimit(self::MAX_ATTEMPTS, self::DECAY_SECONDS, $method ?? 'authenticate', $component);
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
