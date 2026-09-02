<?php

namespace App\Filament\Resources\Transactions\Tables;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('Order #')->sortable(),
                TextColumn::make('listing.title')->searchable()->limit(35),
                TextColumn::make('customer.name')->label('Payer')->searchable(),
                TextColumn::make('owner.name')->label('Provider')->searchable()->toggleable(),
                TextColumn::make('payment_amount')
                    ->label('Amount')
                    ->money('usd', divideBy: false)
                    ->sortable()
                    ->summarize(Sum::make()->money('usd', divideBy: false)->label('Total')),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'paid' ? 'success' : 'warning')
                    ->formatStateUsing(fn (string $state) => $state === 'paid' ? 'Paid' : 'Awaiting'),
                TextColumn::make('stripe_session_id')
                    ->label('Stripe session')
                    ->limit(24)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->label('Ordered at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Paid',
                        'pending' => 'Awaiting payment',
                    ]),
                Filter::make('paid_today')
                    ->label('Paid today')
                    ->query(fn (Builder $query) => $query->where('paid_at', '>=', now()->startOfDay())),
                Filter::make('paid_this_month')
                    ->label('Paid this month')
                    ->query(fn (Builder $query) => $query->where('paid_at', '>=', now()->startOfMonth())),
                Filter::make('paid_this_year')
                    ->label('Paid this year')
                    ->query(fn (Builder $query) => $query->where('paid_at', '>=', now()->startOfYear())),
            ])
            ->recordActions([
                Action::make('openOrder')
                    ->label('Order')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => OrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
