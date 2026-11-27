<?php

namespace App\Http\Controllers;

use App\Models\BilliardMatch;
use App\Models\BilliardSideBet;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\OpenAiPlayerAnalysisService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const PER_PAGE = 5;

    public function __construct(
        protected BilliardStatsService $statsService,
        protected BilliardMatchService $matchService,
    ) {}

    public function index(Request $request): View
    {
        $this->matchService->autoConfirmExpiredMatches();

        /** @var User $currentUser */
        $currentUser = $request->user();

        $myStats = $this->statsService->getPlayerStats($currentUser);
        $myPendingMatches = $this->paginatePendingMatches($currentUser);
        $matchesToPay = $this->paginateList($this->prizePayments($currentUser, ['unpaid', 'rejected']), 'to_pay_page', 'keo-can-tra');
        $matchesAwaitingReceipt = $this->paginateList($this->prizePayments($currentUser, ['paid']), 'awaiting_page', 'keo-da-tra');

        $myUnpaidReceivables = $this->paginateList(
            BilliardMatch::confirmed()
                ->where(function (Builder $query) use ($currentUser): void {
                    $query->where(function (Builder $q) use ($currentUser): void {
                        $q->where('winner_id', $currentUser->id)->whereNotIn('prize_status', ['confirmed']);
                    })->orWhere(function (Builder $q) use ($currentUser): void {
                        $q->where('winner_partner_id', $currentUser->id)->whereNotIn('prize_partner_status', ['confirmed']);
                    });
                })
                ->with(['player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'winnerPartner', 'loser', 'loserPartner'])
                ->orderByDesc('played_at')
                ->orderByDesc('id'),
            'receive_page',
            'keo-cho-nhan',
        );

        $myUnpaidSideBets = $this->paginateList(
            BilliardSideBet::where('loser_id', $currentUser->id)
                ->where('status', 'resolved')
                ->whereNotIn('prize_status', ['confirmed'])
                ->with(['challenge.challenger', 'challenge.opponent', 'challenge.challengerPartner', 'challenge.opponentPartner', 'winner'])
                ->orderByDesc('updated_at')
                ->orderByDesc('id'),
            'side_pay_page',
            'keo-ngoai-can-tra',
        );

        $myUnpaidSideBetReceivables = $this->paginateList(
            BilliardSideBet::where('winner_id', $currentUser->id)
                ->where('status', 'resolved')
                ->whereNotIn('prize_status', ['confirmed'])
                ->with(['challenge.challenger', 'challenge.opponent', 'challenge.challengerPartner', 'challenge.opponentPartner', 'loser'])
                ->orderByDesc('updated_at')
                ->orderByDesc('id'),
            'side_receive_page',
            'keo-ngoai-cho-nhan',
        );

        $myPendingSideBetInvites = $this->paginateList(
            BilliardSideBet::where('target_id', $currentUser->id)
                ->where('status', 'pending')
                ->with(['challenge.challenger', 'challenge.opponent', 'challenge.challengerPartner', 'challenge.opponentPartner', 'creator'])
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
            'invites_page',
            'loi-moi-keo-ngoai',
        );

        $recentMatches = $this->paginateList(
            BilliardMatch::with(['player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'winnerPartner'])
                ->orderByDesc('played_at')
                ->orderByDesc('id'),
            'recent_page',
            'tran-gan-day',
            perPage: 8,
        );

        return view('dashboard.index', compact(
            'myStats',
            'matchesToPay',
            'matchesAwaitingReceipt',
            'myUnpaidReceivables',
            'myUnpaidSideBets',
            'myUnpaidSideBetReceivables',
            'myPendingSideBetInvites',
            'myPendingMatches',
            'recentMatches'
        ));
    }

    /**
     * @param  list<string>  $statuses
     * @return Builder<BilliardMatch>
     */
    private function prizePayments(User $user, array $statuses): Builder
    {
        return BilliardMatch::confirmed()
            ->where(function (Builder $query) use ($user, $statuses): void {
                $query->where(function (Builder $q) use ($user, $statuses): void {
                    $q->where('loser_id', $user->id)->whereIn('prize_status', $statuses);
                })->orWhere(function (Builder $q) use ($user, $statuses): void {
                    $q->where('loser_partner_id', $user->id)->whereIn('prize_partner_status', $statuses);
                });
            })
            ->with(['player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'winnerPartner', 'loser', 'loserPartner'])
            ->orderByDesc('played_at')
            ->orderByDesc('id');
    }

    private function paginatePendingMatches(User $user): LengthAwarePaginator
    {
        $pageName = 'pending_page';
        $pendingMatches = BilliardMatch::where('status', 'pending')
            ->where(function (Builder $query) use ($user): void {
                $query->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id)
                    ->orWhere('player1_partner_id', $user->id)
                    ->orWhere('player2_partner_id', $user->id);
            })
            ->orderBy('auto_confirm_at')
            ->orderBy('id')
            ->get()
            ->filter(fn (BilliardMatch $match): bool => $match->isWaitingForUser($user));

        $total = $pendingMatches->count();
        $page = $this->dashboardPage($pageName, $total, self::PER_PAGE);
        $items = $pendingMatches->forPage($page, self::PER_PAGE)->values()
            ->load(['player1', 'player2', 'player1Partner', 'player2Partner']);

        return (new LengthAwarePaginator($items, $total, self::PER_PAGE, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
            'fragment' => 'tran-can-xac-nhan',
        ]))->withQueryString();
    }

    private function paginateList(Builder $query, string $pageName, string $fragment, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $total = $query->toBase()->getCountForPagination();

        return $query->paginate($perPage, pageName: $pageName, page: $this->dashboardPage($pageName, $total, $perPage), total: $total)
            ->withQueryString()
            ->fragment($fragment);
    }

    private function dashboardPage(string $pageName, int $total, int $perPage): int
    {
        return min(LengthAwarePaginator::resolveCurrentPage($pageName), max(1, (int) ceil($total / $perPage)));
    }

    /**
     * Refresh AI analysis for current player via AJAX.
     */
    public function refreshAiAnalysis(Request $request, OpenAiPlayerAnalysisService $aiService): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập!'], 401);
        }

        $analysis = $aiService->getAnalysisForUser($user, forceRefresh: $request->boolean('refresh', true));

        return response()->json([
            'success' => true,
            'analysis' => $analysis,
            'message' => 'Đã làm mới nhận định AI thành công!',
        ]);
    }
}
