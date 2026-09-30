<?php

namespace App\Models;

use App\Enums\BetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id',
        'supervisor_user_id',
        'status',
        'source_locale',
        'image_path',
        'finish_at',
        'published_at',
        'approved_at',
        'rejected_at',
        'resolved_at',
        'winning_answer_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => BetStatus::class,
            'finish_at' => 'datetime',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }


    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }


    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'supervisor_user_id'
        );
    }


    public function translations(): HasMany
    {
        return $this->hasMany(
            BetTranslation::class
        );
    }


    public function answers(): HasMany
    {
        return $this->hasMany(
            BetAnswer::class
        );
    }


    public function winningAnswer(): BelongsTo
    {
        return $this->belongsTo(
            BetAnswer::class,
            'winning_answer_id'
        );
    }


    public function sources(): HasMany
    {
        return $this->hasMany(
            BetSource::class
        );
    }


    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class
        );
    }


    public function countries(): BelongsToMany
    {
        return $this->belongsToMany(
            Country::class
        );
    }


    public function countryGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            CountryGroup::class
        );
    }

    public function aiResolution()
    {
        return $this->hasOne(
            BetAiResolution::class
        );
    }

    public function merchantBets(): HasMany
    {
        return $this->hasMany(MerchantBet::class);
    }

    public function merchantCallbacks(): HasMany
    {
        return $this->hasMany(MerchantCallback::class);
    }

}
