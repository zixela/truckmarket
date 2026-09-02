<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    public function __construct(private PaymentGateway $gateway) {}

    /** Start a Stripe Checkout session and send the customer to it. */
    public function pay(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('act', $order);

        if (! $order->awaitsPayment()) {
            return back()->withErrors(['payment' => __('orders.payment_not_required')]);
        }

        $order->loadMissing(['listing', 'customer']);

        $session = $this->gateway->createCheckoutSession(
            $order,
            route('account.orders.show', ['order' => $order]).'?payment=success',
            route('account.orders.show', ['order' => $order]).'?payment=cancelled',
        );

        if (! $session) {
            return back()->withErrors(['payment' => __('orders.payment_unavailable')]);
        }

        $order->update(['stripe_session_id' => $session->id]);

        return redirect()->away($session->url);
    }

    /** Called from OrderController::show when the customer returns from Stripe. */
    public static function settle(Order $order, PaymentGateway $gateway): bool
    {
        if (! $order->awaitsPayment() || ! $order->stripe_session_id) {
            return false;
        }

        if (! $gateway->isPaid($order->stripe_session_id)) {
            return false;
        }

        $order->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();

        return true;
    }
}
