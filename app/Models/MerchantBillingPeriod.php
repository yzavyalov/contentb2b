<?php

namespace App\Models;

use App\Enums\MerchantBillingPeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\MerchantBalanceTransaction;

class MerchantBillingPeriod extends Model
{
    protected $fillable = [
        'merchant_id',
        'merchant_subscription_id',
        'period_start',
        'period_end',
        'included_markets',
        'markets_used',
        'monthly_fee',
        'overage_amount',
        'status',
        'monthly_fee_charged_at',
        'monthly_fee_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => MerchantBillingPeriodStatus::class,

            'period_start' => 'datetime',
            'period_end' => 'datetime',

            'included_markets' => 'integer',
            'markets_used' => 'integer',

            'monthly_fee' => 'decimal:2',
            'overage_amount' => 'decimal:2',

            'monthly_fee_charged_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(
            MerchantSubscription::class,
            'merchant_subscription_id'
        );
    }

    public function monthlyFeeTransaction()
    {
        return $this->belongsTo(
            MerchantBalanceTransaction::class,
            'monthly_fee_transaction_id'
        );
    }
}
