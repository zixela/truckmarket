<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::Admin->value);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($admin);
});

it('lists only orders with payment requests', function () {
    $paid = Order::factory()->status(OrderStatus::Completed)->create([
        'payment_amount' => 120.00, 'payment_status' => 'paid', 'paid_at' => now(),
    ]);
    $pending = Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 45.00, 'payment_status' => 'pending',
    ]);
    $noPayment = Order::factory()->status(OrderStatus::Pending)->create();

    Livewire::test(ListTransactions::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$paid, $pending])
        ->assertCanNotSeeTableRecords([$noPayment]);
});

it('filters transactions by payment status', function () {
    $paid = Order::factory()->status(OrderStatus::Completed)->create([
        'payment_amount' => 120.00, 'payment_status' => 'paid', 'paid_at' => now(),
    ]);
    $pending = Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 45.00, 'payment_status' => 'pending',
    ]);

    Livewire::test(ListTransactions::class)
        ->filterTable('payment_status', 'paid')
        ->assertCanSeeTableRecords([$paid])
        ->assertCanNotSeeTableRecords([$pending]);
});
