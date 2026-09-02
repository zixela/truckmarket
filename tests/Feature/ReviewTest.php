<?php

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Services\RatingService;
use App\Services\ReviewService;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->service = app(ReviewService::class);
});

it('allows the customer of a completed order to review once', function () {
    $order = Order::factory()->status(OrderStatus::Completed)->create();

    $review = $this->service->leave($order, $order->customer, 5, true, 'Great!');

    expect($review->subject_id)->toBe($order->owner_id);

    $summary = app(RatingService::class)->summary($order->owner_id);
    expect($summary['count'])->toBe(1)->and($summary['average'])->toBe(5.0);

    $this->service->leave($order->fresh(), $order->customer, 4, true, 'Again');
})->throws(OrderException::class);

it('rejects reviews on non-completed orders', function () {
    $order = Order::factory()->status(OrderStatus::Confirmed)->create();

    $this->service->leave($order, $order->customer, 5, true, 'Too early');
})->throws(OrderException::class);

it('rejects reviews from the listing owner', function () {
    $order = Order::factory()->status(OrderStatus::Completed)->create();

    $this->service->leave($order, $order->owner, 5, true, 'Self review');
})->throws(OrderException::class);

it('lets the subject reply exactly once', function () {
    $order = Order::factory()->status(OrderStatus::Completed)->create();
    $review = $this->service->leave($order, $order->customer, 4, true, 'Nice');

    $this->service->reply($review, $order->owner, 'Thank you!');
    expect($review->fresh()->reply)->toBe('Thank you!');

    $this->service->reply($review->fresh(), $order->owner, 'Second reply');
})->throws(OrderException::class);
