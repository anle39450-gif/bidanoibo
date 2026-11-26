<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BilliardSideBet extends Model
{
    use HasFactory;

    protected $fillable = [
        'billiard_challenge_id',
        'creator_id',
        'target_id',
        'bet_on_team',
        'prize',
        'notes',
        'status',
        'winner_id',
        'loser_id',
        'prize_status',
        'prize_paid_at',
        'prize_confirmed_at',
        'prize_notes',
        'prize_feedback',
    ];

    protected function casts(): array
    {
        return [
            'prize_paid_at' => 'datetime',
            'prize_confirmed_at' => 'datetime',
        ];
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(BilliardChallenge::class, 'billiard_challenge_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_id');
    }

    /**
     * Get the name of the team the creator bet on.
     */
    public function getCreatorTeamNameAttribute(): string
    {
        if (! $this->challenge) {
            return $this->bet_on_team === 'challenger' ? 'Đội Thách đấu' : 'Đội Tiếp chiêu';
        }

        return $this->bet_on_team === 'challenger'
            ? $this->challenge->challenger_team_names
            : $this->challenge->opponent_team_names;
    }

    /**
     * Get the name of the team the target was assigned to.
     */
    public function getTargetTeamNameAttribute(): string
    {
        if (! $this->challenge) {
            return $this->bet_on_team === 'challenger' ? 'Đội Tiếp chiêu' : 'Đội Thách đấu';
        }

        return $this->bet_on_team === 'challenger'
            ? $this->challenge->opponent_team_names
            : $this->challenge->challenger_team_names;
    }

    public function isParticipant(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->id === $this->creator_id || $user->id === $this->target_id;
    }

    public function canUserAccept(?User $user): bool
    {
        return $user && $this->status === 'pending' && $user->id === $this->target_id;
    }

    public function canUserDecline(?User $user): bool
    {
        return $user && $this->status === 'pending' && $user->id === $this->target_id;
    }

    public function canUserCancel(?User $user): bool
    {
        return $user && $this->status === 'pending' && ($user->id === $this->creator_id || $user->isAdmin());
    }

    public function canUserPay(?User $user): bool
    {
        return $user && $this->status === 'resolved' && $user->id === $this->loser_id && in_array($this->prize_status, ['unpaid', 'rejected']);
    }

    public function canUserConfirm(?User $user): bool
    {
        return $user && $this->status === 'resolved' && $user->id === $this->winner_id && $this->prize_status === 'paid';
    }
}
