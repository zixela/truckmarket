<?php

namespace App\Filament\Resources\Listings\Tables;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Services\ListingCache;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                SpatieMediaLibraryImageColumn::make('photos')
                    ->collection(Listing::PHOTO_COLLECTION)
                    ->conversion('card')
                    ->circular()
                    ->stacked()
                    ->limit(3),
                TextColumn::make('user.name')->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (ListingType $state) => $state->label()),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('price')->money('usd', divideBy: false)->sortable(),
                TextColumn::make('zip'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ListingStatus $state) => $state->label())
                    ->color(fn (ListingStatus $state) => match ($state) {
                        ListingStatus::Active => 'success',
                        ListingStatus::PendingModeration => 'warning',
                        ListingStatus::Rejected => 'danger',
                        ListingStatus::Inactive => 'gray',
                    }),
                TextColumn::make('views')->numeric()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(collect(ListingType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])),
                SelectFilter::make('status')
                    ->options(collect(ListingStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status !== ListingStatus::Active)
                    ->action(function ($record) {
                        $record->update(['status' => ListingStatus::Active, 'moderation_note' => null]);
                        app(ListingCache::class)->flush();
                    }),
                Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== ListingStatus::Rejected)
                    ->schema([
                        Textarea::make('moderation_note')->label('Reason'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => ListingStatus::Rejected,
                            'moderation_note' => $data['moderation_note'] ?? null,
                        ]);
                        app(ListingCache::class)->flush();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
