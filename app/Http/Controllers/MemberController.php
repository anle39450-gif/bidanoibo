<?php

namespace App\Http\Controllers;

use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Services\BilliardStatsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(
        protected BilliardStatsService $statsService
    ) {}

    public function index(): View
    {
        $members = User::where('is_active', true)->orderBy('name')->get();

        $membersWithStats = $members->map(function (User $user) {
            $stats = $this->statsService->getPlayerStats($user);

            return [
                'user' => $user,
                'stats' => $stats,
            ];
        });

        return view('members.index', compact('membersWithStats'));
    }

    public function show(string $username): View
    {
        $member = User::where('username', $username)->firstOrFail();
        $stats = $this->statsService->getPlayerStats($member);

        $recentMatches = $member->allMatches()
            ->with(['creator', 'player1', 'player2', 'winner', 'loser'])
            ->orderBy('played_at', 'desc')
            ->take(10)
            ->get();

        $allOtherMembers = User::where('id', '!=', $member->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $botConfig = TelegramBotConfig::current();

        return view('members.show', compact('member', 'stats', 'recentMatches', 'allOtherMembers', 'botConfig'));
    }

    public function generateTelegramCode(User $user): RedirectResponse
    {
        $code = Str::random(10);
        $user->update(['telegram_link_code' => $code]);

        return back()->with('success', 'Mã liên kết Telegram mới đã được tạo thành công!');
    }
}
