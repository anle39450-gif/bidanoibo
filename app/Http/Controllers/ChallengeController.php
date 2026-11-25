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
        $user = Auth::user();

        $validated = $request->validate([
            'opponent_id' => ['required', 'integer', 'exists:users,id'],
            'prize' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
        ], [
            'opponent_id.required' => 'Vui lòng chọn cơ thủ muốn thách đấu!',
            'prize.required' => 'Vui lòng nhập món kèo thách đấu!',
        ]);

        if ((int) $validated['opponent_id'] === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự khiêu chiến chính mình!',
            ], 422);
        }

        $challenge = BilliardChallenge::create([
            'challenger_id' => $user->id,
            'opponent_id' => $validated['opponent_id'],
            'prize' => $validated['prize'],
            'message' => $validated['message'] ?: 'Ra bàn solo ngay nếu không muốn bị coi là sợ!',
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        $opponent = User::find($validated['opponent_id']);

        if ($opponent) {
            UserNotification::create([
                'user_id' => $opponent->id,
                'type' => 'duel_challenge',
                'title' => '🚨 BÁO ĐỘNG KHIÊU CHIẾN!',
                'message' => "{$user->name} vừa gửi tối hậu thư thách đấu bạn với kèo: {$challenge->prize}!",
                'link' => route('calendar'),
            ]);

            $telegramBot->sendDuelChallengeNotification($challenge);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã phát lệnh khiêu chiến tới {$opponent?->name}! Màn hình đối thủ sẽ nháy đỏ SOS!",
            'challenge' => $challenge,
        ]);
    }

    public function checkPending(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['has_challenge' => false]);
        }

        $challenge = BilliardChallenge::where('opponent_id', $user->id)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('challenger')
            ->latest()
            ->first();

        if (! $challenge) {
            return response()->json(['has_challenge' => false]);
        }

        return response()->json([
            'has_challenge' => true,
            'challenge' => [
                'id' => $challenge->id,
                'challenger_id' => $challenge->challenger_id,
                'challenger_name' => $challenge->challenger->name,
                'challenger_username' => $challenge->challenger->username,
                'challenger_avatar' => $challenge->challenger->avatar_url,
                'prize' => $challenge->prize,
                'message' => $challenge->message,
                'created_at' => $challenge->created_at->diffForHumans(),
            ],
        ]);
    }

    public function accept(BilliardChallenge $challenge, TelegramBotService $telegramBot): JsonResponse
    {
        $user = Auth::user();

        if ($challenge->opponent_id !== $user->id) {
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

        if ($challenger) {
            UserNotification::create([
                'user_id' => $challenger->id,
                'type' => 'duel_accepted',
                'title' => '⚔️ ĐỐI THỦ ĐÃ TIẾP CHIÊU!',
                'message' => "{$user->name} đã đồng ý tiếp chiêu lời thách đấu kèo '{$challenge->prize}' của bạn! Trận đấu đã được xếp lịch tại mục 'Trận đấu sắp diễn ra'.",
                'link' => route('calendar', ['tab' => 'upcoming']),
            ]);

            if ($challenger->telegram_id) {
                $telegramBot->sendMessage(
                    $challenger->telegram_id,
                    "⚔️ <b>{$user->name} ĐÃ TIẾP CHIÊU!</b>\n\n"
                    ."Kèo: <b>{$challenge->prize}</b>\n"
                    .'Trận đấu đã được đưa vào mục <b>Trận đấu sắp diễn ra</b> trên website. Sau khi thi đấu xong, 2 cơ thủ hoặc Admin có thể lên chọn người thắng!'
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bạn đã TIẾP CHIÊU thách đấu của {$challenger?->name}! Trận đấu đã được đưa vào lịch sắp diễn ra.",
            'redirect_url' => route('calendar', ['tab' => 'upcoming']),
        ]);
    }

    public function resolve(Request $request, BilliardChallenge $challenge, TelegramBotService $telegramBot): JsonResponse
    {
        $user = Auth::user();

        if ($user->id !== $challenge->challenger_id && $user->id !== $challenge->opponent_id && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ có 2 cơ thủ trong trận khiêu chiến này hoặc Quản trị viên mới được chọn người thắng!',
            ], 403);
        }

        if ($challenge->status !== 'accepted') {
            return response()->json([
                'success' => false,
                'message' => "Trận khiêu chiến không ở trạng thái chờ thi đấu (hiện tại: {$challenge->status})!",
            ], 400);
        }

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
        $user = Auth::user();

        if ($challenge->opponent_id !== $user->id) {
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

        if ($challenger) {
            UserNotification::create([
                'user_id' => $challenger->id,
                'type' => 'duel_declined',
                'title' => '🏳️ ĐỐI THỦ ĐÃ TỪ CHỐI!',
                'message' => "{$user->name} đã từ chối lời khiêu chiến kèo {$challenge->prize} của bạn.",
                'link' => route('calendar'),
            ]);

            if ($challenger->telegram_id) {
                $telegramBot->sendMessage(
                    $challenger->telegram_id,
                    "🏳️ <b>ĐỐI THỦ ĐÃ TỪ CHỐI THÁCH ĐẤU!</b>\n\n"
                    ."{$user->name} đã từ chối lời khiêu chiến kèo <i>{$challenge->prize}</i> của bạn."
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Bạn đã từ chối lời khiêu chiến của {$challenger?->name}!",
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
