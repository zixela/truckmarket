<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

it('shows the header badge with pending orders and unread messages', function () {
    $order = Order::factory()->status(OrderStatus::Pending)->create();

    $order->messages()->create([
        'sender_id' => $order->customer_id,
        'body' => 'Are we good on the price?',
    ]);

    $this->actingAs($order->owner)
        ->get('/en')
        ->assertSee(__('common.notifications_new_orders'))
        ->assertSee(__('common.notifications_unread_messages'));
});

it('shows no badge when there is nothing new', function () {
    $order = Order::factory()->status(OrderStatus::Completed)->create();

    $this->actingAs($order->owner)
        ->get('/en')
        ->assertDontSee(__('common.notifications_new_orders'));
});
