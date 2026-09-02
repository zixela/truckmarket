<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\Hash;

class PhoneVerificationService
{
    public const TTL_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private SmsSender $sms) {}

    /** Issue a fresh SMS code for the user's company phone, invalidating previous ones. */
    public function issue(User $user): void
    {
        if (! $user->company_phone) {
            return;
        }

        $user->verificationCodes()->where('channel', 'phone')->whereNull('used_at')->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $user->verificationCodes()->create([
            'channel' => 'phone',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        $this->sms->send($user->company_phone, __('auth.sms_code_text', ['code' => $code]));
    }

    public function verify(User $user, string $code): bool
    {
        $record = $user->verificationCodes()
            ->where('channel', 'phone')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->latest('id')
            ->first();

        if (! $record) {
            return false;
        }

        $record->increment('attempts');

        if (! Hash::check($code, $record->code_hash)) {
            return false;
        }

        $record->update(['used_at' => now()]);
        $user->forceFill(['phone_verified_at' => now()])->save();

        return true;
    }
}
