<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Exceptions\OrderException;
use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $unreadCount = fn ($query) => $query
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at');

        return view('account.orders.index', [
            'incoming' => $user->receivedOrders()
                ->with(['listing', 'customer'])
                ->withCount(['messages as unread_count' => $unreadCount])
                ->latest()
                ->paginate(10, ['*'], 'incoming'),
            'outgoing' => $user->placedOrders()
                ->with(['listing', 'owner', 'review'])
                ->withCount(['messages as unread_count' => $unreadCount])
                ->latest()
                ->paginate(10, ['*'], 'outgoing'),
        ]);
    }

    public function show(Request $request, Order $order): View
    {
        $this->authorize('view', $order);

        $order->load(['listing', 'customer', 'owner', 'messages.sender']);

        // Returning from Stripe Checkout — verify and settle the payment.
        if ($request->query('payment') === 'success'
            && $request->user()->id === $order->customer_id
            && OrderPaymentController::settle($order, app(PaymentGateway::class))) {
            session()->flash('status', __('orders.payment_success'));
        }

        // Opening the thread marks the other party's messages as read.
        $order->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('account.orders.show', ['order' => $order]);
    }

    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->orders->place($listing, $request->user(), $data['message'] ?? null);
        } catch (OrderException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()
            ->route('account.orders.index')
            ->with('status', __('orders.created'));
    }

    public function confirm(Order $order): RedirectResponse
    {
        return $this->transition($order, 'respond', fn () => $this->orders->confirm($order), __('orders.confirmed'));
    }

    public function decline(Request $request, Order $order): RedirectResponse
    {
        $note = $request->validate(['response_note' => ['nullable', 'string', 'max:500']])['response_note'] ?? null;

        return $this->transition($order, 'respond', fn () => $this->orders->decline($order, $note), __('orders.declined'));
    }

    public function complete(Order $order): RedirectResponse
    {
        return $this->transition($order, 'respond', fn () => $this->orders->complete($order), __('orders.completed'));
    }

    public function cancel(Order $order): RedirectResponse
    {
        return $this->transition($order, 'act', fn () => $this->orders->cancel($order), __('orders.cancelled'));
    }

    private function transition(Order $order, string $ability, callable $action, string $message): RedirectResponse
    {
        $this->authorize($ability, $order);

        try {
            $action();
        } catch (OrderException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return back()->with('status', $message);
    }
}
