<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BetAiSourceCheck extends Model
{
    protected $fillable = [
        'bet_id',
        'bet_ai_resolution_id',
        'suggested_answer_id',
        'source_type',
        'url',
        'title',
        'confidence',
        'interpretation',
        'evidence',
        'is_success',
        'error',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'is_success' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(
            BetAiResolution::class,
            'bet_ai_resolution_id'
        );
    }

    public function suggestedAnswer(): BelongsTo
    {
        return $this->belongsTo(
            BetAnswer::class,
            'suggested_answer_id'
        );
    }
}
