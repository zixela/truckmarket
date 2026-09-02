<?php

declare(strict_types=1);

namespace App\Services\CompanyVerification;

interface CompanyVerifier
{
    /** Check a company registration number against the official registry and compare the name. */
    public function verify(string $companyNumber, string $companyName): CompanyVerificationResult;
}
