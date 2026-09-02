<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Mail\OrderMessageMail;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Mail::fake();

    $this->order = Order::factory()->status(OrderStatus::Pending)->create();
    $this->customer = $this->order->customer;
    $this->owner = $this->order->owner;
});

it('lets both parties exchange messages on an order', function () {
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'Can you do $2000?'])
        ->assertRedirect();

    $this->actingAs($this->owner)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'Deal.'])
        ->assertRedirect();

    expect($this->order->messages()->count())->toBe(2);

    $this->actingAs($this->owner)
        ->get("/en/account/orders/{$this->order->id}")
        ->assertOk()
        ->assertSee('Can you do $2000?')
        ->assertSee('Deal.');
});

it('blocks third parties from the thread', function () {
    $stranger = User::factory()->create();
    $stranger->assignRole(UserRole::Driver->value);

    $this->actingAs($stranger)->get("/en/account/orders/{$this->order->id}")->assertForbidden();
    $this->actingAs($stranger)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'hi'])
        ->assertForbidden();
});

it('closes the composer on finished orders', function () {
    $this->order->update(['status' => OrderStatus::Declined]);

    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'hello?'])
        ->assertSessionHasErrors('message');

    expect($this->order->messages()->count())->toBe(0);
});

it('marks messages read when the recipient opens the thread and shows unread badges', function () {
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'ping']);

    $this->actingAs($this->owner)->get('/en/account/orders')->assertSee('1');

    $this->actingAs($this->owner)->get("/en/account/orders/{$this->order->id}")->assertOk();

    expect($this->order->messages()->whereNull('read_at')->count())->toBe(0);
});

it('emails the other party only for the first unread message', function () {
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'first']);
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'second']);

    Mail::assertQueued(OrderMessageMail::class, 1);
});
