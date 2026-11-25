<?php

namespace App\Http\Controllers;

use App\Models\BilliardMatch;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected BilliardStatsService $statsService,
        protected BilliardMatchService $matchService
    ) {}

    public function index(): View
    {
        // Opportunistic auto-confirm check for any expired matches
        $this->matchService->autoConfirmExpiredMatches();

        $currentUser = Auth::user();

        // 1. Overall stats
        $totalMatches = BilliardMatch::whereIn('status', ['confirmed', 'resolved'])->count();
        $totalMembers = User::where('is_active', true)->count();
        $pendingCount = BilliardMatch::where('status', 'pending')->count();
        $disputedCount = BilliardMatch::where('status', 'disputed')->count();

        // 2. Spotlight: Top Player & Loser of the Month
        $topPlayer = $this->statsService->getTopPlayer('month');
        $loserOfMonth = $this->statsService->getLoserOfTheMonth();

        // 3. Current User Personal Stats & Debts
        $myStats = $currentUser ? $this->statsService->getPlayerStats($currentUser) : null;
        $myUnpaidDebtsCount = 0;
        $myPaidDebtsCount = 0;
        $myUnpaidReceivablesCount = 0;
        $myUnpaidMatches = collect();
        $myUnpaidReceivables = collect();

        if ($currentUser) {
            $unpaidQuery = BilliardMatch::confirmed()
                ->whereNotIn('prize_status', ['confirmed'])
                ->where(function ($q) use ($currentUser) {
                    $q->where('loser_id', $currentUser->id)
                        ->orWhere('loser_partner_id', $currentUser->id);
                });

            $myUnpaidDebtsCount = BilliardMatch::confirmed()
                ->whereNotIn('prize_status', ['paid', 'confirmed'])
                ->where(function ($q) use ($currentUser) {
                    $q->where('loser_id', $currentUser->id)
                        ->orWhere('loser_partner_id', $currentUser->id);
                })
                ->count();

            $myUnpaidMatches = (clone $unpaidQuery)
                ->with(['winner', 'winnerPartner', 'loser', 'loserPartner', 'prizePaidBy', 'prizeConfirmedBy'])
                ->orderBy('played_at', 'desc')
                ->take(6)
                ->get();

            $myPaidDebtsCount = BilliardMatch::confirmed()
                ->whereIn('prize_status', ['paid', 'confirmed'])
                ->where(function ($q) use ($currentUser) {
                    $q->where('loser_id', $currentUser->id)
                        ->orWhere('loser_partner_id', $currentUser->id);
                })
                ->count();

            $receivablesQuery = BilliardMatch::confirmed()
                ->whereNotIn('prize_status', ['confirmed'])
                ->where(function ($q) use ($currentUser) {
                    $q->where('winner_id', $currentUser->id)
                        ->orWhere('winner_partner_id', $currentUser->id);
                });

            $myUnpaidReceivablesCount = (clone $receivablesQuery)->count();
            $myUnpaidReceivables = (clone $receivablesQuery)
                ->with(['winner', 'winnerPartner', 'loser', 'loserPartner', 'prizePaidBy', 'prizeConfirmedBy'])
                ->orderBy('played_at', 'desc')
                ->take(6)
                ->get();
        }

        // 4. Pending matches specifically waiting for Current User
        $myPendingMatches = $currentUser ? BilliardMatch::where('status', 'pending')
            ->where(function ($q) use ($currentUser) {
                $q->where('player1_id', $currentUser->id)
                    ->orWhere('player2_id', $currentUser->id)
                    ->orWhere('player1_partner_id', $currentUser->id)
                    ->orWhere('player2_partner_id', $currentUser->id);
            })
            ->with(['creator', 'player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'loser'])
            ->get()
            ->filter(fn ($m) => $m->isWaitingForUser($currentUser)) : collect();

        // 5. Recent Matches (Confirmed or Resolved)
        $recentMatches = BilliardMatch::with(['creator', 'player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'loser', 'winnerPartner', 'loserPartner'])
            ->orderBy('played_at', 'desc')
            ->take(8)
            ->get();

        // 6. Mini Leaderboard (Top 5)
        $miniLeaderboard = $this->statsService->getLeaderboard('month')->take(5);

        // 7. Activity trend over the last 7 days for the visual chart
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = BilliardMatch::whereDate('played_at', $date->format('Y-m-d'))
                ->whereIn('status', ['confirmed', 'resolved'])
                ->count();
            $last7Days->push([
                'date' => $date->format('d/m'),
                'day_name' => $i === 0 ? 'Hôm nay' : ($date->isYesterday() ? 'Hôm qua' : 'T'.($date->dayOfWeek === 0 ? 'CN' : $date->dayOfWeek + 1)),
                'count' => $count,
                'is_today' => $i === 0,
            ]);
        }
        $max7DaysCount = max($last7Days->max('count'), 1);

        // 8. Match Status Distribution for overview chart
        $statusTotal = max($totalMatches + $pendingCount + $disputedCount, 1);
        $statusDistribution = [
            'confirmed' => $totalMatches,
            'confirmed_pct' => round(($totalMatches / $statusTotal) * 100),
            'pending' => $pendingCount,
            'pending_pct' => round(($pendingCount / $statusTotal) * 100),
            'disputed' => $disputedCount,
            'disputed_pct' => round(($disputedCount / $statusTotal) * 100),
            'total' => $statusTotal,
        ];

        return view('dashboard.index', compact(
            'totalMatches',
            'totalMembers',
            'pendingCount',
            'disputedCount',
            'topPlayer',
            'loserOfMonth',
            'myStats',
            'myUnpaidDebtsCount',
            'myPaidDebtsCount',
            'myUnpaidReceivablesCount',
            'myUnpaidMatches',
            'myUnpaidReceivables',
            'myPendingMatches',
            'recentMatches',
            'miniLeaderboard',
            'last7Days',
            'max7DaysCount',
            'statusDistribution'
        ));
    }
}
