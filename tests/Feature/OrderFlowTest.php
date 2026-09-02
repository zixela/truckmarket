<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Exceptions\OrderException;
use App\Models\Blacklist;
use App\Models\Listing;
use App\Models\User;
use App\Services\OrderService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();

    $this->owner = User::factory()->create();
    $this->owner->assignRole(UserRole::Company->value);
    $this->customer = User::factory()->create();
    $this->customer->assignRole(UserRole::Driver->value);
    $this->listing = Listing::factory()->create(['user_id' => $this->owner->id]);
    $this->service = app(OrderService::class);
});

it('places an order and walks the happy path to review', function () {
    $order = $this->service->place($this->listing, $this->customer, 'Hello');
    expect($order->status)->toBe(OrderStatus::Pending);

    $this->service->confirm($order);
    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    $this->service->complete($order->fresh());
    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->fresh()->isReviewable())->toBeTrue();
});

it('rejects ordering your own listing', function () {
    $this->service->place($this->listing, $this->owner);
})->throws(OrderException::class);

it('rejects duplicate open orders', function () {
    $this->service->place($this->listing, $this->customer);
    $this->service->place($this->listing, $this->customer);
})->throws(OrderException::class);

it('blocks blacklisted customers', function () {
    Blacklist::query()->create([
        'user_id' => $this->owner->id,
        'blocked_user_id' => $this->customer->id,
    ]);

    $this->service->place($this->listing, $this->customer);
})->throws(OrderException::class);

it('cannot complete an order that was never confirmed', function () {
    $order = $this->service->place($this->listing, $this->customer);
    $this->service->complete($order);
})->throws(OrderException::class);

it('lets the customer cancel only while pending', function () {
    $order = $this->service->place($this->listing, $this->customer);
    $this->service->confirm($order);

    $this->service->cancel($order->fresh());
})->throws(OrderException::class);

it('enforces owner-only confirmation over http', function () {
    $order = $this->service->place($this->listing, $this->customer);

    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$order->id}/confirm")
        ->assertForbidden();

    $this->actingAs($this->owner)
        ->post("/en/account/orders/{$order->id}/confirm")
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);
});
