<?php

namespace App\Http\Controllers;

use App\Models\BilliardMatch;
use App\Models\User;
use App\Services\BilliardMatchService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MatchController extends Controller
{
    public function __construct(
        protected BilliardMatchService $matchService
    ) {}

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        if (! $currentUser) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập để ghi nhận trận đấu!'], 401);
            }

            return redirect()->route('login');
        }

        $matchType = $request->input('match_type', 'singles');

        if ($matchType === 'doubles') {
            $validated = $request->validate([
                'teammate_id' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id])],
                'opponent_id' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id, (int) $request->input('teammate_id')])],
                'opponent2_id' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id, (int) $request->input('teammate_id'), (int) $request->input('opponent_id')])],
                'winner_choice' => ['required', 'in:me,opponent'],
                'prize' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'played_at' => ['nullable', 'string'],
                'evidence' => ['nullable', 'image', 'max:10240'],
            ], [
                'teammate_id.required' => 'Vui lòng chọn đồng đội của bạn!',
                'teammate_id.not_in' => 'Đồng đội không thể là chính bạn!',
                'opponent_id.required' => 'Vui lòng chọn đối thủ 1!',
                'opponent_id.not_in' => 'Đối thủ 1 không được trùng với bạn hoặc đồng đội!',
                'opponent2_id.required' => 'Vui lòng chọn đối thủ 2!',
                'opponent2_id.not_in' => 'Đối thủ 2 không được trùng với các người chơi khác!',
                'prize.required' => 'Vui lòng nhập phần thưởng hoặc chọn kèo!',
            ]);

            $player1Id = $currentUser->id;
            $player1PartnerId = (int) $validated['teammate_id'];
            $player2Id = (int) $validated['opponent_id'];
            $player2PartnerId = (int) $validated['opponent2_id'];

            if ($validated['winner_choice'] === 'me') {
                $winnerId = $player1Id;
                $winnerPartnerId = $player1PartnerId;
                $loserId = $player2Id;
                $loserPartnerId = $player2PartnerId;
            } else {
                $winnerId = $player2Id;
                $winnerPartnerId = $player2PartnerId;
                $loserId = $player1Id;
                $loserPartnerId = $player1PartnerId;
            }
        } else {
            $validated = $request->validate([
                'opponent_id' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id])],
                'winner_choice' => ['required', 'in:me,opponent'],
                'prize' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'played_at' => ['nullable', 'string'],
                'evidence' => ['nullable', 'image', 'max:10240'],
            ], [
                'opponent_id.required' => 'Vui lòng chọn đối thủ!',
                'opponent_id.not_in' => 'Không thể tạo trận đấu với chính mình!',
                'prize.required' => 'Vui lòng nhập phần thưởng hoặc chọn kèo!',
            ]);

            $player1Id = $currentUser->id;
            $player1PartnerId = null;
            $player2Id = (int) $validated['opponent_id'];
            $player2PartnerId = null;

            $winnerId = ($validated['winner_choice'] === 'me') ? $player1Id : $player2Id;
            $winnerPartnerId = null;
            $loserId = ($winnerId === $player1Id) ? $player2Id : $player1Id;
            $loserPartnerId = null;
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('evidence', 'public');
        }

        try {
            $playedAt = ! empty($validated['played_at']) ? Carbon::parse($validated['played_at']) : Carbon::now();

            $match = $this->matchService->createMatch([
                'match_type' => $matchType,
                'player1_id' => $player1Id,
                'player1_partner_id' => $player1PartnerId,
                'player2_id' => $player2Id,
                'player2_partner_id' => $player2PartnerId,
                'winner_id' => $winnerId,
                'winner_partner_id' => $winnerPartnerId,
                'loser_id' => $loserId,
                'loser_partner_id' => $loserPartnerId,
                'played_at' => $playedAt,
                'prize' => $validated['prize'],
                'notes' => $validated['notes'] ?? null,
                'evidence_path' => $evidencePath,
                'created_from' => 'web',
            ], $currentUser);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Ghi nhận trận đấu #{$match->id} thành công!",
                    'redirect_url' => route('matches.show', $match->id),
                ]);
            }

            return redirect()->route('matches.show', $match->id)
                ->with('success', "Ghi nhận trận đấu #{$match->id} thành công!");
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function index(Request $request): View
    {
        $this->matchService->autoConfirmExpiredMatches();

        $users = User::where('is_active', true)->orderBy('name')->get();
        $viewMode = $request->get('view', 'list'); // 'list' or 'calendar'

        return view('matches.index', compact('users', 'viewMode'));
    }

    public function calendar(Request $request): View
    {
        $this->matchService->autoConfirmExpiredMatches();

        $today = Carbon::today();
        $year = (int) $request->input('year', $today->year);
        $month = (int) $request->input('month', $today->month);
        $selectedDate = $request->input('date', $today->format('Y-m-d'));

        $data = $this->getCalendarData($year, $month, $selectedDate);

        return view('matches.calendar', $data);
    }

    public function calendarData(Request $request): JsonResponse
    {
        $this->matchService->autoConfirmExpiredMatches();

        $today = Carbon::today();
        $year = (int) $request->input('year', $today->year);
        $month = (int) $request->input('month', $today->month);
        $selectedDate = $request->input('date');

        $data = $this->getCalendarData($year, $month, $selectedDate);

        return response()->json(array_merge(['success' => true], $data));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getCalendarData(int $year, int $month, ?string $selectedDate = null): array
    {
        $currentMonthDate = Carbon::createFromDate($year, $month, 1);
        $monthTitle = 'Tháng '.$currentMonthDate->format('m/Y');

        $startOfMonth = $currentMonthDate->copy()->startOfMonth();
        $endOfMonth = $currentMonthDate->copy()->endOfMonth();

        $startOfCalendar = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $matches = BilliardMatch::with(['creator', 'player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'loser', 'winnerPartner', 'loserPartner'])
            ->whereBetween('played_at', [$startOfCalendar->copy()->startOfDay(), $endOfCalendar->copy()->endOfDay()])
            ->orderBy('played_at', 'desc')
            ->get();

        $matchesByDate = [];
        foreach ($matches as $m) {
            $dateStr = $m->played_at->format('Y-m-d');
            $isConfirmed = in_array($m->status, ['confirmed', 'resolved']);

            $matchesByDate[$dateStr][] = [
                'id' => $m->id,
                'time' => $m->played_at->format('H:i'),
                'status' => $m->status,
                'status_label' => match ($m->status) {
                    'confirmed' => 'Đã xác nhận',
                    'resolved' => 'Đã xử lý',
                    'pending' => 'Chờ duyệt',
                    'disputed' => 'Khiếu nại',
                    default => $m->status,
                },
                'status_class' => match ($m->status) {
                    'confirmed' => 'bg-green-100 text-green-800',
                    'pending' => 'bg-amber-100 text-amber-800',
                    default => 'bg-red-100 text-red-800',
                },
                'team1_names' => $m->team1_names,
                'team2_names' => $m->team2_names,
                'winner_names' => $m->winner_names,
                'is_confirmed' => $isConfirmed,
                'is_team1_winner' => ($m->winner_id === $m->player1_id || $m->winner_partner_id === $m->player1_id),
                'is_team2_winner' => ($m->winner_id === $m->player2_id || $m->winner_partner_id === $m->player2_id),
                'prize' => $m->prize,
                'url' => route('matches.show', $m->id),
            ];
        }

        $todayStr = Carbon::today()->format('Y-m-d');
        if (! $selectedDate) {
            $selectedDate = ($month === Carbon::today()->month && $year === Carbon::today()->year)
                ? $todayStr
                : $startOfMonth->format('Y-m-d');
        }

        $days = [];
        $cursor = $startOfCalendar->copy();
        while ($cursor->lte($endOfCalendar)) {
            $dateStr = $cursor->format('Y-m-d');
            $dayMatches = $matchesByDate[$dateStr] ?? [];

            $days[] = [
                'dayNumber' => $cursor->day,
                'dateString' => $dateStr,
                'isCurrentMonth' => $cursor->month === $month,
                'isToday' => $dateStr === $todayStr,
                'isSelected' => $dateStr === $selectedDate,
                'matchCount' => count($dayMatches),
            ];

            $cursor->addDay();
        }

        $totalMonthMatches = $matches->filter(fn ($m) => $m->played_at->gte($startOfMonth) && $m->played_at->lte($endOfMonth))->count();

        return [
            'year' => $year,
            'month' => $month,
            'monthTitle' => $monthTitle,
            'totalMonthMatches' => $totalMonthMatches,
            'selectedDate' => $selectedDate,
            'days' => $days,
            'matchesByDate' => $matchesByDate,
        ];
    }

    public function show(BilliardMatch $match): View
    {
        $this->matchService->autoConfirmExpiredMatches();

        $match->load([
            'creator',
            'player1',
            'player1Partner',
            'player2',
            'player2Partner',
            'winner',
            'winnerPartner',
            'loser',
            'loserPartner',
            'prizePaidBy',
            'histories.user',
        ]);

        $currentUser = Auth::user();
        $isWaitingForUser = $currentUser ? $match->isWaitingForUser($currentUser) : false;
        $canDispute = $currentUser && $match->status === 'pending' && ($match->isParticipating($currentUser) || $currentUser->isAdmin());

        return view('matches.show', compact('match', 'isWaitingForUser', 'canDispute'));
    }

    public function payPrize(Request $request, BilliardMatch $match): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->matchService->settlePrize($match, $currentUser, $request->input('note'));

            return back()->with('success', 'Đã cập nhật trạng thái trả kèo thành công! Hệ thống đã gửi thông báo đến người nhận.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function confirmPrize(Request $request, BilliardMatch $match): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->matchService->confirmPrize($match, $currentUser, $request->input('note'));

            return back()->with('success', 'Bạn đã xác nhận ĐÃ OK nhận kèo thành công!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectPrize(Request $request, BilliardMatch $match): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->matchService->rejectPrize($match, $currentUser, $request->input('reason'));

            return back()->with('success', 'Đã gửi phản hồi không đồng ý với việc trả kèo!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function confirm(BilliardMatch $match): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        try {
            $this->matchService->confirmMatch($match, $currentUser, 'opponent');

            return back()->with('success', 'Bạn đã xác nhận kết quả trận đấu thành công!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function dispute(Request $request, BilliardMatch $match): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'evidence' => ['nullable', 'image', 'max:10240'], // max 10MB
        ], [
            'reason.required' => 'Bắt buộc phải cung cấp lý do khi từ chối kết quả!',
            'reason.min' => 'Lý do từ chối quá ngắn, vui lòng nêu rõ tình huống.',
        ]);

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('evidence', 'public');
        }

        try {
            $this->matchService->disputeMatch($match, $currentUser, $validated['reason'], $evidencePath);

            return back()->with('success', 'Khiếu nại đã được gửi! Trận đấu đã chuyển sang trạng thái tranh chấp chờ Quản trị viên xử lý.');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
