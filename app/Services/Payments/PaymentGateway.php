<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    /** Create a hosted checkout session; returns [id, url] or null when unavailable. */
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): ?CheckoutSession;

    /** Whether the given checkout session has been paid. */
    public function isPaid(string $sessionId): bool;
}
