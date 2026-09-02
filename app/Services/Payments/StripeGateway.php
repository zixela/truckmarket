<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class StripeGateway implements PaymentGateway
{
    private const API = 'https://api.stripe.com/v1';

    public function __construct(private string $secretKey) {}

    public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): ?CheckoutSession
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->asForm()
                ->post(self::API.'/checkout/sessions', [
                    'mode' => 'payment',
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'client_reference_id' => (string) $order->id,
                    'customer_email' => $order->customer->email,
                    'line_items[0][quantity]' => 1,
                    'line_items[0][price_data][currency]' => 'usd',
                    'line_items[0][price_data][unit_amount]' => (int) round((float) $order->payment_amount * 100),
                    'line_items[0][price_data][product_data][name]' => 'Order #'.$order->id.' — '.$order->listing->title,
                ]);
        } catch (Throwable $e) {
            Log::error('Stripe checkout create failed: '.$e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            Log::error('Stripe checkout create failed: '.$response->body());

            return null;
        }

        return new CheckoutSession($response->json('id'), $response->json('url'));
    }

    public function isPaid(string $sessionId): bool
    {
        try {
            $response = Http::withToken($this->secretKey)
                ->get(self::API.'/checkout/sessions/'.$sessionId);
        } catch (Throwable) {
            return false;
        }

        return $response->successful() && $response->json('payment_status') === 'paid';
    }
}
