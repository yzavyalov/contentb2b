<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetTranslation extends Model
{
    protected $fillable = [
        'bet_id',
        'locale',
        'title',
        'description',
    ];

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }
}
