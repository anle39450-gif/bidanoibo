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
        if (! in_array($period, ['all', 'month', 'year'])) {
            $period = 'all';
        }

        $leaderboard = $this->statsService->getLeaderboard($period);

        return view('leaderboard.index', compact('leaderboard', 'period'));
    }
}
