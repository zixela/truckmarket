<?php

declare(strict_types=1);

namespace App\Services\CompanyVerification;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Verifies US trucking companies against the FMCSA QCMobile API by USDOT number.
 *
 * @see https://mobile.fmcsa.dot.gov/QCDevsite/ (free webKey registration)
 */
class FmcsaCompanyVerifier implements CompanyVerifier
{
    public function __construct(private string $webKey) {}

    public function verify(string $companyNumber, string $companyName): CompanyVerificationResult
    {
        $dot = preg_replace('/\D/', '', $companyNumber);

        if ($dot === '') {
            return CompanyVerificationResult::invalid('not_a_number');
        }

        try {
            $response = Http::timeout(10)
                ->get("https://mobile.fmcsa.dot.gov/qc/services/carriers/{$dot}", [
                    'webKey' => $this->webKey,
                ]);
        } catch (Throwable) {
            return CompanyVerificationResult::unknown('service_unreachable');
        }

        if ($response->status() === 404) {
            return CompanyVerificationResult::invalid('not_found');
        }

        if (! $response->successful()) {
            return CompanyVerificationResult::unknown('http_'.$response->status());
        }

        $carrier = $response->json('content.carrier');

        if (! is_array($carrier) || empty($carrier['legalName'])) {
            return CompanyVerificationResult::invalid('not_found');
        }

        if (($carrier['allowedToOperate'] ?? 'Y') === 'N') {
            return CompanyVerificationResult::invalid('not_allowed_to_operate');
        }

        $legalName = (string) $carrier['legalName'];
        $dbaName = (string) ($carrier['dbaName'] ?? '');

        if ($this->namesMatch($companyName, $legalName) || ($dbaName !== '' && $this->namesMatch($companyName, $dbaName))) {
            return CompanyVerificationResult::valid($legalName);
        }

        return CompanyVerificationResult::invalid('name_mismatch');
    }

    /** Tolerant comparison: case/punctuation-insensitive with a similarity threshold. */
    private function namesMatch(string $given, string $official): bool
    {
        $normalize = fn (string $value): string => trim(preg_replace(
            '/\s+/',
            ' ',
            preg_replace('/[^a-z0-9 ]/', '', Str::lower(Str::ascii($value)))
        ));

        $a = $normalize($given);
        $b = $normalize($official);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b || str_contains($b, $a) || str_contains($a, $b)) {
            return true;
        }

        similar_text($a, $b, $percent);

        return $percent >= 85.0;
    }
}
