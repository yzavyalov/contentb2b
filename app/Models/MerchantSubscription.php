<?php

namespace App\Models;

use App\Enums\MerchantSubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\MerchantSubscriptionChangeReason;

class MerchantSubscription extends Model
{
    protected $fillable = [
        'merchant_id',
        'billing_plan_id',
        'status',
        'starts_at',
        'ends_at',
        'custom_monthly_fee',
        'custom_price_per_market',
        'custom_included_markets',
        'custom_overage_price',
        'change_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => MerchantSubscriptionStatus::class,

            'starts_at' => 'datetime',
            'ends_at' => 'datetime',

            'custom_monthly_fee' => 'decimal:2',
            'custom_price_per_market' => 'decimal:2',
            'custom_overage_price' => 'decimal:2',

            'custom_included_markets' => 'integer',
            'change_reason' => MerchantSubscriptionChangeReason::class,
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function billingPlan(): BelongsTo
    {
        return $this->belongsTo(BillingPlan::class);
    }

    public function billingPeriods(): HasMany
    {
        return $this->hasMany(MerchantBillingPeriod::class);
    }
}
