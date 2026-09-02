<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/** Development driver — writes the SMS to laravel.log instead of sending. */
class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): void
    {
        Log::info("[SMS to {$to}] {$message}");
    }
}
