<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Country extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function countryGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            CountryGroup::class,
            'country_country_group',
            'country_id',
            'country_group_id'
        );
    }

    public function bets(): BelongsToMany
    {
        return $this->belongsToMany(
            Bet::class,
            'bet_country',
            'country_id',
            'bet_id'
        );
    }
}
