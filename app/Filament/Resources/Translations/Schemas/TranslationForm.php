<?php

namespace App\Filament\Resources\Translations\Schemas;

use App\Models\Translation;
use App\Rules\KeepsPlaceholders;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TranslationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('key')
                    ->disabled()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (?Translation $record) => $record ? "{$record->group}.{$record->key}" : null),
                Textarea::make('en')
                    ->label('English')
                    ->required()
                    ->rows(3)
                    ->autosize()
                    ->helperText(fn (?Translation $record) => self::fileHint($record, 'en'))
                    ->rule(fn (?Translation $record) => new KeepsPlaceholders($record?->en_default)),
                Textarea::make('ru')
                    ->label('Russian')
                    ->rows(3)
                    ->autosize()
                    ->helperText(fn (?Translation $record) => self::fileHint($record, 'ru'))
                    ->rule(fn (?Translation $record) => new KeepsPlaceholders($record?->en_default)),
            ]);
    }

    private static function fileHint(?Translation $record, string $locale): ?string
    {
        if (! $record) {
            return null;
        }

        $default = $record->{"{$locale}_default"};

        if ($default === null || $default === '') {
            return 'No text in the language file: an empty value falls back to English.';
        }

        return $record->{$locale} === $default ? 'Same as the language file.' : "Language file: {$default}";
    }
}
