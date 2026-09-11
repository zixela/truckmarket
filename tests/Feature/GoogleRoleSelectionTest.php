<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    // A user freshly created by the Google callback: verified e-mail, no role yet.
    $this->user = User::factory()->create(['google_id' => 'g-123', 'needs_role_selection' => true]);
});

it('shows the company fields on the role selection page', function () {
    $this->actingAs($this->user)
        ->get('/en/choose-role')
        ->assertOk()
        ->assertSee('name="company_number"', false)
        ->assertSee('name="company_phone"', false);
});

it('requires the company details when the company role is chosen', function () {
    $this->actingAs($this->user)
        ->post('/en/choose-role', ['role' => UserRole::Company->value])
        ->assertSessionHasErrors(['company_name', 'company_number', 'company_phone']);

    expect($this->user->refresh()->needs_role_selection)->toBeTrue();
});

it('stores the company details and continues to phone confirmation', function () {
    $this->actingAs($this->user)
        ->post('/en/choose-role', [
            'role' => UserRole::Company->value,
            'company_name' => 'Vmoon Corporation',
            'company_number' => '123-4567',
            'company_phone' => '+1 (555) 123-4567',
        ])
        ->assertRedirect('/en/verify-phone');

    $user = $this->user->refresh();

    expect($user->hasRole(UserRole::Company->value))->toBeTrue()
        ->and($user->needs_role_selection)->toBeFalse()
        ->and($user->company_name)->toBe('Vmoon Corporation')
        ->and($user->company_number)->toBe('1234567')
        ->and($user->company_phone)->toBe('+15551234567')
        ->and($user->needsPhoneVerification())->toBeTrue();
});

it('sends other roles straight to the account without company fields', function () {
    $this->actingAs($this->user)
        ->post('/en/choose-role', ['role' => UserRole::Driver->value])
        ->assertRedirect('/en/account/listings');

    $user = $this->user->refresh();

    expect($user->hasRole(UserRole::Driver->value))->toBeTrue()
        ->and($user->company_name)->toBeNull()
        ->and($user->needsPhoneVerification())->toBeFalse();
});
