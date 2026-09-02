<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Revenue by month (last 12 months)';

    protected int|string|array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $start = now()->subMonths(11)->startOfMonth();

        // Grouped in PHP to stay portable between MySQL and sqlite.
        $orders = Order::query()
            ->where('payment_status', 'paid')
            ->where('paid_at', '>=', $start)
            ->get(['paid_at', 'payment_amount']);

        $labels = [];
        $totals = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $totals[$key] = 0.0;
        }

        foreach ($orders as $order) {
            $key = $order->paid_at->format('Y-m');

            if (array_key_exists($key, $totals)) {
                $totals[$key] += (float) $order->payment_amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (USD)',
                    'data' => array_values($totals),
                    'backgroundColor' => '#f97316',
                ],
            ],
            'labels' => $labels,
        ];
    }
}
