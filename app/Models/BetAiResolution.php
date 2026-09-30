<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BetAiResolution extends Model
{
    protected $fillable = [
        'bet_id',
        'suggested_answer_id',
        'confidence',
        'summary',
        'status',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function bet(): BelongsTo
    {
        return $this->belongsTo(Bet::class);
    }

    public function suggestedAnswer(): BelongsTo
    {
        return $this->belongsTo(
            BetAnswer::class,
            'suggested_answer_id'
        );
    }

    public function sourceChecks(): HasMany
    {
        return $this->hasMany(
            BetAiSourceCheck::class
        );
    }
}
