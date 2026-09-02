<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $paid = fn () => Order::query()->where('payment_status', 'paid');

        $total = (float) $paid()->sum('payment_amount');
        $thisMonth = (float) $paid()->where('paid_at', '>=', now()->startOfMonth())->sum('payment_amount');
        $today = (float) $paid()->where('paid_at', '>=', now()->startOfDay())->sum('payment_amount');
        $paidCount = $paid()->count();

        $pendingSum = (float) Order::query()->where('payment_status', 'pending')->sum('payment_amount');
        $pendingCount = Order::query()->where('payment_status', 'pending')->count();

        return [
            Stat::make('Total revenue', '$'.number_format($total, 2))
                ->description($paidCount.' paid orders')
                ->color('success'),
            Stat::make('Revenue this month', '$'.number_format($thisMonth, 2)),
            Stat::make('Revenue today', '$'.number_format($today, 2)),
            Stat::make('Awaiting payment', '$'.number_format($pendingSum, 2))
                ->description($pendingCount.' unpaid orders')
                ->color('warning'),
        ];
    }
}
