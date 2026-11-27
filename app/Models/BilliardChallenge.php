<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeAwaitingResponse(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function recordResponse(bool $accepted): bool
    {
        $changes = $accepted
            ? ['status' => 'accepted', 'accepted_at' => now()]
            : ['status' => 'declined', 'declined_at' => now()];

        if (! self::query()->whereKey($this->getKey())->awaitingResponse()->update($changes)) {
            return false;
        }

        $this->forceFill($changes)->syncOriginal();

        return true;
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

    /**
     * Get head-to-head, team stats, and AI/statistical predicted win probability for both sides
     *
     * @return array<string, mixed>
     */
    public function getMatchupAnalysis(): array
    {
        $isDoubles = $this->isDoubles();

        // 1. Team 1 Stats (Challenger side)
        $p1 = $this->challenger;
        $p1Partner = $isDoubles ? $this->challengerPartner : null;

        $p1Wins = $p1?->wins_count ?? 0;
        $p1Losses = $p1?->losses_count ?? 0;
        $p1Total = $p1Wins + $p1Losses;
        $p1Rate = $p1?->win_rate ?? 0.0;
        $p1Streak = $this->calculateUserStreak($p1);

        if ($isDoubles && $p1Partner) {
            $p1pWins = $p1Partner->wins_count;
            $p1pLosses = $p1Partner->losses_count;
            $team1Wins = $p1Wins + $p1pWins;
            $team1Losses = $p1Losses + $p1pLosses;
            $team1Total = $team1Wins + $team1Losses;
            $team1Rate = $team1Total > 0 ? round(($team1Wins / $team1Total) * 100, 1) : 0.0;
            $team1Streak = $p1Streak; // primary streak
        } else {
            $team1Wins = $p1Wins;
            $team1Losses = $p1Losses;
            $team1Total = $p1Total;
            $team1Rate = $p1Rate;
            $team1Streak = $p1Streak;
        }

        // 2. Team 2 Stats (Opponent side)
        $p2 = $this->opponent;
        $p2Partner = $isDoubles ? $this->opponentPartner : null;

        $p2Wins = $p2?->wins_count ?? 0;
        $p2Losses = $p2?->losses_count ?? 0;
        $p2Total = $p2Wins + $p2Losses;
        $p2Rate = $p2?->win_rate ?? 0.0;
        $p2Streak = $this->calculateUserStreak($p2);

        if ($isDoubles && $p2Partner) {
            $p2pWins = $p2Partner->wins_count;
            $p2pLosses = $p2Partner->losses_count;
            $team2Wins = $p2Wins + $p2pWins;
            $team2Losses = $p2Losses + $p2pLosses;
            $team2Total = $team2Wins + $team2Losses;
            $team2Rate = $team2Total > 0 ? round(($team2Wins / $team2Total) * 100, 1) : 0.0;
            $team2Streak = $p2Streak;
        } else {
            $team2Wins = $p2Wins;
            $team2Losses = $p2Losses;
            $team2Total = $p2Total;
            $team2Rate = $p2Rate;
            $team2Streak = $p2Streak;
        }

        [$h2hChallengerWins, $h2hOpponentWins] = $this->getHeadToHeadWins();
        $h2hTotal = $h2hChallengerWins + $h2hOpponentWins;

        // 4. Statistical Prediction Formula (Bayesian smoothing + Streak + H2H)
        $score1 = (($team1Wins + 3) / (max($team1Total, 0) + 6)) * 100;
        $score2 = (($team2Wins + 3) / (max($team2Total, 0) + 6)) * 100;

        // Streak weight (+/- 2.5% per consecutive result, max 10%)
        if ($team1Streak['type'] === 'win') {
            $score1 += min($team1Streak['count'], 4) * 2.5;
        } elseif ($team1Streak['type'] === 'loss') {
            $score1 -= min($team1Streak['count'], 4) * 2.5;
        }

        if ($team2Streak['type'] === 'win') {
            $score2 += min($team2Streak['count'], 4) * 2.5;
        } elseif ($team2Streak['type'] === 'loss') {
            $score2 -= min($team2Streak['count'], 4) * 2.5;
        }

        // H2H weight (+/- 3% per H2H diff, max 12%)
        if ($h2hTotal > 0) {
            $h2hDiff = $h2hChallengerWins - $h2hOpponentWins;
            $h2hBonus = max(min($h2hDiff * 3, 12), -12);
            $score1 += $h2hBonus;
            $score2 -= $h2hBonus;
        }

        $score1 = max($score1, 15);
        $score2 = max($score2, 15);
        $totalScore = $score1 + $score2;

        $challengerPct = (int) round(($score1 / $totalScore) * 100);
        $challengerPct = max(min($challengerPct, 82), 18);
        $opponentPct = 100 - $challengerPct;

        if ($challengerPct >= 58) {
            $evalText = "⭐ Đội {$this->challenger_team_names} được đánh giá cao hơn ({$challengerPct}%)";
        } elseif ($opponentPct >= 58) {
            $evalText = "⭐ Đội {$this->opponent_team_names} nắm lợi thế nhỉnh hơn ({$opponentPct}%)";
        } else {
            $evalText = '⚖️ Kèo đấu rất cân tài cân sức (50-50)';
        }

        return [
            'challenger' => [
                'name' => $this->challenger_team_names,
                'win_rate' => $team1Rate,
                'wins' => $team1Wins,
                'losses' => $team1Losses,
                'total_matches' => $team1Total,
                'streak' => $team1Streak,
            ],
            'opponent' => [
                'name' => $this->opponent_team_names,
                'win_rate' => $team2Rate,
                'wins' => $team2Wins,
                'losses' => $team2Losses,
                'total_matches' => $team2Total,
                'streak' => $team2Streak,
            ],
            'h2h' => [
                'total' => $h2hTotal,
                'challenger_wins' => $h2hChallengerWins,
                'opponent_wins' => $h2hOpponentWins,
            ],
            'prediction' => [
                'challenger_pct' => $challengerPct,
                'opponent_pct' => $opponentPct,
                'eval_text' => $evalText,
            ],
        ];
    }

    /** @return array{int, int} */
    protected function getHeadToHeadWins(): array
    {
        $matches = BilliardMatch::confirmed();
        $challengerTeam = [$this->challenger_id];
        $opponentTeam = [$this->opponent_id];

        if ($this->isDoubles()) {
            if (! $this->challenger_partner_id || ! $this->opponent_partner_id) {
                return [0, 0];
            }

            $challengerTeam[] = $this->challenger_partner_id;
            $opponentTeam[] = $this->opponent_partner_id;

            $matches->where('match_type', 'doubles')
                ->whereColumn('winner_id', '!=', 'winner_partner_id')
                ->whereColumn('loser_id', '!=', 'loser_partner_id')
                ->where(fn (Builder $query): Builder => $query
                    ->where(fn (Builder $result): Builder => $result
                        ->whereIn('winner_id', $challengerTeam)->whereIn('winner_partner_id', $challengerTeam)
                        ->whereIn('loser_id', $opponentTeam)->whereIn('loser_partner_id', $opponentTeam))
                    ->orWhere(fn (Builder $result): Builder => $result
                        ->whereIn('winner_id', $opponentTeam)->whereIn('winner_partner_id', $opponentTeam)
                        ->whereIn('loser_id', $challengerTeam)->whereIn('loser_partner_id', $challengerTeam)));
        } else {
            $players = [$this->challenger_id, $this->opponent_id];
            $matches->where('match_type', 'singles')
                ->whereIn('winner_id', $players)
                ->whereIn('loser_id', $players)
                ->whereColumn('winner_id', '!=', 'loser_id');
        }

        $winsByPlayer = $matches->toBase()
            ->selectRaw('winner_id, count(*) as wins')
            ->groupBy('winner_id')
            ->pluck('wins', 'winner_id');

        return [
            (int) $winsByPlayer->only($challengerTeam)->sum(),
            (int) $winsByPlayer->only($opponentTeam)->sum(),
        ];
    }

    /**
     * @return array{type: ?string, count: int}
     */
    protected function calculateUserStreak(?User $user): array
    {
        if (! $user) {
            return ['type' => null, 'count' => 0];
        }

        $recentMatches = BilliardMatch::confirmed()
            ->where(function ($q) use ($user) {
                $q->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id)
                    ->orWhere('player1_partner_id', $user->id)
                    ->orWhere('player2_partner_id', $user->id);
            })
            ->orderBy('played_at', 'desc')
            ->take(10)
            ->get();

        if ($recentMatches->isEmpty()) {
            return ['type' => null, 'count' => 0];
        }

        $firstMatch = $recentMatches->first();
        $isFirstWin = ($firstMatch->winner_id === $user->id || $firstMatch->winner_partner_id === $user->id);
        $type = $isFirstWin ? 'win' : 'loss';
        $count = 0;

        foreach ($recentMatches as $m) {
            $isWin = ($m->winner_id === $user->id || $m->winner_partner_id === $user->id);
            if (($type === 'win' && $isWin) || ($type === 'loss' && ! $isWin)) {
                $count++;
            } else {
                break;
            }
        }

        return ['type' => $type, 'count' => $count];
    }
}
