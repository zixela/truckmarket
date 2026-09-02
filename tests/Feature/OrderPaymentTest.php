<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OrderService;
use App\Services\Payments\CheckoutSession;
use App\Services\Payments\PaymentGateway;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();

    Setting::query()->updateOrCreate(['key' => 'payments_enabled'], ['value' => '1']);
    Setting::query()->updateOrCreate(['key' => 'default_payment_amount'], ['value' => null]);
});

function fakeGateway(bool $paid = true): PaymentGateway
{
    return new class($paid) implements PaymentGateway
    {
        public function __construct(private bool $paid) {}

        public function createCheckoutSession(Order $order, string $successUrl, string $cancelUrl): ?CheckoutSession
        {
            return new CheckoutSession('cs_test_123', 'https://checkout.stripe.test/session');
        }

        public function isPaid(string $sessionId): bool
        {
            return $this->paid;
        }
    };
}

it('requests payment on confirm when payments are enabled and an amount is set', function () {
    $order = Order::factory()->create(['payment_amount' => 25.00]);

    app(OrderService::class)->confirm($order);

    $order->refresh();
    expect($order->awaitsPayment())->toBeTrue()
        ->and((float) $order->payment_amount)->toBe(25.0);
});

it('uses the admin default amount when no per-order amount is set', function () {
    Setting::query()->find('default_payment_amount')->update(['value' => '10.50']);

    $order = Order::factory()->create();
    app(OrderService::class)->confirm($order);

    expect($order->refresh()->awaitsPayment())->toBeTrue()
        ->and((float) $order->payment_amount)->toBe(10.5);
});

it('does not request payment when the feature is disabled', function () {
    Setting::query()->find('payments_enabled')->update(['value' => '0']);

    $order = Order::factory()->create(['payment_amount' => 25.00]);
    app(OrderService::class)->confirm($order);

    expect($order->refresh()->awaitsPayment())->toBeFalse();
});

it('does not request payment when no amount is resolvable', function () {
    $order = Order::factory()->create();
    app(OrderService::class)->confirm($order);

    expect($order->refresh()->awaitsPayment())->toBeFalse();
});

it('sends the customer to Stripe checkout', function () {
    $this->app->instance(PaymentGateway::class, fakeGateway());

    $order = Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 25.00,
        'payment_status' => 'pending',
    ]);

    $this->actingAs($order->customer)
        ->post("/en/account/orders/{$order->id}/pay")
        ->assertRedirect('https://checkout.stripe.test/session');

    expect($order->refresh()->stripe_session_id)->toBe('cs_test_123');
});

it('blocks the owner from paying', function () {
    $order = Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 25.00,
        'payment_status' => 'pending',
    ]);

    $this->actingAs($order->owner)
        ->post("/en/account/orders/{$order->id}/pay")
        ->assertForbidden();
});

it('marks the order paid after a successful Stripe return', function () {
    $this->app->instance(PaymentGateway::class, fakeGateway(paid: true));

    $order = Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 25.00,
        'payment_status' => 'pending',
        'stripe_session_id' => 'cs_test_123',
    ]);

    $this->actingAs($order->customer)
        ->get("/en/account/orders/{$order->id}?payment=success")
        ->assertOk();

    expect($order->refresh()->isPaid())->toBeTrue()
        ->and($order->paid_at)->not->toBeNull();
});

it('does not mark paid when Stripe says the session is unpaid', function () {
    $this->app->instance(PaymentGateway::class, fakeGateway(paid: false));

    $order = Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 25.00,
        'payment_status' => 'pending',
        'stripe_session_id' => 'cs_test_123',
    ]);

    $this->actingAs($order->customer)
        ->get("/en/account/orders/{$order->id}?payment=success")
        ->assertOk();

    expect($order->refresh()->isPaid())->toBeFalse();
});
