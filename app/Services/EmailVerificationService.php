<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public const TTL_MINUTES = 15;

    private const MAX_ATTEMPTS = 5;

    /** Issue a fresh code, invalidating any previous unused ones. */
    public function issue(User $user): void
    {
        $user->verificationCodes()->where('channel', 'email')->whereNull('used_at')->update(['used_at' => now()]);

        $code = (string) random_int(100000, 999999);

        $user->verificationCodes()->create([
            'channel' => 'email',
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        Mail::to($user->email)
            ->locale($user->locale ?: config('app.locale'))
            ->queue(new VerificationCodeMail($code));
    }

    /** Verify a submitted code and mark the user verified on success. */
    public function verify(User $user, string $code): bool
    {
        $record = $user->verificationCodes()
            ->where('channel', 'email')
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
        $user->forceFill(['email_verified_at' => now()])->save();

        return true;
    }
}
