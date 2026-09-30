<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BetAnswer extends Model
{
    protected $fillable = [
        'bet_id',
        'sort_order',
    ];

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BetAnswerTranslation::class);
    }
}
