<?php

namespace App\Models;

use App\Enums\BillingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingPlan extends Model
{
    protected $fillable = [
        'name',
        'billing_type',
        'monthly_fee',
        'price_per_market',
        'included_markets',
        'is_unlimited',
        'overage_price',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'billing_type' => BillingType::class,

            'monthly_fee' => 'decimal:2',
            'price_per_market' => 'decimal:2',
            'overage_price' => 'decimal:2',

            'included_markets' => 'integer',

            'is_unlimited' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantSubscription::class);
    }
}
