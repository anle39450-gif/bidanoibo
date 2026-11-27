<?php

namespace App\Http\Controllers;

use App\Models\BilliardChallenge;
use App\Models\BilliardMatch;
use App\Models\MatchHistory;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChallengeController extends Controller
{
    public function store(Request $request, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'challenge_type' => ['nullable', 'in:singles,doubles'],
            'opponent_id' => ['required', 'integer', 'exists:users,id'],
            'challenger_partner_id' => ['nullable', 'integer', 'exists:users,id'],
            'opponent_partner_id' => ['nullable', 'integer', 'exists:users,id'],
            'prize' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
        ], [
            'opponent_id.required' => 'Vui lòng chọn cơ thủ muốn thách đấu!',
            'prize.required' => 'Vui lòng nhập món kèo thách đấu!',
        ]);

        $challengeType = $validated['challenge_type'] ?? 'singles';

        if ($challengeType === 'doubles') {
            if (empty($validated['challenger_partner_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn đồng đội của bạn khi khiêu chiến đôi 2vs2!',
                ], 422);
            }

            if (empty($validated['opponent_partner_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vui lòng chọn đủ 2 đối thủ của đội bạn khi khiêu chiến đôi 2vs2!',
                ], 422);
            }

            $allPlayerIds = [
                $user->id,
                (int) $validated['challenger_partner_id'],
                (int) $validated['opponent_id'],
                (int) $validated['opponent_partner_id'],
            ];

            if (count(array_unique($allPlayerIds)) !== 4) {
                return response()->json([
                    'success' => false,
                    'message' => 'Các cơ thủ tham gia trận đôi 2vs2 phải là 4 người khác nhau hoàn toàn!',
                ], 422);
            }
        } else {
            if ((int) $validated['opponent_id'] === $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không thể tự khiêu chiến chính mình!',
                ], 422);
            }
        }

        $challenge = BilliardChallenge::create([
            'challenge_type' => $challengeType,
            'challenger_id' => $user->id,
            'challenger_partner_id' => $challengeType === 'doubles' ? $validated['challenger_partner_id'] : null,
            'opponent_id' => $validated['opponent_id'],
            'opponent_partner_id' => $challengeType === 'doubles' ? $validated['opponent_partner_id'] : null,
            'prize' => $validated['prize'],
            'message' => $validated['message'] ?: ($challengeType === 'doubles' ? 'Lập team 2vs2 ra bàn song đấu ngay!' : 'Ra bàn solo ngay nếu không muốn bị coi là sợ!'),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        $opponent = User::find($validated['opponent_id']);
        $opponentPartner = $challengeType === 'doubles' ? User::find($validated['opponent_partner_id']) : null;
        $challengerPartner = $challengeType === 'doubles' ? User::find($validated['challenger_partner_id']) : null;

        if ($challengeType === 'doubles') {
            $msgForOpponents = "Đội {$user->name} & {$challengerPartner?->name} mời đội bạn đánh đôi. Món kèo: {$challenge->prize}.";

            // Opponent 1
            if ($opponent) {
                UserNotification::create([
                    'user_id' => $opponent->id,
                    'type' => 'duel_challenge',
                    'title' => 'Lời khiêu chiến đánh đôi',
                    'message' => $msgForOpponents,
                    'link' => route('calendar'),
                ]);
            }

            // Opponent 2 (Partner)
            if ($opponentPartner) {
                UserNotification::create([
                    'user_id' => $opponentPartner->id,
                    'type' => 'duel_challenge',
                    'title' => 'Lời khiêu chiến đánh đôi',
                    'message' => $msgForOpponents,
                    'link' => route('calendar'),
                ]);
            }

            // Challenger Partner
            if ($challengerPartner) {
                UserNotification::create([
                    'user_id' => $challengerPartner->id,
                    'type' => 'duel_challenge',
                    'title' => '🤝 ĐỒNG ĐỘI ĐÃ KÉO BẠN VÀO TRẬN ĐÔI!',
                    'message' => "{$user->name} đã chọn bạn làm đồng đội và phát lệnh khiêu chiến 2vs2 tới đội {$opponent?->name} & {$opponentPartner?->name}!",
                    'link' => route('calendar'),
                ]);
            }
        } else {
            if ($opponent) {
                UserNotification::create([
                    'user_id' => $opponent->id,
                    'type' => 'duel_challenge',
                    'title' => 'Lời khiêu chiến đánh đơn',
                    'message' => "{$user->name} mời bạn đánh đơn. Món kèo: {$challenge->prize}.",
                    'link' => route('calendar'),
                ]);
            }
        }

        $telegramBot->sendDuelChallengeNotification($challenge);

        $targetNames = $challengeType === 'doubles'
            ? "{$opponent?->name} & {$opponentPartner?->name}"
            : "{$opponent?->name}";

        return response()->json([
            'success' => true,
            'message' => "Đã gửi lời khiêu chiến tới {$targetNames}.",
            'challenge' => $challenge,
        ]);
    }

    public function checkPending(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['has_challenge' => false]);
        }

        $challenge = BilliardChallenge::where(function ($q) use ($user) {
            $q->where('opponent_id', $user->id)
                ->orWhere('opponent_partner_id', $user->id);
        })
            ->awaitingResponse()
            ->with(['challenger', 'challengerPartner', 'opponent', 'opponentPartner'])
            ->latest()
            ->first();

        if (! $challenge) {
            return response()->json(['has_challenge' => false]);
        }

        $isDoubles = $challenge->isDoubles();

        return response()->json([
            'has_challenge' => true,
            'challenge' => [
                'id' => $challenge->id,
                'is_doubles' => $isDoubles,
                'challenge_type' => $challenge->challenge_type,
                'challenger_id' => $challenge->challenger_id,
                'challenger_name' => $challenge->challenger->name,
                'challenger_username' => $challenge->challenger->username,
                'challenger_avatar' => $challenge->challenger->avatar_url,
                'challenger_partner_name' => $challenge->challengerPartner?->name,
                'challenger_partner_username' => $challenge->challengerPartner?->username,
                'challenger_partner_avatar' => $challenge->challengerPartner?->avatar_url,
                'challenger_team_names' => $challenge->challenger_team_names,
                'opponent_team_names' => $challenge->opponent_team_names,
                'opponent_name' => $challenge->opponent?->name,
                'opponent_partner_name' => $challenge->opponentPartner?->name,
                'prize' => $challenge->prize,
                'message' => $challenge->message,
                'created_at' => $challenge->created_at->diffForHumans(),
                'reply_deadline' => $challenge->expires_at?->format('H:i d/m'),
                'accept_url' => route('challenges.accept', $challenge),
                'decline_url' => route('challenges.decline', $challenge),
            ],
        ]);
    }

    public function accept(BilliardChallenge $challenge, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $challenge->isOpponentTeam($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Lời khiêu chiến này không dành cho bạn!',
            ], 403);
        }

        if (! $challenge->recordResponse(accepted: true)) {
            return response()->json([
                'success' => false,
                'message' => 'Lời khiêu chiến đã hết hạn hoặc đã được phản hồi.',
            ], 409);
        }

        $challenger = $challenge->challenger;
        $challengerPartner = $challenge->challengerPartner;

        $acceptMsg = $challenge->isDoubles()
            ? "{$user->name} đã đại diện đội tiếp chiêu lời thách đấu đôi kèo '{$challenge->prize}' của các bạn! Trận đấu đã được xếp lịch tại mục 'Trận đấu sắp diễn ra'."
            : "{$user->name} đã đồng ý tiếp chiêu lời thách đấu kèo '{$challenge->prize}' của bạn! Trận đấu đã được xếp lịch tại mục 'Trận đấu sắp diễn ra'.";

        if ($challenger) {
            UserNotification::create([
                'user_id' => $challenger->id,
                'type' => 'duel_accepted',
                'title' => '⚔️ ĐỐI THỦ ĐÃ TIẾP CHIÊU!',
                'message' => $acceptMsg,
                'link' => route('calendar', ['tab' => 'upcoming']),
            ]);

            if ($challenger->telegram_id) {
                $telegramBot->sendMessage(
                    $challenger->telegram_id,
                    "⚔️ <b>{$user->name} ĐÃ TIẾP CHIÊU!</b>\n\n"
                    ."Kèo: <b>{$challenge->prize}</b>\n"
                    .'Trận đấu đã được đưa vào mục <b>Trận đấu sắp diễn ra</b> trên website. Sau khi thi đấu xong, 2 đội hoặc Admin có thể lên chọn đội thắng!'
                );
            }
        }

        if ($challengerPartner) {
            UserNotification::create([
                'user_id' => $challengerPartner->id,
                'type' => 'duel_accepted',
                'title' => '⚔️ ĐỐI THỦ ĐÃ TIẾP CHIÊU TRẬN ĐÔI!',
                'message' => $acceptMsg,
                'link' => route('calendar', ['tab' => 'upcoming']),
            ]);

            if ($challengerPartner->telegram_id && $challengerPartner->telegram_id !== $challenger?->telegram_id) {
                $telegramBot->sendMessage(
                    $challengerPartner->telegram_id,
                    "⚔️ <b>{$user->name} ĐÃ TIẾP CHIÊU TRẬN ĐÔI!</b>\n\n"
                    ."Kèo: <b>{$challenge->prize}</b>\n"
                    .'Trận đấu đã được đưa vào mục <b>Trận đấu sắp diễn ra</b> trên website. Sau khi thi đấu xong, 2 đội hoặc Admin có thể lên chọn đội thắng!'
                );
            }
        }

        $telegramBot->sendDuelAcceptedNotification($challenge);

        return response()->json([
            'success' => true,
            'message' => 'Bạn đã TIẾP CHIÊU thách đấu! Trận đấu đã được đưa vào lịch sắp diễn ra.',
            'redirect_url' => route('calendar', ['tab' => 'upcoming']),
        ]);
    }

    public function resolve(Request $request, BilliardChallenge $challenge, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $challenge->isParticipant($user) && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có các cơ thủ trong trận khiêu chiến này hoặc Quản trị viên mới được chọn đội thắng!',
            ], 403);
        }

        if ($challenge->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => "Trận khiêu chiến không ở trạng thái chờ thi đấu (hiện tại: {$challenge->status})!",
            ], 400);
        }

        $isDoubles = $challenge->isDoubles();

        if ($isDoubles) {
            $validated = $request->validate([
                'winner_id' => ['required', 'integer', 'in:'.$challenge->challenger_id.','.$challenge->opponent_id],
            ], [
                'winner_id.required' => 'Vui lòng chọn đội chiến thắng!',
                'winner_id.in' => 'Đội thắng phải là 1 trong 2 đội khiêu chiến!',
            ]);

            $isChallengerTeamWon = ((int) $validated['winner_id'] === $challenge->challenger_id);

            $winnerId = $isChallengerTeamWon ? $challenge->challenger_id : $challenge->opponent_id;
            $winnerPartnerId = $isChallengerTeamWon ? $challenge->challenger_partner_id : $challenge->opponent_partner_id;
            $loserId = $isChallengerTeamWon ? $challenge->opponent_id : $challenge->challenger_id;
            $loserPartnerId = $isChallengerTeamWon ? $challenge->opponent_partner_id : $challenge->challenger_partner_id;

            $winner = User::findOrFail($winnerId);
            $winnerPartner = $winnerPartnerId ? User::find($winnerPartnerId) : null;
            $loser = User::findOrFail($loserId);
            $loserPartner = $loserPartnerId ? User::find($loserPartnerId) : null;

            $winnerNames = $winnerPartner ? "{$winner->name} & {$winnerPartner->name}" : $winner->name;
            $loserNames = $loserPartner ? "{$loser->name} & {$loserPartner->name}" : $loser->name;

            $now = now();

            $match = BilliardMatch::create([
                'creator_id' => $user->id,
                'match_type' => 'doubles',
                'player1_id' => $challenge->challenger_id,
                'player1_partner_id' => $challenge->challenger_partner_id,
                'player2_id' => $challenge->opponent_id,
                'player2_partner_id' => $challenge->opponent_partner_id,
                'winner_id' => $winnerId,
                'winner_partner_id' => $winnerPartnerId,
                'loser_id' => $loserId,
                'loser_partner_id' => $loserPartnerId,
                'played_at' => $now,
                'prize' => $challenge->prize,
                'prize_status' => 'unpaid',
                'prize_partner_status' => 'unpaid',
                'notes' => 'Trận khiêu chiến đôi 2vs2: '.($challenge->message ?: 'Đã phân định thắng bại'),
                'status' => 'confirmed',
                'confirmed_at' => $now,
                'confirmed_by' => $user->isAdmin() ? 'admin' : ($challenge->isOpponentTeam($user) ? 'loser' : 'winner'),
                'created_from' => 'challenge',
            ]);

            MatchHistory::create([
                'billiard_match_id' => $match->id,
                'user_id' => $user->id,
                'action' => 'confirmed',
                'note' => "Trận khiêu chiến đôi hoàn tất. Đội thắng: {$winnerNames}, Đội thua: {$loserNames}",
                'metadata' => [
                    'winner_id' => $winnerId,
                    'winner_partner_id' => $winnerPartnerId,
                    'loser_id' => $loserId,
                    'loser_partner_id' => $loserPartnerId,
                    'prize' => $challenge->prize,
                    'resolved_by' => $user->id,
                ],
            ]);

            $challenge->update([
                'status' => 'completed',
                'match_id' => $match->id,
            ]);

            // Tự động phân định thắng thua các kèo ngoài bên lề
            $challenge->resolveSideBets($isChallengerTeamWon ? 'challenger' : 'opponent', $telegramBot);

            // Notify winners
            foreach (array_filter([$winner, $winnerPartner]) as $w) {
                UserNotification::create([
                    'user_id' => $w->id,
                    'billiard_match_id' => $match->id,
                    'type' => 'duel_won',
                    'title' => '🏆 ĐỘI BẠN ĐÃ CHIẾN THẮNG TRẬN KHIÊU CHIẾN ĐÔI!',
                    'message' => "Đội của bạn ({$winnerNames}) đã đánh bại {$loserNames} trong trận khiêu chiến kèo {$challenge->prize}!",
                    'link' => route('matches.show', $match->id),
                ]);
            }

            // Notify losers
            foreach (array_filter([$loser, $loserPartner]) as $l) {
                UserNotification::create([
                    'user_id' => $l->id,
                    'billiard_match_id' => $match->id,
                    'type' => 'duel_lost',
                    'title' => '💀 KẾT QUẢ KHIÊU CHIẾN: ĐỘI BẠN ĐÃ THUA',
                    'message' => "Đội của bạn ({$loserNames}) đã để thua trước {$winnerNames}. Đừng quên thanh toán kèo: {$challenge->prize} nhé!",
                    'link' => route('matches.show', $match->id),
                ]);
            }

            $telegramBot->sendDuelCompletedNotification($challenge, $match, $winner, $loser);

            return response()->json([
                'success' => true,
                'message' => "Đã ghi nhận kết quả: Đội {$winnerNames} đã chiến thắng!",
                'match_id' => $match->id,
            ]);
        }

        // Singles
        $validated = $request->validate([
            'winner_id' => ['required', 'integer', 'in:'.$challenge->challenger_id.','.$challenge->opponent_id],
        ], [
            'winner_id.required' => 'Vui lòng chọn người chiến thắng!',
            'winner_id.in' => 'Người thắng phải là 1 trong 2 cơ thủ khiêu chiến!',
        ]);

        $winnerId = (int) $validated['winner_id'];
        $loserId = ($winnerId === $challenge->challenger_id) ? $challenge->opponent_id : $challenge->challenger_id;

        $winner = User::findOrFail($winnerId);
        $loser = User::findOrFail($loserId);

        $now = now();

        $match = BilliardMatch::create([
            'creator_id' => $user->id,
            'match_type' => 'singles',
            'player1_id' => $winnerId,
            'player2_id' => $loserId,
            'winner_id' => $winnerId,
            'loser_id' => $loserId,
            'played_at' => $now,
            'prize' => $challenge->prize,
            'prize_status' => 'unpaid',
            'notes' => 'Trận khiêu chiến: '.($challenge->message ?: 'Đã phân định thắng bại'),
            'status' => 'confirmed',
            'confirmed_at' => $now,
            'confirmed_by' => $user->isAdmin() ? 'admin' : ($user->id === $loserId ? 'loser' : 'winner'),
            'created_from' => 'challenge',
        ]);

        MatchHistory::create([
            'billiard_match_id' => $match->id,
            'user_id' => $user->id,
            'action' => 'confirmed',
            'note' => "Trận khiêu chiến hoàn tất. Người thắng: {$winner->name}, Người thua: {$loser->name}",
            'metadata' => [
                'winner_id' => $winnerId,
                'loser_id' => $loserId,
                'prize' => $challenge->prize,
                'resolved_by' => $user->id,
            ],
        ]);

        $challenge->update([
            'status' => 'completed',
            'match_id' => $match->id,
        ]);

        // Tự động phân định thắng thua các kèo ngoài bên lề
        $isChallengerWon = ($winnerId === $challenge->challenger_id);
        $challenge->resolveSideBets($isChallengerWon ? 'challenger' : 'opponent', $telegramBot);

        // Thông báo in-app
        UserNotification::create([
            'user_id' => $winner->id,
            'billiard_match_id' => $match->id,
            'type' => 'duel_won',
            'title' => '🏆 BẠN ĐÃ CHIẾN THẮNG TRẬN KHIÊU CHIẾN!',
            'message' => "Bạn đã đánh bại {$loser->name} trong trận khiêu chiến kèo {$challenge->prize}!",
            'link' => route('matches.show', $match->id),
        ]);

        UserNotification::create([
            'user_id' => $loser->id,
            'billiard_match_id' => $match->id,
            'type' => 'duel_lost',
            'title' => '💀 KẾT QUẢ KHIÊU CHIẾN: BẠN ĐÃ THUA',
            'message' => "Bạn đã để thua trước {$winner->name}. Đừng quên thanh toán kèo: {$challenge->prize} nhé!",
            'link' => route('matches.show', $match->id),
        ]);

        $telegramBot->sendDuelCompletedNotification($challenge, $match, $winner, $loser);

        return response()->json([
            'success' => true,
            'message' => "Đã ghi nhận kết quả thành công: {$winner->name} đã chiến thắng!",
            'match_id' => $match->id,
        ]);
    }

    public function decline(BilliardChallenge $challenge, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $challenge->isOpponentTeam($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Lời khiêu chiến này không dành cho bạn!',
            ], 403);
        }

        if (! $challenge->recordResponse(accepted: false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lời khiêu chiến đã hết hạn hoặc đã được phản hồi.',
            ], 409);
        }

        $challenger = $challenge->challenger;
        $challengerPartner = $challenge->challengerPartner;

        $declineMsg = "{$user->name} đã từ chối lời khiêu chiến kèo {$challenge->prize} của bạn.";

        if ($challenger) {
            UserNotification::create([
                'user_id' => $challenger->id,
                'type' => 'duel_declined',
                'title' => '🏳️ ĐỐI THỦ ĐÃ TỪ CHỐI!',
                'message' => $declineMsg,
                'link' => route('calendar'),
            ]);

            if ($challenger->telegram_id) {
                $telegramBot->sendMessage(
                    $challenger->telegram_id,
                    "🏳️ <b>ĐỐI THỦ ĐÃ TỪ CHỐI THÁCH ĐẤU!</b>\n\n"
                    ."{$user->name} đã từ chối lời khiêu chiến kèo <i>{$challenge->prize}</i>."
                );
            }
        }

        if ($challengerPartner) {
            UserNotification::create([
                'user_id' => $challengerPartner->id,
                'type' => 'duel_declined',
                'title' => '🏳️ ĐỐI THỦ ĐÃ TỪ CHỐI TRẬN ĐÔI!',
                'message' => $declineMsg,
                'link' => route('calendar'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bạn đã từ chối lời khiêu chiến!',
        ]);
    }

    public function notifications(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json([
                'unreadCount' => 0,
                'notifications' => [],
                'pendingActionMatches' => [],
            ]);
        }

        $unreadCount = UserNotification::where('user_id', $user->id)->where('is_read', false)->count();
        $notifications = UserNotification::where('user_id', $user->id)
            ->with(['match.winner', 'match.winnerPartner'])
            ->latest()->orderByDesc('id')->take(10)->get();

        $pendingActionMatches = BilliardMatch::where('status', 'pending')
            ->where('creator_id', '!=', $user->id)
            ->where(function ($q) use ($user) {
                $q->where('loser_id', $user->id)
                    ->orWhere('loser_partner_id', $user->id);
            })
            ->with('creator')
            ->get()
            ->map(function ($m) {
                $remainingSecs = $m->getRemainingSecondsForAutoConfirm();

                return [
                    'id' => $m->id,
                    'creator_name' => $m->creator->name,
                    'prize' => $m->prize,
                    'hours' => floor($remainingSecs / 3600),
                    'mins' => floor(($remainingSecs % 3600) / 60),
                ];
            });

        return response()->json([
            'unreadCount' => $unreadCount,
            'notifications' => $notifications->map(function (UserNotification $notification) use ($user): array {
                $isMatchRequest = $notification->type === 'match_pending_confirmation';
                $match = $isMatchRequest ? $notification->match : null;
                $canViewMatch = $match && (! $match->isHidden() || $user->isAdmin());
                $withinDeadline = $canViewMatch && (! $match->auto_confirm_at || $match->auto_confirm_at->gt(now()));
                $canConfirm = $withinDeadline && $match->isWaitingForUser($user);
                $matchStatus = match (true) {
                    ! $isMatchRequest => null,
                    ! $canViewMatch => 'Trận không còn khả dụng',
                    $canConfirm => 'Cần xác nhận',
                    $match->status === 'confirmed' => 'Đã xác nhận',
                    $match->status === 'resolved' => 'Đã xử lý',
                    $match->status === 'disputed' => 'Đang khiếu nại',
                    $match->status === 'cancelled' => 'Đã hủy',
                    ! $withinDeadline => 'Đã hết hạn phản hồi',
                    default => 'Đang chờ xác nhận',
                };
                $statusMessage = $canViewMatch ? "Trận #{$match->id} · {$matchStatus}." : $matchStatus.'.';

                return [
                    'id' => $notification->id,
                    'title' => $isMatchRequest && ! $canConfirm ? 'Cập nhật kết quả trận đấu' : $notification->title,
                    'message' => $isMatchRequest && ! $canConfirm ? $statusMessage : $notification->message,
                    'link' => $isMatchRequest
                        ? ($canViewMatch ? route('matches.show', $match) : null)
                        : ($notification->link ? url($notification->link) : null),
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at->locale('vi')->diffForHumans(),
                    'match_id' => $notification->billiard_match_id,
                    'is_pending_match' => $isMatchRequest,
                    'can_confirm' => $canConfirm,
                    'confirm_url' => $canConfirm ? route('matches.confirm', $match) : null,
                    'confirm_text' => $canConfirm ? "Người thắng: {$match->winner_names}. Món kèo: {$match->prize}." : null,
                ];
            }),
            'pendingActionMatches' => $pendingActionMatches,
        ]);
    }

    public function markNotificationsRead(): JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            UserNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }
}
