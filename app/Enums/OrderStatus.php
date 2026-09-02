<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('orders.statuses.'.$this->value);
    }

    /** Statuses that still block a duplicate order on the same listing. */
    public static function active(): array
    {
        return [self::Pending, self::Confirmed];
    }

    public function isOpen(): bool
    {
        return in_array($this, self::active(), true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
