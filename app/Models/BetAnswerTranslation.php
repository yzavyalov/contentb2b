<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetAnswerTranslation extends Model
{
    protected $fillable = [
        'bet_answer_id',
        'locale',
        'title',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(
            BetAnswer::class,
            'bet_answer_id'
        );
    }
}
