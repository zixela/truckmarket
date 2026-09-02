<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;

/** Used when no Stripe key is configured. */
class NullGateway implements PaymentGateway
{
    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): ?CheckoutSession
    {
        return null;
    }

    public function isPaid(string $sessionId): bool
    {
        return false;
    }
}
