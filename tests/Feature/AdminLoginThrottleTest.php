<?php

use App\Enums\UserRole;
use App\Filament\Auth\Login;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    RateLimiter::clear(...array_fill(0, 1, sha1(Login::class.'|authenticate')));

    $this->admin = User::factory()->create(['email' => 'admin-throttle@test.dev']);
    $this->admin->assignRole(UserRole::Admin->value);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function attemptLogin(string $password)
{
    return Livewire::test(Login::class)
        ->fillForm(['email' => 'admin-throttle@test.dev', 'password' => $password])
        ->call('authenticate');
}

it('blocks the admin login for 5 minutes after 4 failed attempts', function () {
    foreach (range(1, 4) as $i) {
        attemptLogin('wrong-password')->assertHasFormErrors();
    }

    // 5th attempt — even with the correct password — is rate limited.
    attemptLogin('password')->assertNotified();
    expect(auth()->check())->toBeFalse();

    // After the 5-minute window the correct password works again.
    $this->travel(301)->seconds();
    attemptLogin('password');
    expect(auth()->check())->toBeTrue();
});

it('does not lock out after successful logins', function () {
    attemptLogin('wrong-password')->assertHasFormErrors();

    attemptLogin('password');
    expect(auth()->check())->toBeTrue();

    auth()->logout();
    expect(auth()->check())->toBeFalse();

    // Counter was cleared by the successful login — 4 fresh attempts available.
    foreach (range(1, 3) as $i) {
        attemptLogin('wrong-password');
        expect(auth()->check())->toBeFalse();
    }
    attemptLogin('password');
    expect(auth()->check())->toBeTrue();
});
