<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\RevenueStats;
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

it('shows revenue totals from paid orders', function () {
    Order::factory()->status(OrderStatus::Completed)->create([
        'payment_amount' => 100.00, 'payment_status' => 'paid', 'paid_at' => now(),
    ]);
    Order::factory()->status(OrderStatus::Completed)->create([
        'payment_amount' => 50.50, 'payment_status' => 'paid', 'paid_at' => now()->subMonths(2),
    ]);
    Order::factory()->status(OrderStatus::Confirmed)->create([
        'payment_amount' => 30.00, 'payment_status' => 'pending',
    ]);

    Livewire::test(RevenueStats::class)
        ->assertSee('$150.50')   // total: both paid orders
        ->assertSee('$100.00')   // this month / today
        ->assertSee('$30.00')    // awaiting payment
        ->assertSee('2 paid orders')
        ->assertSee('1 unpaid orders');
});

it('renders the monthly revenue chart with paid sums in the right buckets', function () {
    Order::factory()->status(OrderStatus::Completed)->create([
        'payment_amount' => 70.00, 'payment_status' => 'paid', 'paid_at' => now(),
    ]);
    Order::factory()->status(OrderStatus::Completed)->create([
        'payment_amount' => 20.00, 'payment_status' => 'paid', 'paid_at' => now(),
    ]);

    $component = Livewire::test(RevenueChart::class);
    $component->assertOk();

    $data = (fn () => $this->getCachedData())->call($component->instance());

    expect(end($data['datasets'][0]['data']))->toBe(90.0)
        ->and(array_sum($data['datasets'][0]['data']))->toBe(90.0);
});
