<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'avatar',
        'role',
        'is_active',
        'telegram_id',
        'telegram_username',
        'telegram_link_code',
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
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function getTelegramLinkCode(): string
    {
        if (empty($this->telegram_link_code)) {
            $this->telegram_link_code = Str::random(10);
            $this->save();
        }

        return $this->telegram_link_code;
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http')
                ? $this->avatar
                : asset('storage/'.$this->avatar);
        }

        $encodedName = urlencode($this->name ?: 'User');

        return "https://ui-avatars.com/api/?name={$encodedName}&background=0284c7&color=ffffff&bold=true";
    }

    public function matchesAsPlayer1(): HasMany
    {
        return $this->hasMany(BilliardMatch::class, 'player1_id');
    }

    public function matchesAsPlayer2(): HasMany
    {
        return $this->hasMany(BilliardMatch::class, 'player2_id');
    }

    public function matchesWon(): HasMany
    {
        return $this->hasMany(BilliardMatch::class, 'winner_id');
    }

    public function matchesLost(): HasMany
    {
        return $this->hasMany(BilliardMatch::class, 'loser_id');
    }

    public function matchesCreated(): HasMany
    {
        return $this->hasMany(BilliardMatch::class, 'creator_id');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }

    public function sentChallenges(): HasMany
    {
        return $this->hasMany(BilliardChallenge::class, 'challenger_id');
    }

    public function receivedChallenges(): HasMany
    {
        return $this->hasMany(BilliardChallenge::class, 'opponent_id');
    }

    /**
     * Query for all matches this user participated in.
     */
    public function allMatches(): Builder
    {
        return BilliardMatch::where(function (Builder $query) {
            $query->where('player1_id', $this->id)
                ->orWhere('player2_id', $this->id);
        });
    }

    /**
     * Query for all officially counted matches (confirmed or resolved).
     */
    public function confirmedMatches(): Builder
    {
        return $this->allMatches()->whereIn('status', ['confirmed', 'resolved']);
    }

    protected ?int $memoizedWinsCount = null;

    protected ?int $memoizedLossesCount = null;

    public function getWinsCountAttribute(): int
    {
        if ($this->memoizedWinsCount !== null) {
            return $this->memoizedWinsCount;
        }

        return $this->memoizedWinsCount = BilliardMatch::whereIn('status', ['confirmed', 'resolved'])
            ->where(function ($q) {
                $q->where('winner_id', $this->id)
                    ->orWhere('winner_partner_id', $this->id);
            })
            ->count();
    }

    public function getLossesCountAttribute(): int
    {
        if ($this->memoizedLossesCount !== null) {
            return $this->memoizedLossesCount;
        }

        return $this->memoizedLossesCount = BilliardMatch::whereIn('status', ['confirmed', 'resolved'])
            ->where(function ($q) {
                $q->where('loser_id', $this->id)
                    ->orWhere('loser_partner_id', $this->id);
            })
            ->count();
    }

    public function getWinRateAttribute(): float
    {
        $total = $this->wins_count + $this->losses_count;

        return $total > 0 ? round(($this->wins_count / $total) * 100, 1) : 0.0;
    }

    protected ?int $memoizedUnpaidDebtsCount = null;

    protected ?int $memoizedPaidDebtsCount = null;

    public function getUnpaidDebtsCountAttribute(): int
    {
        if ($this->memoizedUnpaidDebtsCount !== null) {
            return $this->memoizedUnpaidDebtsCount;
        }

        return $this->memoizedUnpaidDebtsCount = BilliardMatch::whereIn('status', ['confirmed', 'resolved'])
            ->where(function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('loser_id', $this->id)
                        ->whereNotIn('prize_status', ['paid', 'confirmed']);
                })->orWhere(function (Builder $q) {
                    $q->where('loser_partner_id', $this->id)
                        ->whereNotIn('prize_partner_status', ['paid', 'confirmed']);
                });
            })
            ->count();
    }

    public function getPaidDebtsCountAttribute(): int
    {
        if ($this->memoizedPaidDebtsCount !== null) {
            return $this->memoizedPaidDebtsCount;
        }

        return $this->memoizedPaidDebtsCount = BilliardMatch::whereIn('status', ['confirmed', 'resolved'])
            ->where(function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('loser_id', $this->id)
                        ->whereIn('prize_status', ['paid', 'confirmed']);
                })->orWhere(function (Builder $q) {
                    $q->where('loser_partner_id', $this->id)
                        ->whereIn('prize_partner_status', ['paid', 'confirmed']);
                });
            })
            ->count();
    }

    protected ?int $memoizedUnpaidReceivablesCount = null;

    public function getUnpaidReceivablesCountAttribute(): int
    {
        if ($this->memoizedUnpaidReceivablesCount !== null) {
            return $this->memoizedUnpaidReceivablesCount;
        }

        return $this->memoizedUnpaidReceivablesCount = BilliardMatch::whereIn('status', ['confirmed', 'resolved'])
            ->where(function (Builder $query) {
                $query->where(function (Builder $q) {
                    $q->where('winner_id', $this->id)
                        ->whereNotIn('prize_status', ['confirmed']);
                })->orWhere(function (Builder $q) {
                    $q->where('winner_partner_id', $this->id)
                        ->whereNotIn('prize_partner_status', ['confirmed']);
                });
            })
            ->count();
    }

    public function refresh()
    {
        $this->memoizedWinsCount = null;
        $this->memoizedLossesCount = null;
        $this->memoizedUnpaidDebtsCount = null;
        $this->memoizedPaidDebtsCount = null;
        $this->memoizedUnpaidReceivablesCount = null;

        return parent::refresh();
    }
}
