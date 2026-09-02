<?php

namespace App\Filament\Resources\Reviews\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('author_id')
                    ->relationship('author', 'name')
                    ->required(),
                Select::make('subject_id')
                    ->relationship('subject', 'name')
                    ->required(),
                TextInput::make('score')
                    ->required()
                    ->numeric(),
                Toggle::make('is_positive')
                    ->required(),
                Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('reply')
                    ->columnSpanFull(),
                DateTimePicker::make('replied_at'),
                Toggle::make('is_hidden')
                    ->required(),
            ]);
    }
}
