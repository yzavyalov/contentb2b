<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantDeliveryRuleLocale extends Model
{
    protected $fillable = [
        'merchant_delivery_rule_id',
        'locale',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(
            MerchantDeliveryRule::class,
            'merchant_delivery_rule_id'
        );
    }
}
