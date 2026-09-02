<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TwilioSmsSender implements SmsSender
{
    public function __construct(
        private string $sid,
        private string $token,
        private string $from,
    ) {}

    public function send(string $to, string $message): void
    {
        $response = Http::withBasicAuth($this->sid, $this->token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->sid}/Messages.json", [
                'To' => $to,
                'From' => $this->from,
                'Body' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Twilio SMS failed: '.$response->body());
        }
    }
}
