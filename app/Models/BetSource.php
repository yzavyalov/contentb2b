<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetSource extends Model
{
    protected $fillable = [
        'bet_id',
        'url',
        'sort_order',
    ];

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
