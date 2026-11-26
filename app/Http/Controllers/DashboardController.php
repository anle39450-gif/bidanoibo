<?php

namespace App\Http\Controllers;

use App\Models\BilliardMatch;
use App\Models\BilliardSideBet;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\OpenAiPlayerAnalysisService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected BilliardStatsService $statsService,
        protected BilliardMatchService $matchService,
        protected OpenAiPlayerAnalysisService $aiAnalysisService
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
        $myAiAnalysis = $currentUser ? $this->aiAnalysisService->getAnalysisForUser($currentUser) : null;
        $myUnpaidDebtsCount = 0;
        $myPaidDebtsCount = 0;
        $myUnpaidReceivablesCount = 0;
        $myUnpaidMatches = collect();
        $myUnpaidReceivables = collect();
        $myUnpaidSideBets = collect();
        $myUnpaidSideBetReceivables = collect();
        $myPendingSideBetInvites = collect();

        if ($currentUser) {
            $myUnpaidDebtsCount = $currentUser->unpaid_debts_count;
            $myPaidDebtsCount = $currentUser->paid_debts_count;
            $myUnpaidReceivablesCount = $currentUser->unpaid_receivables_count;

            $myUnpaidMatches = BilliardMatch::confirmed()
                ->where(function ($query) use ($currentUser) {
                    $query->where(function ($q) use ($currentUser) {
                        $q->where('loser_id', $currentUser->id)
                            ->whereNotIn('prize_status', ['confirmed']);
                    })->orWhere(function ($q) use ($currentUser) {
                        $q->where('loser_partner_id', $currentUser->id)
                            ->whereNotIn('prize_partner_status', ['confirmed']);
                    });
                })
                ->with(['winner', 'winnerPartner', 'loser', 'loserPartner', 'prizePaidBy', 'prizeConfirmedBy', 'prizePartnerPaidBy', 'prizePartnerConfirmedBy'])
                ->orderBy('played_at', 'desc')
                ->take(6)
                ->get();

            $myUnpaidReceivables = BilliardMatch::confirmed()
                ->where(function ($query) use ($currentUser) {
                    $query->where(function ($q) use ($currentUser) {
                        $q->where('winner_id', $currentUser->id)
                            ->whereNotIn('prize_status', ['confirmed']);
                    })->orWhere(function ($q) use ($currentUser) {
                        $q->where('winner_partner_id', $currentUser->id)
                            ->whereNotIn('prize_partner_status', ['confirmed']);
                    });
                })
                ->with(['winner', 'winnerPartner', 'loser', 'loserPartner', 'prizePaidBy', 'prizeConfirmedBy', 'prizePartnerPaidBy', 'prizePartnerConfirmedBy'])
                ->orderBy('played_at', 'desc')
                ->take(6)
                ->get();

            // Side bets debts (unpaid side bets where current user lost)
            $myUnpaidSideBets = BilliardSideBet::where('loser_id', $currentUser->id)
                ->where('status', 'resolved')
                ->whereNotIn('prize_status', ['confirmed'])
                ->with(['challenge.challenger', 'challenge.opponent', 'winner', 'loser', 'creator', 'target'])
                ->orderByDesc('updated_at')
                ->take(6)
                ->get();

            // Side bets receivables (side bets won by current user waiting for payment/confirmation)
            $myUnpaidSideBetReceivables = BilliardSideBet::where('winner_id', $currentUser->id)
                ->where('status', 'resolved')
                ->whereNotIn('prize_status', ['confirmed'])
                ->with(['challenge.challenger', 'challenge.opponent', 'winner', 'loser', 'creator', 'target'])
                ->orderByDesc('updated_at')
                ->take(6)
                ->get();

            // Pending side bet invitations directed to current user
            $myPendingSideBetInvites = BilliardSideBet::where('target_id', $currentUser->id)
                ->where('status', 'pending')
                ->with(['challenge.challenger', 'challenge.opponent', 'creator', 'target'])
                ->orderByDesc('created_at')
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
            'myAiAnalysis',
            'myUnpaidDebtsCount',
            'myPaidDebtsCount',
            'myUnpaidReceivablesCount',
            'myUnpaidMatches',
            'myUnpaidReceivables',
            'myUnpaidSideBets',
            'myUnpaidSideBetReceivables',
            'myPendingSideBetInvites',
            'myPendingMatches',
            'recentMatches',
            'miniLeaderboard',
            'last7Days',
            'max7DaysCount',
            'statusDistribution'
        ));
    }

    /**
     * Refresh AI analysis for current player via AJAX.
     */
    public function refreshAiAnalysis(OpenAiPlayerAnalysisService $aiService): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập!'], 401);
        }

        $analysis = $aiService->getAnalysisForUser($user, forceRefresh: true);

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'message' => 'Đã làm mới nhận định AI thành công!',
        ]);
    }
}
