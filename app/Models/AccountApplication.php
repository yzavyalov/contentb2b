<?php

namespace App\Models;

use App\Enums\AccountApplicationStatus;
use App\Enums\AccountApplicationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountApplication extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',

        'display_name',
        'preferred_locale',

        'company_name',

        'contact_email',
        'telegram',
        'phone',
        'website',

        'message',

        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountApplicationType::class,
            'status' => AccountApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_user_id'
        );
    }
}
