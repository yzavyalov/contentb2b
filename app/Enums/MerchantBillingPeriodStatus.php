<?php

namespace App\Enums;

enum MerchantBillingPeriodStatus: string
{
    case OPEN = 'open';
    case INVOICED = 'invoiced';
    case PAID = 'paid';
    case CLOSED = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::INVOICED => 'Invoiced',
            self::PAID => 'Paid',
            self::CLOSED => 'Closed',
        };
    }
}
