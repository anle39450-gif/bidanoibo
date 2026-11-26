<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function sideBets(): HasMany
    {
        return $this->hasMany(BilliardSideBet::class, 'billiard_challenge_id');
    }

    public function acceptedSideBets(): HasMany
    {
        return $this->hasMany(BilliardSideBet::class, 'billiard_challenge_id')->where('status', 'accepted');
    }

    public function pendingSideBets(): HasMany
    {
        return $this->hasMany(BilliardSideBet::class, 'billiard_challenge_id')->where('status', 'pending');
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

    /**
     * Resolve all side bets for this challenge once the match is completed.
     *
     * @param  string  $winningTeamKey  'challenger' or 'opponent'
     */
    public function resolveSideBets(string $winningTeamKey, mixed $telegramBot = null): array
    {
        $resolvedBets = [];
        $acceptedBets = $this->sideBets()->where('status', 'accepted')->with(['creator', 'target'])->get();

        foreach ($acceptedBets as $bet) {
            $creatorWon = ($bet->bet_on_team === $winningTeamKey);
            $winner = $creatorWon ? $bet->creator : $bet->target;
            $loser = $creatorWon ? $bet->target : $bet->creator;

            $bet->update([
                'status' => 'resolved',
                'winner_id' => $winner->id,
                'loser_id' => $loser->id,
                'prize_status' => 'unpaid',
            ]);

            $resolvedBets[] = [
                'bet' => $bet,
                'winner' => $winner,
                'loser' => $loser,
            ];

            // In-app notifications
            UserNotification::create([
                'user_id' => $winner->id,
                'type' => 'side_bet_won',
                'title' => '🏆 BẠN ĐÃ THẮNG KÈO NGOÀI!',
                'message' => "Bạn đã thắng kèo ngoài món '{$bet->prize}' với {$loser->name} trong trận {$this->challenger_team_names} vs {$this->opponent_team_names}!",
                'link' => route('dashboard'),
            ]);

            UserNotification::create([
                'user_id' => $loser->id,
                'type' => 'side_bet_lost',
                'title' => '💀 BẠN ĐÃ THUA KÈO NGOÀI!',
                'message' => "Bạn đã thua kèo ngoài món '{$bet->prize}' trước {$winner->name} trong trận {$this->challenger_team_names} vs {$this->opponent_team_names}. Đừng quên trả kèo nhé!",
                'link' => route('dashboard'),
            ]);

            if ($telegramBot) {
                if ($winner->telegram_id) {
                    $telegramBot->sendMessage($winner->telegram_id, "🏆 <b>BẠN ĐÃ THẮNG KÈO NGOÀI!</b>\n\nBạn đã thắng kèo ngoài món <b>{$bet->prize}</b> với <b>{$loser->name}</b> trong trận <b>{$this->challenger_team_names}</b> vs <b>{$this->opponent_team_names}</b>!");
                }
                if ($loser->telegram_id) {
                    $telegramBot->sendMessage($loser->telegram_id, "💀 <b>BẠN ĐÃ THUA KÈO NGOÀI!</b>\n\nBạn đã thua kèo ngoài món <b>{$bet->prize}</b> trước <b>{$winner->name}</b> trong trận <b>{$this->challenger_team_names}</b> vs <b>{$this->opponent_team_names}</b>. Đừng quên thanh toán nhé!");
                }
            }
        }

        // Auto-cancel any lingering pending side bets when match finishes
        $this->sideBets()->where('status', 'pending')->update(['status' => 'cancelled']);

        return $resolvedBets;
    }
}
