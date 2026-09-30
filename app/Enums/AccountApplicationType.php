<?php

namespace App\Enums;

enum AccountApplicationType: string
{
    case CONTENT_MANAGER = 'content_manager';
    case MERCHANT = 'merchant';

    public function label(): string
    {
        return match ($this) {
            self::CONTENT_MANAGER => 'Content Creator',
            self::MERCHANT => 'Merchant',
        };
    }
}
