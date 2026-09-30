<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\HasMany;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isUser(): bool
    {
        return $this->role === UserRole::USER;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isContentManager(): bool
    {
        return $this->role === UserRole::CONTENT_MANAGER;
    }

    public function isContentSupervisor(): bool
    {
        return $this->role === UserRole::CONTENT_SUPERVISOR;
    }

    public function isFinancialManager(): bool
    {
        return $this->role === UserRole::FINANCIAL_MANAGER;
    }

    public function isMerchant(): bool
    {
        return $this->role === UserRole::MERCHANT;
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function createdBets(): HasMany
    {
        return $this->hasMany(
            Bet::class,
            'created_by_user_id'
        );
    }


    public function supervisedBets(): HasMany
    {
        return $this->hasMany(
            Bet::class,
            'supervisor_user_id'
        );
    }

    public function accountApplication()
    {
        return $this->hasOne(AccountApplication::class);
    }
}
