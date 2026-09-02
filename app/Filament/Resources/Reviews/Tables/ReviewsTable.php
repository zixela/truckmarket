<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Services\RatingService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('author.name')->searchable(),
                TextColumn::make('subject.name')->searchable(),
                TextColumn::make('score')->sortable(),
                IconColumn::make('is_positive')->boolean(),
                TextColumn::make('body')->limit(50)->wrap(),
                IconColumn::make('is_hidden')->boolean(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_positive'),
                TernaryFilter::make('is_hidden'),
            ])
            ->recordActions([
                Action::make('toggleHidden')
                    ->label(fn ($record) => $record->is_hidden ? 'Show' : 'Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->color(fn ($record) => $record->is_hidden ? 'success' : 'danger')
                    ->action(function ($record) {
                        $record->update(['is_hidden' => ! $record->is_hidden]);
                        app(RatingService::class)->forget($record->subject_id);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
