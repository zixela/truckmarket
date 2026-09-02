<?php

use App\Enums\UserRole;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Services\EmailVerificationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('registers a user with a role and sends a verification code', function () {
    Mail::fake();

    $response = $this->post('/en/register', [
        'name' => 'John Smith',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::Driver->value,
    ]);

    $response->assertRedirect('/en/verify-email');

    $user = User::query()->where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(UserRole::Driver->value))->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->verificationCodes()->count())->toBe(1);

    Mail::assertQueued(VerificationCodeMail::class);
});

it('verifies the email with a valid code', function () {
    $user = User::factory()->unverified()->create();
    $user->assignRole(UserRole::Driver->value);

    $user->verificationCodes()->create([
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->actingAs($user)->post('/en/verify-email', ['code' => '123456']);

    $response->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid or expired code', function () {
    $user = User::factory()->unverified()->create();
    $user->assignRole(UserRole::Driver->value);

    $user->verificationCodes()->create([
        'code_hash' => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)->post('/en/verify-email', ['code' => '123456'])->assertSessionHasErrors('code');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('blocks unverified users from the account area', function () {
    $user = User::factory()->unverified()->create();
    $user->assignRole(UserRole::Driver->value);

    $this->actingAs($user)
        ->get('/en/account/listings')
        ->assertRedirect('/en/verify-email');
});

it('invalidates old codes when a new one is issued', function () {
    $user = User::factory()->unverified()->create();

    $service = app(EmailVerificationService::class);
    Mail::fake();
    $service->issue($user);
    $service->issue($user);

    expect($user->verificationCodes()->whereNull('used_at')->count())->toBe(1);
});
