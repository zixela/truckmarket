<?php

declare(strict_types=1);

namespace App\Services\CompanyVerification;

class CompanyVerificationResult
{
    private function __construct(
        public readonly ?bool $valid,
        public readonly ?string $legalName = null,
        public readonly ?string $note = null,
    ) {}

    public static function valid(?string $legalName = null): self
    {
        return new self(true, $legalName);
    }

    public static function invalid(?string $note = null): self
    {
        return new self(false, null, $note);
    }

    /** Verification service unavailable / not configured — decide later. */
    public static function unknown(?string $note = null): self
    {
        return new self(null, null, $note);
    }

    public function isValid(): bool
    {
        return $this->valid === true;
    }

    public function isInvalid(): bool
    {
        return $this->valid === false;
    }
}
