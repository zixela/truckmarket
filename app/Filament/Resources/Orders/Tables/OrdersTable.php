<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('listing.title')->searchable()->limit(35),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('owner.name')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => match ($state) {
                        OrderStatus::Pending => 'warning',
                        OrderStatus::Confirmed => 'info',
                        OrderStatus::Completed => 'success',
                        OrderStatus::Declined, OrderStatus::Cancelled => 'gray',
                    }),
                TextColumn::make('payment_amount')->money('usd', divideBy: false)->label('Charge'),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->placeholder('—'),
                TextColumn::make('confirmed_at')->dateTime()->toggleable(),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
