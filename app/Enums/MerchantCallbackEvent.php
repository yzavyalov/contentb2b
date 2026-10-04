<?php

namespace App\Enums;

enum MerchantCallbackEvent: string
{
    case MARKET_PUBLISHED = 'market.published';
    case MARKET_RESOLVING = 'market.resolving';
    case MARKET_RESOLVED = 'market.resolved';
    case MARKET_CANCELLED = 'market.cancelled';

    public function label(): string
    {
        return match ($this) {
            self::MARKET_PUBLISHED => 'Market Published',
            self::MARKET_RESOLVING => 'Market Resolving',
            self::MARKET_RESOLVED => 'Market Resolved',
            self::MARKET_CANCELLED => 'Market Cancelled',
        };
    }
}
