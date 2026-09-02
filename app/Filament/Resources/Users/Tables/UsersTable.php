<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => UserRole::tryFrom($state)?->label() ?? $state),
                TextColumn::make('company_name')->searchable()->toggleable(),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->email_verified_at !== null),
                IconColumn::make('is_blocked')->boolean(),
                TextColumn::make('listings_count')->counts('listings')->label('Listings'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $role) => $q->whereHas('roles', fn ($r) => $r->where('name', $role))
                    )),
                TernaryFilter::make('is_blocked'),
            ])
            ->recordActions([
                Action::make('toggleBlock')
                    ->label(fn ($record) => $record->is_blocked ? 'Unblock' : 'Block')
                    ->icon('heroicon-o-no-symbol')
                    ->color(fn ($record) => $record->is_blocked ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_blocked' => ! $record->is_blocked])),
                Action::make('verify')
                    ->label('Verify')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn ($record) => $record->email_verified_at === null)
                    ->action(fn ($record) => $record->forceFill(['email_verified_at' => now()])->save()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
