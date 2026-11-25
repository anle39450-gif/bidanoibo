<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilliardChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenger_id',
        'challenger_partner_id',
        'opponent_id',
        'opponent_partner_id',
        'challenge_type',
        'prize',
        'message',
        'status',
        'match_id',
        'accepted_at',
        'declined_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function challenger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function challengerPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'challenger_partner_id');
    }

    public function opponent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    public function opponentPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opponent_partner_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(BilliardMatch::class, 'match_id');
    }

    public function isDoubles(): bool
    {
        return $this->challenge_type === 'doubles' || ! empty($this->challenger_partner_id);
    }

    public function getChallengerTeamNamesAttribute(): string
    {
        if ($this->isDoubles() && $this->challengerPartner) {
            return "{$this->challenger->name} & {$this->challengerPartner->name}";
        }

        return $this->challenger ? $this->challenger->name : 'Thách đấu';
    }

    public function getOpponentTeamNamesAttribute(): string
    {
        if ($this->isDoubles() && $this->opponentPartner) {
            return "{$this->opponent->name} & {$this->opponentPartner->name}";
        }

        return $this->opponent ? $this->opponent->name : 'Tiếp chiêu';
    }

    public function isParticipant(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $ids = array_filter([
            $this->challenger_id,
            $this->challenger_partner_id,
            $this->opponent_id,
            $this->opponent_partner_id,
        ]);

        return in_array($user->id, $ids);
    }

    public function isOpponentTeam(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $ids = array_filter([
            $this->opponent_id,
            $this->opponent_partner_id,
        ]);

        return in_array($user->id, $ids);
    }

    public function isChallengerTeam(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $ids = array_filter([
            $this->challenger_id,
            $this->challenger_partner_id,
        ]);

        return in_array($user->id, $ids);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at !== null && $this->expires_at->isPast();
    }
}
