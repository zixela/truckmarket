<?php

namespace App\Filament\Widgets;

use App\Enums\ListingStatus;
use App\Enums\OrderStatus;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::count()),
            Stat::make('Active listings', Listing::query()->where('status', ListingStatus::Active)->count())
                ->description(Listing::query()->where('status', ListingStatus::PendingModeration)->count().' pending moderation'),
            Stat::make('Orders', Order::count())
                ->description(Order::query()->where('status', OrderStatus::Pending)->count().' pending'),
            Stat::make('Reviews', Review::count())
                ->description(Review::query()->where('is_hidden', true)->count().' hidden'),
        ];
    }
}
