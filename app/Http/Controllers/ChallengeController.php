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
            $msgForOpponents = "Đội {$user->name} & {$challengerPartner?->name} vừa phát lệnh khiêu chiến ĐÔI 2VS2 tới đội của bạn ({$opponent?->name} & {$opponentPartner?->name}) với kèo: {$challenge->prize}!";

            // Opponent 1
            if ($opponent) {
                UserNotification::create([
                    'user_id' => $opponent->id,
                    'type' => 'duel_challenge',
                    'title' => '🚨 BÁO ĐỘNG KHIÊU CHIẾN ĐÔI 2VS2!',
                    'message' => $msgForOpponents,
                    'link' => route('calendar'),
                ]);
            }

            // Opponent 2 (Partner)
            if ($opponentPartner) {
                UserNotification::create([
                    'user_id' => $opponentPartner->id,
                    'type' => 'duel_challenge',
                    'title' => '🚨 BÁO ĐỘNG KHIÊU CHIẾN ĐÔI 2VS2!',
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
                    'title' => '🚨 BÁO ĐỘNG KHIÊU CHIẾN!',
                    'message' => "{$user->name} vừa gửi tối hậu thư thách đấu bạn với kèo: {$challenge->prize}!",
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
            'message' => "Đã phát lệnh khiêu chiến tới {$targetNames}! Màn hình đối thủ sẽ nháy đỏ SOS!",
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
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
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

        if ($challenge->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Lời khiêu chiến đã ở trạng thái {$challenge->status}!",
            ], 400);
        }

        $challenge->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

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

        if ($challenge->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Lời khiêu chiến đã ở trạng thái {$challenge->status}!",
            ], 400);
        }

        $challenge->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);

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
        $notifications = UserNotification::where('user_id', $user->id)->latest()->take(10)->get();

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
            'notifications' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'link' => $n->link ? url($n->link) : '#',
                'is_read' => (bool) $n->is_read,
                'created_at' => $n->created_at->diffForHumans(),
                'match_id' => $n->billiard_match_id,
                'is_pending_match' => ($n->type === 'match_pending_confirmation'),
            ]),
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
