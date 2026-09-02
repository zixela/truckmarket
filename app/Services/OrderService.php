<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Mail\OrderStatusMail;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    /**
     * Place an order on someone else's listing.
     *
     * @throws OrderException
     */
    public function place(Listing $listing, User $customer, ?string $message = null): Order
    {
        if ($listing->status !== ListingStatus::Active) {
            throw new OrderException(__('orders.listing_unavailable'));
        }

        if ($listing->user_id === $customer->id) {
            throw new OrderException(__('orders.cannot_order_own'));
        }

        if ($listing->user->hasBlocked($customer)) {
            throw new OrderException(__('orders.blocked_by_owner'));
        }

        $duplicate = Order::query()
            ->where('listing_id', $listing->id)
            ->where('customer_id', $customer->id)
            ->whereIn('status', OrderStatus::active())
            ->exists();

        if ($duplicate) {
            throw new OrderException(__('orders.already_ordered'));
        }

        $order = Order::query()->create([
            'listing_id' => $listing->id,
            'customer_id' => $customer->id,
            'owner_id' => $listing->user_id,
            'status' => OrderStatus::Pending,
            'message' => $message,
        ]);

        $this->notify($order, OrderStatus::Pending, $order->owner);

        return $order;
    }

    /** @throws OrderException */
    public function confirm(Order $order): Order
    {
        $this->assertStatus($order, [OrderStatus::Pending]);

        $order->update([
            'status' => OrderStatus::Confirmed,
            'confirmed_at' => now(),
        ] + $this->paymentRequest($order));

        $this->notify($order, OrderStatus::Confirmed, $order->customer);

        return $order;
    }

    /**
     * When payments are enabled (admin setting) and an amount is resolvable
     * (per-order amount set by admin, else the global default), request payment.
     *
     * @return array<string, mixed>
     */
    private function paymentRequest(Order $order): array
    {
        if (! Setting::bool('payments_enabled') || $order->isPaid()) {
            return [];
        }

        $amount = $order->payment_amount
            ?? (float) Setting::get('default_payment_amount', '0');

        if ((float) $amount <= 0) {
            return [];
        }

        return ['payment_amount' => $amount, 'payment_status' => 'pending'];
    }

    /** @throws OrderException */
    public function decline(Order $order, ?string $note = null): Order
    {
        $this->assertStatus($order, [OrderStatus::Pending]);

        $order->update(['status' => OrderStatus::Declined, 'response_note' => $note]);
        $this->notify($order, OrderStatus::Declined, $order->customer);

        return $order;
    }

    /** @throws OrderException */
    public function complete(Order $order): Order
    {
        $this->assertStatus($order, [OrderStatus::Confirmed]);

        $order->update(['status' => OrderStatus::Completed, 'completed_at' => now()]);
        $this->notify($order, OrderStatus::Completed, $order->customer);

        return $order;
    }

    /** @throws OrderException */
    public function cancel(Order $order): Order
    {
        $this->assertStatus($order, [OrderStatus::Pending]);

        $order->update(['status' => OrderStatus::Cancelled]);
        $this->notify($order, OrderStatus::Cancelled, $order->owner);

        return $order;
    }

    /** @param  array<OrderStatus>  $allowed */
    private function assertStatus(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw new OrderException(__('orders.invalid_transition'));
        }
    }

    private function notify(Order $order, OrderStatus $status, User $recipient): void
    {
        if (! $recipient->notify_by_email) {
            return;
        }

        $order->loadMissing(['listing', 'customer', 'owner']);

        Mail::to($recipient->email)
            ->locale($recipient->locale ?: config('app.locale'))
            ->queue(new OrderStatusMail($order, $status));
    }
}
