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

it('shows the read status on own messages', function () {
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'ping']);

    $this->actingAs($this->customer)
        ->get("/en/account/orders/{$this->order->id}")
        ->assertSee('✓ Sent')
        ->assertDontSee('✓✓ Read');

    $this->actingAs($this->owner)->get("/en/account/orders/{$this->order->id}");

    $this->actingAs($this->customer)
        ->get("/en/account/orders/{$this->order->id}")
        ->assertSee('✓✓ Read');
});

it('lets the recipient like and unlike a message', function () {
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'Deal?']);
    $message = $this->order->messages()->first();

    $this->actingAs($this->customer)
        ->get("/en/account/orders/{$this->order->id}")
        ->assertDontSee('title="Liked"', false);

    $this->actingAs($this->owner)
        ->post("/en/account/orders/{$this->order->id}/messages/{$message->id}/like")
        ->assertRedirect();

    expect($message->refresh()->liked_at)->not->toBeNull();

    $this->actingAs($this->customer)
        ->get("/en/account/orders/{$this->order->id}")
        ->assertSee('title="Liked"', false);

    $this->actingAs($this->owner)
        ->post("/en/account/orders/{$this->order->id}/messages/{$message->id}/like");

    expect($message->refresh()->liked_at)->toBeNull();
});

it('rejects likes on own messages, other orders and closed threads', function () {
    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => 'mine']);
    $message = $this->order->messages()->first();

    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages/{$message->id}/like")
        ->assertForbidden();

    $otherOrder = Order::factory()->status(OrderStatus::Pending)->create();
    $this->actingAs($otherOrder->owner)
        ->post("/en/account/orders/{$otherOrder->id}/messages/{$message->id}/like")
        ->assertNotFound();

    $this->order->update(['status' => OrderStatus::Completed]);
    $this->actingAs($this->owner)
        ->post("/en/account/orders/{$this->order->id}/messages/{$message->id}/like")
        ->assertSessionHasErrors('message');

    expect($message->refresh()->liked_at)->toBeNull();
});

it('offers quick emojis and stores them in messages', function () {
    $this->actingAs($this->customer)
        ->get("/en/account/orders/{$this->order->id}")
        ->assertSee('👍')
        ->assertSee("insert('🔥')", false);

    $this->actingAs($this->customer)
        ->post("/en/account/orders/{$this->order->id}/messages", ['body' => '👍 Deal 🔥']);

    expect($this->order->messages()->value('body'))->toBe('👍 Deal 🔥');
});
