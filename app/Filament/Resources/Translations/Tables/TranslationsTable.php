<?php

namespace App\Filament\Resources\Translations\Tables;

use App\Models\Translation;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TranslationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderBy('group')->orderBy('key'))
            ->columns([
                TextColumn::make('group')->badge()->sortable(),
                TextColumn::make('key')->searchable()->sortable(),
                TextColumn::make('en')->label('English')->searchable()->wrap()->limit(120),
                TextColumn::make('ru')
                    ->label('Russian')
                    ->searchable()
                    ->wrap()
                    ->limit(120)
                    ->placeholder('Falls back to English'),
                IconColumn::make('customized')
                    ->label('Edited')
                    ->boolean()
                    ->state(fn (Translation $record) => $record->isCustomized()),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options(fn () => Translation::query()
                        ->distinct()
                        ->orderBy('group')
                        ->pluck('group')
                        ->mapWithKeys(fn (string $group) => [$group => $group])
                        ->all()),
                Filter::make('missing_ru')
                    ->label('Missing Russian')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where(
                        fn (Builder $q) => $q->whereNull('ru')->orWhere('ru', '')
                    )),
                Filter::make('customized')
                    ->label('Edited in admin')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->where(fn (Builder $q) => $q
                        ->whereRaw("COALESCE(en, '') <> COALESCE(en_default, '')")
                        ->orWhereRaw("COALESCE(ru, '') <> COALESCE(ru_default, '')"))),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth(Width::TwoExtraLarge),
                Action::make('reset')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('gray')
                    ->visible(fn (Translation $record) => $record->isCustomized())
                    ->requiresConfirmation()
                    ->modalHeading('Reset to the language file?')
                    ->modalDescription('Both texts go back to the values from lang/*.php.')
                    ->action(fn (Translation $record) => $record->update([
                        'en' => $record->en_default,
                        'ru' => $record->ru_default,
                    ])),
            ])
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
