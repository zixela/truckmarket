<?php

namespace App\Filament\Resources\ServiceCategories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->label('Name (English)')
                    ->required()
                    ->maxLength(100),
                TextInput::make('name_ru')
                    ->label('Name (Russian)')
                    ->required()
                    ->maxLength(100),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first.'),
                Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Inactive categories are hidden from users but stay on existing listings.'),
            ]);
    }
}
