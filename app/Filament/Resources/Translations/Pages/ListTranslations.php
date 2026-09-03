<?php

namespace App\Filament\Resources\Translations\Pages;

use App\Filament\Resources\Translations\TranslationResource;
use App\Services\Translation\TranslationSync;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTranslations extends ListRecords
{
    protected static string $resource = TranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync from files')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->modalHeading('Sync from language files')
                ->modalDescription('Imports keys added to lang/*.php and refreshes the file defaults. Texts edited here are kept.')
                ->action(function () {
                    $result = app(TranslationSync::class)->run();

                    Notification::make()
                        ->title("Sync complete: {$result['created']} added, {$result['updated']} updated")
                        ->success()
                        ->send();
                }),
        ];
    }
}
