<?php

namespace App\Filament\Resources\Transactions;

use App\Filament\Resources\Transactions\Pages\ListTransactions;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $slug = 'transactions';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'transaction';

    protected static ?string $pluralModelLabel = 'transactions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    /** Only orders that have a payment request (pending or paid). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('payment_status');
    }

    public static function table(Table $table): Table
    {
        return Tables\TransactionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
        ];
    }
}
