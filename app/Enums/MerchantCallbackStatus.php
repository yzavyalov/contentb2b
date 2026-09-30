<?php

namespace App\Enums;

enum MerchantCallbackStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::DELIVERED => 'Delivered',
            self::FAILED => 'Failed',
        };
    }
}
