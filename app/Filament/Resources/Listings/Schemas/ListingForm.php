<?php

namespace App\Filament\Resources\Listings\Schemas;

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\ServiceCategory;
use Closure;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->options(ListingType::class)
                    ->live()
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('zip'),
                Select::make('status')
                    ->options(ListingStatus::class)
                    ->default('active')
                    ->required(),
                Textarea::make('moderation_note')
                    ->columnSpanFull(),

                SpatieMediaLibraryFileUpload::make('photos')
                    ->collection(Listing::PHOTO_COLLECTION)
                    ->disk('public')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->maxFiles(Listing::MAX_PHOTOS)
                    ->maxSize(5120)
                    ->columnSpanFull(),

                Fieldset::make(ListingType::Truck->label())
                    ->relationship('truckDetail')
                    ->visible(self::isType(ListingType::Truck))
                    ->schema([
                        Select::make('deal')
                            ->options(['sell' => __('listings.deals.sell'), 'rent' => __('listings.deals.rent')])
                            ->default('sell')
                            ->required(),
                        TextInput::make('make_model'),
                        Select::make('cab_type')
                            ->options([
                                'sleeper' => __('listings.cab_types.sleeper'),
                                'day_cab' => __('listings.cab_types.day_cab'),
                            ]),
                        TextInput::make('year')->numeric()->minValue(1950)->maxValue(2100),
                        TextInput::make('mileage')->numeric()->minValue(0),
                    ]),

                Fieldset::make(ListingType::Trailer->label())
                    ->relationship('trailerDetail')
                    ->visible(self::isType(ListingType::Trailer))
                    ->schema([
                        Select::make('deal')
                            ->options(['sell' => __('listings.deals.sell'), 'rent' => __('listings.deals.rent')])
                            ->default('sell')
                            ->required(),
                        Select::make('trailer_type')
                            ->options([
                                'flatbed' => __('listings.trailer_types.flatbed'),
                                'reefer' => __('listings.trailer_types.reefer'),
                                'dry_van' => __('listings.trailer_types.dry_van'),
                            ]),
                        TextInput::make('year')->numeric()->minValue(1950)->maxValue(2100),
                    ]),

                Fieldset::make(ListingType::Load->label())
                    ->relationship('loadDetail')
                    ->visible(self::isType(ListingType::Load))
                    ->schema([
                        Select::make('load_type')
                            ->options([
                                'car_hauler' => __('listings.load_types.car_hauler'),
                                'flatbed' => __('listings.load_types.flatbed'),
                                'reefer' => __('listings.load_types.reefer'),
                                'dry_van' => __('listings.load_types.dry_van'),
                            ]),
                        Select::make('vehicle_type')
                            ->options([
                                'sedan' => __('listings.vehicle_types.sedan'),
                                'suv' => __('listings.vehicle_types.suv'),
                                'truck' => __('listings.vehicle_types.truck'),
                            ]),
                        TextInput::make('pickup_zip'),
                        TextInput::make('delivery_zip'),
                        TextInput::make('weight')->numeric()->minValue(0),
                    ]),

                Fieldset::make(ListingType::Company->label())
                    ->relationship('companyDetail')
                    ->visible(self::isType(ListingType::Company))
                    ->schema([
                        TextInput::make('company_name')->required(),
                        TextInput::make('services'),
                    ]),

                Fieldset::make(ListingType::Dispatcher->label())
                    ->relationship('dispatcherDetail')
                    ->visible(self::isType(ListingType::Dispatcher))
                    ->schema([
                        TextInput::make('experience_years')->numeric()->minValue(0)->maxValue(60),
                        Select::make('employment_type')
                            ->options([
                                'full_time' => __('listings.employment_types.full_time'),
                                'part_time' => __('listings.employment_types.part_time'),
                            ]),
                        CheckboxList::make('languages')
                            ->options([
                                'english_a1' => __('listings.languages.english_a1'),
                                'english_b2' => __('listings.languages.english_b2'),
                                'english_c1' => __('listings.languages.english_c1'),
                                'russian' => __('listings.languages.russian'),
                                'georgian' => __('listings.languages.georgian'),
                            ]),
                    ]),

                Fieldset::make(ListingType::DriverOwner->label())
                    ->relationship('driverOwnerDetail')
                    ->visible(self::isType(ListingType::DriverOwner))
                    ->schema([
                        TextInput::make('experience_years')->numeric()->minValue(0)->maxValue(60),
                        Select::make('cdl_class')
                            ->options([
                                'a' => __('listings.cdl_classes.a'),
                                'b' => __('listings.cdl_classes.b'),
                            ]),
                        Toggle::make('owns_truck'),
                    ]),

                Fieldset::make(ListingType::Service->label())
                    ->relationship('serviceDetail')
                    ->visible(self::isType(ListingType::Service))
                    ->schema([
                        Select::make('service_category_id')
                            ->label(__('listings.fields.service_category'))
                            ->options(fn () => ServiceCategory::query()->ordered()->pluck('name_en', 'id'))
                            ->required()
                            ->searchable(),
                    ]),
            ]);
    }

    /** Visibility closure comparing the live "type" state against the given case. */
    private static function isType(ListingType $type): Closure
    {
        return function (Get $get) use ($type): bool {
            $state = $get('type');

            if ($state instanceof ListingType) {
                return $state === $type;
            }

            return $state === $type->value;
        };
    }
}
