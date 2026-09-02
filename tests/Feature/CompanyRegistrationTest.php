<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\CompanyVerification\CompanyVerificationResult;
use App\Services\CompanyVerification\CompanyVerifier;
use App\Services\CompanyVerification\FmcsaCompanyVerifier;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function companyPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'John Smith',
        'email' => 'company-reg@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::Company->value,
        'company_name' => 'Vmoon Corporation',
        'company_number' => '1234567',
        'company_phone' => '+15551234567',
    ], $overrides);
}

it('requires company fields when registering as a company', function () {
    $this->post('/en/register', companyPayload([
        'company_name' => '',
        'company_number' => '',
        'company_phone' => '',
    ]))->assertSessionHasErrors(['company_name', 'company_number', 'company_phone']);
});

it('does not require company fields for other roles', function () {
    $this->post('/en/register', companyPayload([
        'role' => UserRole::Driver->value,
        'company_name' => '',
        'company_number' => '',
        'company_phone' => '',
    ]))->assertRedirect('/en/verify-email');
});

it('stores company data and marks verified when the registry confirms', function () {
    $this->app->instance(CompanyVerifier::class, new class implements CompanyVerifier
    {
        public function verify(string $companyNumber, string $companyName): CompanyVerificationResult
        {
            return CompanyVerificationResult::valid('VMOON CORPORATION');
        }
    });

    $this->post('/en/register', companyPayload())->assertRedirect('/en/verify-email');

    $user = User::query()->where('email', 'company-reg@example.com')->first();
    expect($user->company_number)->toBe('1234567')
        ->and($user->company_phone)->toBe('+15551234567')
        ->and($user->company_verified_at)->not->toBeNull();
});

it('rejects registration when the registry says the company is invalid', function () {
    $this->app->instance(CompanyVerifier::class, new class implements CompanyVerifier
    {
        public function verify(string $companyNumber, string $companyName): CompanyVerificationResult
        {
            return CompanyVerificationResult::invalid('not_found');
        }
    });

    $this->post('/en/register', companyPayload())->assertSessionHasErrors('company_number');
    expect(User::query()->where('email', 'company-reg@example.com')->exists())->toBeFalse();
});

it('leaves company verification pending when no registry is configured', function () {
    $this->post('/en/register', companyPayload())->assertRedirect('/en/verify-email');

    expect(User::query()->where('email', 'company-reg@example.com')->first()->company_verified_at)->toBeNull();
});

it('forces company users to confirm the phone by SMS after email verification', function () {
    $user = User::factory()->create(['company_phone' => '+15551234567']);
    $user->assignRole(UserRole::Company->value);

    $this->actingAs($user)->get('/en/account/listings')->assertRedirect('/en/verify-phone');

    $user->verificationCodes()->create([
        'channel' => 'phone',
        'code_hash' => Hash::make('654321'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->actingAs($user)->post('/en/verify-phone', ['code' => '654321'])->assertRedirect();

    expect($user->fresh()->phone_verified_at)->not->toBeNull();
    $this->actingAs($user->fresh())->get('/en/account/listings')->assertOk();
});

it('rejects a wrong sms code', function () {
    $user = User::factory()->create(['company_phone' => '+15551234567']);
    $user->assignRole(UserRole::Company->value);

    $user->verificationCodes()->create([
        'channel' => 'phone',
        'code_hash' => Hash::make('654321'),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->actingAs($user)->post('/en/verify-phone', ['code' => '111111'])->assertSessionHasErrors('code');
    expect($user->fresh()->phone_verified_at)->toBeNull();
});

it('verifies a real carrier through the FMCSA API contract', function () {
    Http::fake([
        'mobile.fmcsa.dot.gov/*' => Http::response([
            'content' => [
                'carrier' => [
                    'legalName' => 'VMOON CORPORATION',
                    'dbaName' => null,
                    'allowedToOperate' => 'Y',
                ],
            ],
        ]),
    ]);

    $verifier = new FmcsaCompanyVerifier('test-key');

    expect($verifier->verify('1234567', 'Vmoon Corporation')->isValid())->toBeTrue()
        ->and($verifier->verify('1234567', 'Totally Different LLC')->isInvalid())->toBeTrue();
});

it('treats an unknown DOT number as invalid', function () {
    Http::fake(['mobile.fmcsa.dot.gov/*' => Http::response(null, 404)]);

    expect((new FmcsaCompanyVerifier('test-key'))->verify('999', 'Whatever')->isInvalid())->toBeTrue();
});
