<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\UserRole;

/**
 * Validation rules and normalisation for the company-only sign-up fields,
 * shared by email registration and the Google role-selection step.
 */
final class CompanyFields
{
    /** @return array<string, array<int, string>> */
    public static function rules(): array
    {
        $requiredForCompany = 'required_if:role,'.UserRole::Company->value;

        return [
            'company_name' => [$requiredForCompany, 'nullable', 'string', 'max:150'],
            'company_number' => [$requiredForCompany, 'nullable', 'string', 'max:32', 'regex:/^[0-9\- ]+$/'],
            'company_phone' => [$requiredForCompany, 'nullable', 'string', 'max:40', 'regex:/^\+?[0-9\-\s()]{7,}$/'],
        ];
    }

    /**
     * Normalised column values (digits-only USDOT, digits/+ phone); nulls for non-company roles.
     *
     * @param  array<string, mixed>  $data  validated input including 'role'
     * @return array{company_name: ?string, company_number: ?string, company_phone: ?string}
     */
    public static function attributes(array $data): array
    {
        if (($data['role'] ?? null) !== UserRole::Company->value) {
            return ['company_name' => null, 'company_number' => null, 'company_phone' => null];
        }

        return [
            'company_name' => $data['company_name'],
            'company_number' => preg_replace('/\D/', '', $data['company_number']),
            'company_phone' => preg_replace('/[^0-9+]/', '', $data['company_phone']),
        ];
    }
}
