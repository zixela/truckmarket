<?php

declare(strict_types=1);

namespace App\Services\CompanyVerification;

/** Used when no registry API key is configured — verification stays pending. */
class NullCompanyVerifier implements CompanyVerifier
{
    public function verify(string $companyNumber, string $companyName): CompanyVerificationResult
    {
        return CompanyVerificationResult::unknown('verifier_not_configured');
    }
}
