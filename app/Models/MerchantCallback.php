<?php

namespace App\Models;

use App\Enums\MerchantCallbackEvent;
use App\Enums\MerchantCallbackStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantCallback extends Model
{
    protected $fillable = [
        'merchant_id',
        'merchant_bet_id',
        'bet_id',
        'event',
        'status',
        'callback_url',
        'http_status',
        'attempts_count',
        'billable',
        'delivered_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'event' => MerchantCallbackEvent::class,
            'status' => MerchantCallbackStatus::class,

            'http_status' => 'integer',
            'attempts_count' => 'integer',

            'billable' => 'boolean',

            'delivered_at' => 'datetime',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function merchantBet(): BelongsTo
    {
        return $this->belongsTo(MerchantBet::class);
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
