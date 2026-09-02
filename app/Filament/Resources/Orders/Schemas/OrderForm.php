<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('listing_id')
                    ->relationship('listing', 'title')
                    ->required(),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('pending')
                    ->required(),
                Textarea::make('message')
                    ->columnSpanFull(),
                Textarea::make('response_note')
                    ->columnSpanFull(),
                DateTimePicker::make('confirmed_at'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
