<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('description')
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Toggle::make('value')
                    ->label('Enabled')
                    ->visible(fn (Get $get) => $get('key') === 'payments_enabled')
                    ->formatStateUsing(fn ($state) => in_array($state, ['1', 'true', 'on', 'yes', true], true))
                    ->dehydrateStateUsing(fn ($state) => $state ? '1' : '0'),
                TextInput::make('value')
                    ->label('Value')
                    ->visible(fn (Get $get) => $get('key') !== 'payments_enabled'),
            ]);
    }
}
