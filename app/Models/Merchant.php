<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    protected $fillable = [
        'user_id',
        'name',

        // Legacy field. Пока оставляем для обратной совместимости.
        'webhook_url',

        // Endpoint для отправки новых markets мерчанту.
        'market_url',

        // Endpoint для последующих callbacks:
        // market.resolved и других событий.
        'callback_url',

        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(MerchantToken::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantSubscription::class);
    }

    public function billingPeriods(): HasMany
    {
        return $this->hasMany(MerchantBillingPeriod::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(MerchantWallet::class);
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(MerchantBalanceTransaction::class);
    }

    public function merchantBets(): HasMany
    {
        return $this->hasMany(MerchantBet::class);
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(MerchantCallback::class);
    }

    public function deliveryRules(): HasMany
    {
        return $this->hasMany(MerchantDeliveryRule::class);
    }
}
