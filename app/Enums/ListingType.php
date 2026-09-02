<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\ListingDetails\CompanyDetail;
use App\Models\ListingDetails\DispatcherDetail;
use App\Models\ListingDetails\DriverOwnerDetail;
use App\Models\ListingDetails\LoadDetail;
use App\Models\ListingDetails\ServiceDetail;
use App\Models\ListingDetails\TrailerDetail;
use App\Models\ListingDetails\TruckDetail;

enum ListingType: string
{
    case Load = 'load';
    case Truck = 'truck';
    case Trailer = 'trailer';
    case Company = 'company';
    case Dispatcher = 'dispatcher';
    case DriverOwner = 'driver_owner';
    case Service = 'service';

    public function label(): string
    {
        return __('listings.types.'.$this->value);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Load => '🔴',
            self::Truck => '🚛',
            self::Trailer => '🚜',
            self::Company => '🏢',
            self::Dispatcher => '👷',
            self::DriverOwner => '🧑',
            self::Service => '🛠️',
        };
    }

    /** Detail model class holding the type-specific fields. */
    public function detailClass(): string
    {
        return match ($this) {
            self::Load => LoadDetail::class,
            self::Truck => TruckDetail::class,
            self::Trailer => TrailerDetail::class,
            self::Company => CompanyDetail::class,
            self::Dispatcher => DispatcherDetail::class,
            self::DriverOwner => DriverOwnerDetail::class,
            self::Service => ServiceDetail::class,
        };
    }

    /** Types that support the Sell / Rent deal switch. */
    public function hasDeal(): bool
    {
        return in_array($this, [self::Truck, self::Trailer], true);
    }

    /** URL segment used for SEO listing pages (/en/trucks, /en/loads, ...). */
    public function slug(): string
    {
        return match ($this) {
            self::Load => 'loads',
            self::Truck => 'trucks',
            self::Trailer => 'trailers',
            self::Company => 'companies',
            self::Dispatcher => 'dispatchers',
            self::DriverOwner => 'drivers',
            self::Service => 'services',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $type) {
            if ($type->slug() === $slug) {
                return $type;
            }
        }

        return null;
    }

    /** @return array<string> */
    public static function slugs(): array
    {
        return array_map(fn (self $type) => $type->slug(), self::cases());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
