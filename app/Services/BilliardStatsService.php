<?php

namespace App\Services;

use App\Models\BilliardMatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BilliardStatsService
{
    /**
     * Get detailed stats for a single user.
     *
     * @return array<string, mixed>
     */
    public function getPlayerStats(User $user, ?string $period = 'all'): array
    {
        $matchesQuery = BilliardMatch::confirmed()
            ->where(function ($query) use ($user) {
                $query->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id)
                    ->orWhere('player1_partner_id', $user->id)
                    ->orWhere('player2_partner_id', $user->id);
            });

        $matchesQuery = $this->applyPeriodFilter($matchesQuery, $period);
        $matches = $matchesQuery->orderBy('played_at', 'asc')->orderBy('id')->get();

        $totalMatches = $matches->count();
        $wins = $matches->filter(fn ($m) => $m->winner_id === $user->id || $m->winner_partner_id === $user->id)->count();
        $losses = $matches->filter(fn ($m) => $m->loser_id === $user->id || $m->loser_partner_id === $user->id)->count();
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;

        // Calculate streaks (based on chronological order)
        $currentStreakType = null;
        $currentStreakCount = 0;
        $longestWinStreak = 0;
        $longestLossStreak = 0;
        $tempWinStreak = 0;
        $tempLossStreak = 0;

        foreach ($matches as $match) {
            $isWin = ($match->winner_id === $user->id || $match->winner_partner_id === $user->id);

            if ($isWin) {
                $tempWinStreak++;
                $tempLossStreak = 0;
                $longestWinStreak = max($longestWinStreak, $tempWinStreak);
            } else {
                $tempLossStreak++;
                $tempWinStreak = 0;
                $longestLossStreak = max($longestLossStreak, $tempLossStreak);
            }
        }

        // Current streak from the end
        if ($matches->isNotEmpty()) {
            $reversed = $matches->reverse();
            $lastMatch = $reversed->first();
            $lastIsWin = ($lastMatch->winner_id === $user->id || $lastMatch->winner_partner_id === $user->id);
            $currentStreakType = $lastIsWin ? 'win' : 'loss';

            foreach ($reversed as $match) {
                $isWin = ($match->winner_id === $user->id || $match->winner_partner_id === $user->id);
                if (($currentStreakType === 'win' && $isWin) || ($currentStreakType === 'loss' && ! $isWin)) {
                    $currentStreakCount++;
                } else {
                    break;
                }
            }
        }

        $recentResults = $matches->take(-5)
            ->map(fn (BilliardMatch $match): string => in_array($user->id, [$match->winner_id, $match->winner_partner_id], true) ? 'win' : 'loss')
            ->values()->all();

        return [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $winRate,
            'current_streak_type' => $currentStreakType, // 'win' or 'loss' or null
            'current_streak_count' => $currentStreakCount,
            'longest_win_streak' => $longestWinStreak,
            'longest_loss_streak' => $longestLossStreak,
            'recent_results' => $recentResults,
            'badges' => $this->getFunBadges($user, $totalMatches, $wins, $losses, $winRate, $currentStreakType, $currentStreakCount, $longestWinStreak),
        ];
    }

    /**
     * Get head-to-head statistics between two players.
     *
     * @return array<string, mixed>
     */
    public function getHeadToHead(User $user1, User $user2): array
    {
        $matches = BilliardMatch::confirmed()
            ->where(function ($q) use ($user1, $user2) {
                $q->where(function ($inner) use ($user1, $user2) {
                    $inner->where(function ($t1) use ($user1) {
                        $t1->where('player1_id', $user1->id)->orWhere('player1_partner_id', $user1->id);
                    })->where(function ($t2) use ($user2) {
                        $t2->where('player2_id', $user2->id)->orWhere('player2_partner_id', $user2->id);
                    });
                })->orWhere(function ($inner) use ($user1, $user2) {
                    $inner->where(function ($t1) use ($user2) {
                        $t1->where('player1_id', $user2->id)->orWhere('player1_partner_id', $user2->id);
                    })->where(function ($t2) use ($user1) {
                        $t2->where('player2_id', $user1->id)->orWhere('player2_partner_id', $user1->id);
                    });
                });
            })
            ->with(['player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'loser', 'winnerPartner', 'loserPartner'])
            ->orderBy('played_at', 'desc')
            ->get();

        $total = $matches->count();
        $user1Wins = $matches->filter(fn ($m) => $m->winner_id === $user1->id || $m->winner_partner_id === $user1->id)->count();
        $user2Wins = $matches->filter(fn ($m) => $m->winner_id === $user2->id || $m->winner_partner_id === $user2->id)->count();

        $user1WinRate = $total > 0 ? round(($user1Wins / $total) * 100, 1) : 0;
        $user2WinRate = $total > 0 ? round(($user2Wins / $total) * 100, 1) : 0;

        return [
            'total_matches' => $total,
            'user1_wins' => $user1Wins,
            'user2_wins' => $user2Wins,
            'user1_win_rate' => $user1WinRate,
            'user2_win_rate' => $user2WinRate,
            'matches' => $matches,
        ];
    }

    /**
     * Get complete Leaderboard.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getLeaderboard(?string $period = 'all', bool $includeAdmins = true): Collection
    {
        $usersQuery = User::where('is_active', true);
        if (! $includeAdmins) {
            $usersQuery->where('role', '!=', 'admin');
        }

        $leaderboard = $usersQuery->get()->map(function (User $user) use ($period) {
            $stats = $this->getPlayerStats($user, $period);

            return [
                'user' => $user,
                'total_matches' => $stats['total_matches'],
                'wins' => $stats['wins'],
                'losses' => $stats['losses'],
                'win_rate' => $stats['win_rate'],
                'current_streak_type' => $stats['current_streak_type'],
                'current_streak_count' => $stats['current_streak_count'],
                'longest_win_streak' => $stats['longest_win_streak'],
                'longest_loss_streak' => $stats['longest_loss_streak'],
                'recent_results' => $stats['recent_results'],
                'badges' => $stats['badges'],
            ];
        });

        // Filter out members with 0 matches for ranking order, sort by wins desc, win_rate desc, total matches desc
        return $leaderboard->sort(function ($a, $b) {
            if ($a['wins'] !== $b['wins']) {
                return $b['wins'] <=> $a['wins'];
            }
            if ($a['win_rate'] !== $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate'];
            }

            return $b['total_matches'] <=> $a['total_matches'];
        })->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;

            return $item;
        });
    }

    /**
     * Get Top Player of current period.
     *
     * @return array<string, mixed>|null
     */
    public function getTopPlayer(?string $period = 'month'): ?array
    {
        $leaderboard = $this->getLeaderboard($period);

        // Player with at least 1 win and highest score
        return $leaderboard->first(fn ($item) => $item['wins'] > 0);
    }

    /**
     * Get Loser of the Month ("Chiến thần bao cà phê", "Cây ATM của quán").
     *
     * @return array<string, mixed>|null
     */
    public function getLoserOfTheMonth(): ?array
    {
        $leaderboard = $this->getLeaderboard('month');

        // Loser with the highest losses
        $loser = $leaderboard
            ->filter(fn ($item) => $item['losses'] > 0)
            ->sortByDesc('losses')
            ->first();

        if ($loser) {
            // Pick a fun tagline
            $titles = [
                'Chiến thần bao cà phê muối',
                'Cây ATM của toàn công ty',
                'Nhà tài trợ đồ uống uy tín',
                'Người mang niềm vui cho anh em',
                'Đại sứ thương hiệu Rockstar',
            ];
            $loser['humorous_title'] = $titles[array_rand($titles)];
        }

        return $loser;
    }

    /**
     * Generate fun badges for player.
     *
     * @return array<int, array<string, string>>
     */
    public function getFunBadges(
        User $user,
        int $total,
        int $wins,
        int $losses,
        float $winRate,
        ?string $streakType,
        int $streakCount,
        int $longestWin
    ): array {
        $badges = [];

        if ($total >= 10 && $winRate >= 70) {
            $badges[] = ['name' => 'Chiến thần Bida', 'color' => 'amber', 'icon' => 'crown'];
        }

        if ($longestWin >= 4) {
            $badges[] = ['name' => "Chuỗi {$longestWin} trận bất bại", 'color' => 'emerald', 'icon' => 'flame'];
        }

        if ($streakType === 'win' && $streakCount >= 3) {
            $badges[] = ['name' => "Đang đỏ lửa ({$streakCount}W)", 'color' => 'rose', 'icon' => 'sparkles'];
        }

        if ($losses >= 5 && $losses > $wins) {
            $badges[] = ['name' => 'Cây ATM Cà Phê', 'color' => 'blue', 'icon' => 'coffee'];
        }

        if ($streakType === 'loss' && $streakCount >= 3) {
            $badges[] = ['name' => 'Tay cơ từ thiện', 'color' => 'purple', 'icon' => 'gift'];
        }

        if ($total >= 20) {
            $badges[] = ['name' => 'Cơ thủ kỳ cựu', 'color' => 'indigo', 'icon' => 'award'];
        }

        return $badges;
    }

    private function applyPeriodFilter($query, ?string $period)
    {
        if ($period === 'month') {
            return $query->where('played_at', '>=', Carbon::now()->startOfMonth());
        }

        if ($period === 'year') {
            return $query->where('played_at', '>=', Carbon::now()->startOfYear());
        }

        return $query;
    }
}
