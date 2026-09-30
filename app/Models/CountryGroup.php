<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CountryGroup extends Model
{
    protected $fillable = [
        'name',
        'slug',
    ];

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'country_country_group',
            'country_group_id',
            'country_id'
        );
    }

    public function bets(): BelongsToMany
    {
        return $this->belongsToMany(
            Bet::class,
            'bet_country_group',
            'country_group_id',
            'bet_id'
        );
    }
}
