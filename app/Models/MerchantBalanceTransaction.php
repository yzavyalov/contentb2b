<?php

namespace App\Models;

use App\Enums\MerchantBalanceTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MerchantBalanceTransaction extends Model
{
    protected $fillable = [
        'merchant_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'currency',
        'reference_type',
        'reference_id',
        'description',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => MerchantBalanceTransactionType::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }
}
