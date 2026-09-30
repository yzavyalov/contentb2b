<?php

namespace App\Enums;

enum AccountApplicationStatus: string
{
    case NEW = 'new';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }
}
