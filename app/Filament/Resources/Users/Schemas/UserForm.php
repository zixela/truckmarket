<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => UserRole::tryFrom($record->name)?->label() ?? $record->name)
                    ->preload(),
                TextInput::make('company_name'),
                TextInput::make('company_number')->label('Company number (USDOT)'),
                TextInput::make('company_phone')->tel(),
                DateTimePicker::make('company_verified_at'),
                DateTimePicker::make('phone_verified_at'),
                TextInput::make('phone')->tel(),
                TextInput::make('address'),
                TextInput::make('residency'),
                TextInput::make('zip'),
                Select::make('locale')
                    ->options(['en' => 'English', 'ru' => 'Русский'])
                    ->required()
                    ->default('en'),
                Toggle::make('is_blocked'),
                Toggle::make('notify_by_email')->default(true),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create'),
            ]);
    }
}
