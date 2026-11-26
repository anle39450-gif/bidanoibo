<?php

namespace App\Http\Controllers;

use App\Models\BilliardChallenge;
use App\Models\BilliardSideBet;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SideBetController extends Controller
{
    /**
     * Create a new side bet on a challenge.
     */
    public function store(Request $request, BilliardChallenge $challenge, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (in_array($challenge->status, ['completed', 'declined', 'cancelled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Trận khiêu chiến này đã kết thúc hoặc không còn khả dụng để vào kèo!',
            ], 400);
        }

        $validated = $request->validate([
            'bet_on_team' => ['required', 'string', 'in:challenger,opponent'],
            'target_id' => ['required', 'integer', 'exists:users,id', 'different:creator_id'],
            'prize' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'bet_on_team.required' => 'Vui lòng chọn đội bạn muốn bắt kèo!',
            'target_id.required' => 'Vui lòng chọn người bạn muốn bắt kèo cùng!',
            'target_id.different' => 'Bạn không thể tự bắt kèo với chính mình!',
            'prize.required' => 'Vui lòng nhập món kèo thách thức!',
        ]);

        if ((int) $validated['target_id'] === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự bắt kèo với chính mình!',
            ], 422);
        }

        $targetUser = User::findOrFail((int) $validated['target_id']);

        $sideBet = BilliardSideBet::create([
            'billiard_challenge_id' => $challenge->id,
            'creator_id' => $user->id,
            'target_id' => $targetUser->id,
            'bet_on_team' => $validated['bet_on_team'],
            'prize' => trim($validated['prize']),
            'notes' => ! empty($validated['notes']) ? trim($validated['notes']) : null,
            'status' => 'pending',
        ]);

        $creatorTeam = $sideBet->creator_team_name;
        $targetTeam = $sideBet->target_team_name;

        // In-app notification for target
        UserNotification::create([
            'user_id' => $targetUser->id,
            'type' => 'side_bet_invite',
            'title' => '🎲 BẠN ĐƯỢC RỦ VÀO KÈO NGOÀI!',
            'message' => "{$user->name} rủ bạn vào kèo ngoài trận {$challenge->challenger_team_names} vs {$challenge->opponent_team_names}: {$user->name} bắt {$creatorTeam}, bạn bắt {$targetTeam}. Kèo: {$sideBet->prize}!",
            'link' => route('calendar', ['tab' => 'upcoming']),
        ]);

        // Telegram DM notification for target if connected
        if ($targetUser->telegram_id) {
            $msg = "🎲 <b>KÈO NGOÀI BÊN LỀ!</b>\n\n"
                ."<b>{$user->name}</b> vừa rủ bạn vào kèo ngoài trận <b>{$challenge->challenger_team_names}</b> vs <b>{$challenge->opponent_team_names}</b>:\n"
                ."• {$user->name} bắt: <b>{$creatorTeam}</b>\n"
                ."• Bạn bắt: <b>{$targetTeam}</b>\n"
                ."🎁 <b>Kèo:</b> <b>{$sideBet->prize}</b>\n";
            if ($sideBet->notes) {
                $msg .= "📝 <b>Lời nhắn:</b> <i>\"{$sideBet->notes}\"</i>\n";
            }
            $msg .= "\nBạn có đồng ý nhận kèo ngoài này không?";

            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '⚔️ NHẬN KÈO', 'callback_data' => "sidebet_accept_{$sideBet->id}"],
                        ['text' => '🏳️ TỪ CHỐI', 'callback_data' => "sidebet_decline_{$sideBet->id}"],
                    ],
                    [
                        ['text' => '🌐 Xem Trên Web', 'url' => url('/calendar?tab=upcoming')],
                    ],
                ],
            ];

            $telegramBot->sendMessage($targetUser->telegram_id, $msg, $markup);
        }

        return response()->json([
            'success' => true,
            'message' => "Đã gửi lời rủ bắt kèo ngoài tới {$targetUser->name} thành công!",
            'side_bet' => $sideBet->load(['creator', 'target']),
        ]);
    }

    /**
     * Accept an incoming side bet.
     */
    public function accept(BilliardSideBet $sideBet, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($sideBet->target_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải là người được mời nhận kèo này!',
            ], 403);
        }

        if ($sideBet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Kèo ngoài này đã ở trạng thái: {$sideBet->status}!",
            ], 400);
        }

        $sideBet->update(['status' => 'accepted']);

        // In-app notification for creator
        UserNotification::create([
            'user_id' => $sideBet->creator_id,
            'type' => 'side_bet_accepted',
            'title' => '⚔️ ĐỐI PHƯƠNG ĐÃ NHẬN KÈO NGOÀI!',
            'message' => "{$user->name} đã nhận lời bắt kèo ngoài món '{$sideBet->prize}' với bạn!",
            'link' => route('calendar', ['tab' => 'upcoming']),
        ]);

        if ($sideBet->creator && $sideBet->creator->telegram_id) {
            $telegramBot->sendMessage(
                $sideBet->creator->telegram_id,
                "⚔️ <b>KÈO NGOÀI ĐÃ ĐƯỢC CHỐT!</b>\n\n"
                ."<b>{$user->name}</b> đã đồng ý nhận kèo ngoài món <b>{$sideBet->prize}</b> với bạn! Cùng chờ kết quả trận đấu nhé!"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Bạn đã chấp nhận vào kèo ngoài thành công! Chúc bạn may mắn.',
            'side_bet' => $sideBet->fresh(['creator', 'target']),
        ]);
    }

    /**
     * Decline an incoming side bet.
     */
    public function decline(BilliardSideBet $sideBet, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($sideBet->target_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không phải là người được mời nhận kèo này!',
            ], 403);
        }

        if ($sideBet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Kèo ngoài này đã ở trạng thái: {$sideBet->status}!",
            ], 400);
        }

        $sideBet->update(['status' => 'declined']);

        // In-app notification for creator
        UserNotification::create([
            'user_id' => $sideBet->creator_id,
            'type' => 'side_bet_declined',
            'title' => '🏳️ ĐỐI PHƯƠNG TỪ CHỐI KÈO NGOÀI',
            'message' => "{$user->name} đã từ chối kèo ngoài món '{$sideBet->prize}' của bạn.",
            'link' => route('calendar', ['tab' => 'upcoming']),
        ]);

        if ($sideBet->creator && $sideBet->creator->telegram_id) {
            $telegramBot->sendMessage(
                $sideBet->creator->telegram_id,
                "🏳️ <b>ĐỐI PHƯƠNG TỪ CHỐI KÈO NGOÀI</b>\n\n"
                ."{$user->name} đã từ chối kèo ngoài món <i>{$sideBet->prize}</i> của bạn."
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối lời mời vào kèo ngoài.',
        ]);
    }

    /**
     * Cancel a pending side bet by creator or Admin.
     */
    public function cancel(BilliardSideBet $sideBet): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($sideBet->creator_id !== $user->id && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ người tạo hoặc Quản trị viên mới được hủy kèo ngoài này!',
            ], 403);
        }

        if ($sideBet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Không thể hủy vì kèo ngoài đã ở trạng thái: {$sideBet->status}!",
            ], 400);
        }

        $sideBet->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Đã hủy kèo ngoài thành công.',
        ]);
    }

    /**
     * Pay prize for a resolved side bet (by loser).
     */
    public function pay(Request $request, BilliardSideBet $sideBet, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($sideBet->loser_id !== $user->id && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ người thua hoặc Quản trị viên mới được xác nhận trả kèo ngoài!',
            ], 403);
        }

        if ($sideBet->status !== 'resolved') {
            return response()->json([
                'success' => false,
                'message' => 'Kèo ngoài chưa kết thúc nên chưa thể trả kèo!',
            ], 400);
        }

        $notes = $request->input('notes') ? trim((string) $request->input('notes')) : null;

        $sideBet->update([
            'prize_status' => 'paid',
            'prize_paid_at' => now(),
            'prize_notes' => $notes ?: 'Đã trả kèo',
        ]);

        if ($sideBet->winner) {
            UserNotification::create([
                'user_id' => $sideBet->winner_id,
                'type' => 'side_bet_paid',
                'title' => '🎁 ĐỐI THỦ ĐÃ TRẢ KÈO NGOÀI CHO BẠN!',
                'message' => "{$user->name} báo đã trả kèo ngoài '{$sideBet->prize}'. Vui lòng bấm xác nhận OK khi đã nhận đủ!",
                'link' => route('dashboard'),
            ]);

            if ($sideBet->winner->telegram_id) {
                $telegramBot->sendMessage(
                    $sideBet->winner->telegram_id,
                    "🎁 <b>ĐỐI THỦ ĐÃ TRẢ KÈO NGOÀI!</b>\n\n"
                    ."<b>{$user->name}</b> báo đã trả kèo ngoài món <b>{$sideBet->prize}</b> cho bạn!\n"
                    .($notes ? "📝 Ghi chú: <i>\"{$notes}\"</i>\n" : '')
                    ."Vui lòng kiểm tra và lên website bấm 'Đã OK' nhé!"
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái trả kèo ngoài! Đang chờ đối thủ xác nhận OK.',
        ]);
    }

    /**
     * Confirm prize received for a resolved side bet (by winner).
     */
    public function confirm(Request $request, BilliardSideBet $sideBet, TelegramBotService $telegramBot): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($sideBet->winner_id !== $user->id && ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ người thắng hoặc Quản trị viên mới được xác nhận nhận kèo!',
            ], 403);
        }

        if ($sideBet->status !== 'resolved' || $sideBet->prize_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Kèo ngoài chưa được đối thủ báo trả!',
            ], 400);
        }

        $feedback = $request->input('feedback') ? trim((string) $request->input('feedback')) : 'Đã nhận đủ kèo';

        $sideBet->update([
            'prize_status' => 'confirmed',
            'prize_confirmed_at' => now(),
            'prize_feedback' => $feedback,
        ]);

        if ($sideBet->loser) {
            UserNotification::create([
                'user_id' => $sideBet->loser_id,
                'type' => 'side_bet_confirmed',
                'title' => '✅ ĐÃ XÁC NHẬN OK KÈO NGOÀI!',
                'message' => "{$user->name} đã xác nhận nhận đủ kèo ngoài '{$sideBet->prize}' từ bạn. Sòng phẳng!",
                'link' => route('dashboard'),
            ]);

            if ($sideBet->loser->telegram_id) {
                $telegramBot->sendMessage(
                    $sideBet->loser->telegram_id,
                    "✅ <b>XÁC NHẬN KÈO NGOÀI HOÀN TẤT!</b>\n\n"
                    ."<b>{$user->name}</b> đã xác nhận nhận đủ kèo ngoài món <b>{$sideBet->prize}</b> từ bạn. Rất sòng phẳng!"
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã xác nhận OK hoàn tất kèo ngoài! Sòng phẳng.',
        ]);
    }
}
