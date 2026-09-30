<?php

namespace App\Enums;

enum MerchantBetStatus: string
{
    case QUEUED = 'queued';
    case DELIVERED = 'delivered';
    case RESOLVED = 'resolved';
    case CALLBACK_PENDING = 'callback_pending';
    case CALLBACK_DELIVERED = 'callback_delivered';
    case CALLBACK_FAILED = 'callback_failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::DELIVERED => 'Delivered',
            self::RESOLVED => 'Resolved',
            self::CALLBACK_PENDING => 'Callback Pending',
            self::CALLBACK_DELIVERED => 'Callback Delivered',
            self::CALLBACK_FAILED => 'Callback Failed',
        };
    }
}
