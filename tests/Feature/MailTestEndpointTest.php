<?php

use App\Enums\UserRole;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

it('lets an admin send a test mail from a URL, directly or through the queue', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);

    $this->actingAs($admin)
        ->getJson('/en/mail-test?to=ops@example.com')
        ->assertOk()
        ->assertJson(['ok' => true, 'to' => 'ops@example.com', 'queue' => false]);

    $this->actingAs($admin)
        ->getJson('/en/mail-test?to=ops@example.com&queue=1')
        ->assertOk()
        ->assertJson(['ok' => true, 'queue' => true]);

    Mail::assertQueued(VerificationCodeMail::class);
});

it('rejects guests, non-admins and bad addresses', function () {
    $this->getJson('/en/mail-test?to=ops@example.com')->assertUnauthorized();

    $user = User::factory()->create();
    $user->assignRole(UserRole::Driver->value);
    $this->actingAs($user)->getJson('/en/mail-test?to=ops@example.com')->assertForbidden();

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);
    $this->actingAs($admin)->getJson('/en/mail-test?to=not-an-email')->assertUnprocessable();
});
