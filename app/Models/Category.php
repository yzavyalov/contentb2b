<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function bets(): BelongsToMany
    {
        return $this->belongsToMany(
            Bet::class,
            'bet_category',
            'category_id',
            'bet_id'
        );
    }
}
