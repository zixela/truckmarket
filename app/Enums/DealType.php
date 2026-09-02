<?php

declare(strict_types=1);

namespace App\Enums;

enum DealType: string
{
    case Sell = 'sell';
    case Rent = 'rent';

    public function label(): string
    {
        return __('listings.deals.'.$this->value);
    }
}
