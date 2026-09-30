<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MerchantDeliveryRule extends Model
{
    protected $fillable = [
        'merchant_id',
        'name',
        'is_active',
        'min_finish_hours',
        'max_finish_hours',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_finish_hours' => 'integer',
            'max_finish_hours' => 'integer',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'merchant_delivery_rule_categories'
        )->withTimestamps();
    }

    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class,
            'merchant_delivery_rule_countries'
        )->withTimestamps();
    }

    public function countryGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            CountryGroup::class,
            'merchant_delivery_rule_country_groups'
        )->withTimestamps();
    }

    public function locales(): HasMany
    {
        return $this->hasMany(
            MerchantDeliveryRuleLocale::class,
            'merchant_delivery_rule_id'
        );
    }
}
