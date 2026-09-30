<?php

namespace App\Enums;

enum BillingType: string
{
    case PER_MARKET = 'per_market';
    case SUBSCRIPTION = 'subscription';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::PER_MARKET => 'Per Market',
            self::SUBSCRIPTION => 'Subscription',
            self::HYBRID => 'Hybrid',
        };
    }
}
