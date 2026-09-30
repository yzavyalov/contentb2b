<?php

namespace App\Models;

use App\Enums\BillingType;
use App\Enums\MerchantBetDeliverySource;
use App\Enums\MerchantBetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantBet extends Model
{
    protected $fillable = [
        'merchant_id',
        'bet_id',
        'status',
        'delivery_source',
        'delivered_at',
        'resolved_at',
        'callback_status',
        'callback_delivered_at',
        'billing_mode',
        'unit_price',
        'charged_amount',
        'charged_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MerchantBetStatus::class,
            'delivery_source' => MerchantBetDeliverySource::class,
            'billing_mode' => BillingType::class,

            'unit_price' => 'decimal:2',
            'charged_amount' => 'decimal:2',

            'delivered_at' => 'datetime',
            'resolved_at' => 'datetime',
            'callback_delivered_at' => 'datetime',
            'charged_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(MerchantCallback::class);
    }
}
