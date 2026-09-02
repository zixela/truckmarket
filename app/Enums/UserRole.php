<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Company = 'company';
    case Dispatcher = 'dispatcher';
    case Driver = 'driver';
    case DriverOwner = 'driver_owner';
    case Admin = 'admin';

    /** Roles a visitor may pick during registration. */
    public static function registerable(): array
    {
        return [self::Company, self::Dispatcher, self::Driver, self::DriverOwner];
    }

    public function label(): string
    {
        return __('roles.'.$this->value);
    }
}
