<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    case CONTENT_MANAGER = 'content_manager';

    case CONTENT_SUPERVISOR = 'content_supervisor';

    case FINANCIAL_MANAGER = 'financial_manager';

    case MERCHANT = 'merchant';

    public function label(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::ADMIN => 'Administrator',
            self::CONTENT_MANAGER => 'Content Manager',
            self::CONTENT_SUPERVISOR => 'Content Supervisor',
            self::FINANCIAL_MANAGER => 'Financial Manager',
            self::MERCHANT => 'Merchant',
        };
    }
}
