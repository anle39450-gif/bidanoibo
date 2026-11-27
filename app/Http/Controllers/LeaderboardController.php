<?php

namespace App\Http\Controllers;

use App\Services\BilliardStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __construct(
        protected BilliardStatsService $statsService
    ) {}

    public function index(Request $request): View
    {
        $period = $request->get('period', 'all');
        if (! in_array($period, ['all', 'month', 'year'], true)) {
            $period = 'all';
        }

        $streak = $request->query('streak');
        if (! in_array($streak, ['win', 'loss'], true)) {
            $streak = null;
        }

        $leaderboard = $this->statsService->getLeaderboard($period, includeAdmins: false);
        $viewerId = $request->user()?->id;
        $viewerEntry = $leaderboard->first(fn (array $entry): bool => $entry['user']->id === $viewerId);
        $viewerRank = $viewerEntry['rank'] ?? null;
        $viewerIsVisible = $viewerEntry && ($streak === null || $viewerEntry['current_streak_type'] === $streak);

        if ($streak !== null) {
            $leaderboard = $leaderboard->filter(fn (array $entry): bool => $entry['current_streak_type'] === $streak);
        }

        return view('leaderboard.index', compact('leaderboard', 'period', 'streak', 'viewerId', 'viewerRank', 'viewerIsVisible'));
    }
}
