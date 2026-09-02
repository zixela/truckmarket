<?php

declare(strict_types=1);

namespace App\Services\Payments;

class CheckoutSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
    ) {}
}
