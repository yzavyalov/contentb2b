<?php

namespace App\Enums;

enum BetStatus: string
{
    case DRAFT = 'draft';

    case PENDING_REVIEW = 'pending_review';

    case APPROVED = 'approved';

    case REJECTED = 'rejected';

    case PUBLISHED = 'published';

    case RESOLVING = 'resolving';

    case RESOLVED = 'resolved';

    case CANCELLED = 'cancelled';


    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PENDING_REVIEW => 'Pending Review',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::PUBLISHED => 'Published',
            self::RESOLVING => 'Resolving',
            self::RESOLVED => 'Resolved',
            self::CANCELLED => 'Cancelled',
        };
    }
}
