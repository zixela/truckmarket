<?php

declare(strict_types=1);

namespace App\Enums;

enum ListingStatus: string
{
    case PendingModeration = 'pending_moderation';
    case Active = 'active';
    case Inactive = 'inactive';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('listings.statuses.'.$this->value);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
