<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BilliardMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'match_type',
        'player1_id',
        'player1_partner_id',
        'player2_id',
        'player2_partner_id',
        'winner_id',
        'winner_partner_id',
        'loser_id',
        'loser_partner_id',
        'played_at',
        'prize',
        'notes',
        'evidence_path',
        'status',
        'notified_at',
        'auto_confirm_at',
        'confirmed_at',
        'confirmed_by',
        'disputed_at',
        'dispute_reason',
        'dispute_evidence_path',
        'admin_notes',
        'created_from',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'notified_at' => 'datetime',
            'auto_confirm_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'disputed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player1Partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_partner_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function player2Partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_partner_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function winnerPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_partner_id');
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_id');
    }

    public function loserPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_partner_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MatchHistory::class, 'billiard_match_id')->orderBy('created_at', 'desc');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'billiard_match_id');
    }

    // Scopes
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'resolved']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDisputed(Builder $query): Builder
    {
        return $query->where('status', 'disputed');
    }

    public function isDoubles(): bool
    {
        return $this->match_type === 'doubles';
    }

    public function isSingles(): bool
    {
        return $this->match_type !== 'doubles';
    }

    public function isParticipating(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        return in_array($id, array_filter([
            $this->player1_id,
            $this->player1_partner_id,
            $this->player2_id,
            $this->player2_partner_id,
        ]));
    }

    /**
     * @return array<int, User>
     */
    public function getOpponentsOf(User|int $user): array
    {
        $id = $user instanceof User ? $user->id : $user;

        if ($this->player1_id === $id || $this->player1_partner_id === $id) {
            return array_values(array_filter([$this->player2, $this->player2Partner]));
        }

        if ($this->player2_id === $id || $this->player2_partner_id === $id) {
            return array_values(array_filter([$this->player1, $this->player1Partner]));
        }

        return [];
    }

    public function getOpponentOf(User|int $user): ?User
    {
        $opponents = $this->getOpponentsOf($user);

        return $opponents[0] ?? null;
    }

    public function isWaitingForUser(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        if ($this->status !== 'pending') {
            return false;
        }

        $creatorInTeam1 = ($this->creator_id === $this->player1_id || $this->creator_id === $this->player1_partner_id);
        $creatorInTeam2 = ($this->creator_id === $this->player2_id || $this->creator_id === $this->player2_partner_id);

        if ($creatorInTeam1) {
            return $this->player2_id === $id || $this->player2_partner_id === $id;
        }

        if ($creatorInTeam2) {
            return $this->player1_id === $id || $this->player1_partner_id === $id;
        }

        // If created by third party (admin), waiting for any participant
        return $this->isParticipating($id);
    }

    public function getTeam1NamesAttribute(): string
    {
        if ($this->isDoubles() && $this->player1Partner) {
            return "{$this->player1->name} + {$this->player1Partner->name}";
        }

        return $this->player1?->name ?? 'Người chơi 1';
    }

    public function getTeam2NamesAttribute(): string
    {
        if ($this->isDoubles() && $this->player2Partner) {
            return "{$this->player2->name} + {$this->player2Partner->name}";
        }

        return $this->player2?->name ?? 'Người chơi 2';
    }

    public function getWinnerNamesAttribute(): string
    {
        if ($this->isDoubles() && $this->winnerPartner) {
            return "{$this->winner->name} + {$this->winnerPartner->name}";
        }

        return $this->winner?->name ?? 'Người thắng';
    }

    public function getLoserNamesAttribute(): string
    {
        if ($this->isDoubles() && $this->loserPartner) {
            return "{$this->loser->name} + {$this->loserPartner->name}";
        }

        return $this->loser?->name ?? 'Người thua';
    }

    public function isAutoConfirmExpired(): bool
    {
        return $this->status === 'pending'
            && $this->auto_confirm_at
            && Carbon::now()->greaterThanOrEqualTo($this->auto_confirm_at);
    }

    public function getRemainingSecondsForAutoConfirm(): int
    {
        if ($this->status !== 'pending' || ! $this->auto_confirm_at) {
            return 0;
        }

        $diff = Carbon::now()->diffInSeconds($this->auto_confirm_at, false);

        return max(0, (int) $diff);
    }
}
