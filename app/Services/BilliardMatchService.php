<?php

namespace App\Services;

use App\Models\BilliardMatch;
use App\Models\MatchHistory;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BilliardMatchService
{
    public function __construct(
        protected TelegramBotService $telegramBot
    ) {}

    /**
     * Create a new match.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createMatch(array $data, User $creator): BilliardMatch
    {
        $matchType = $data['match_type'] ?? 'singles';
        $player1Id = (int) $data['player1_id'];
        $player2Id = (int) $data['player2_id'];
        $player1PartnerId = ! empty($data['player1_partner_id']) ? (int) $data['player1_partner_id'] : null;
        $player2PartnerId = ! empty($data['player2_partner_id']) ? (int) $data['player2_partner_id'] : null;

        $participants = array_filter([$player1Id, $player1PartnerId, $player2Id, $player2PartnerId]);
        if (count($participants) !== count(array_unique($participants))) {
            throw ValidationException::withMessages([
                'player2_id' => 'Các người chơi tham gia trận đấu phải khác nhau!',
            ]);
        }

        $winnerId = (int) $data['winner_id'];
        if ($matchType === 'doubles') {
            if ($winnerId === $player1Id || $winnerId === $player1PartnerId) {
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
            if ($winnerId !== $player1Id && $winnerId !== $player2Id) {
                throw ValidationException::withMessages([
                    'winner_id' => 'Người thắng phải là 1 trong 2 người tham gia trận đấu!',
                ]);
            }
            $loserId = ($winnerId === $player1Id) ? $player2Id : $player1Id;
            $winnerPartnerId = null;
            $loserPartnerId = null;
        }

        if (isset($data['loser_id'])) {
            $loserId = (int) $data['loser_id'];
        }
        if (array_key_exists('winner_partner_id', $data)) {
            $winnerPartnerId = ! empty($data['winner_partner_id']) ? (int) $data['winner_partner_id'] : null;
        }
        if (array_key_exists('loser_partner_id', $data)) {
            $loserPartnerId = ! empty($data['loser_partner_id']) ? (int) $data['loser_partner_id'] : null;
        }

        $now = Carbon::now();

        return DB::transaction(function () use ($data, $creator, $matchType, $player1Id, $player1PartnerId, $player2Id, $player2PartnerId, $winnerId, $winnerPartnerId, $loserId, $loserPartnerId, $now) {
            $match = BilliardMatch::create([
                'creator_id' => $creator->id,
                'match_type' => $matchType,
                'player1_id' => $player1Id,
                'player1_partner_id' => $player1PartnerId,
                'player2_id' => $player2Id,
                'player2_partner_id' => $player2PartnerId,
                'winner_id' => $winnerId,
                'winner_partner_id' => $winnerPartnerId,
                'loser_id' => $loserId,
                'loser_partner_id' => $loserPartnerId,
                'played_at' => $data['played_at'] ?? $now,
                'prize' => $data['prize'] ?? 'Kèo cà phê muối',
                'notes' => $data['notes'] ?? null,
                'evidence_path' => $data['evidence_path'] ?? null,
                'status' => 'pending',
                'notified_at' => $now,
                'auto_confirm_at' => $now->copy()->addHours(24),
                'created_from' => $data['created_from'] ?? 'web',
            ]);

            // Audit history
            MatchHistory::create([
                'billiard_match_id' => $match->id,
                'user_id' => $creator->id,
                'action' => 'created',
                'note' => "Trận đấu ({$matchType}) được tạo bởi {$creator->name}",
                'metadata' => [
                    'match_type' => $matchType,
                    'prize' => $match->prize,
                    'winner_id' => $match->winner_id,
                    'loser_id' => $match->loser_id,
                ],
            ]);

            // Determine opponent(s) who need to confirm
            $opponents = $match->getOpponentsOf($creator);
            if (empty($opponents)) {
                if ($match->loser) {
                    $opponents = [$match->loser];
                }
            }

            // Create in-app notifications & telegram notifications for opponents
            foreach ($opponents as $opponent) {
                UserNotification::create([
                    'user_id' => $opponent->id,
                    'billiard_match_id' => $match->id,
                    'type' => 'match_pending_confirmation',
                    'title' => 'Trận đấu mới cần bạn xác nhận!',
                    'message' => "{$creator->name} đã ghi nhận trận đấu với bạn (Kèo: {$match->prize}). Bạn có 24h để xác nhận hoặc khiếu nại.",
                    'link' => "/matches/{$match->id}",
                ]);

                // Send Telegram Notification
                $this->telegramBot->sendMatchConfirmationRequest($match, $opponent);
            }

            return $match;
        });
    }

    /**
     * Opponent or admin confirms the match.
     *
     * @throws ValidationException
     */
    public function confirmMatch(BilliardMatch $match, User $user, string $by = 'opponent'): bool
    {
        if ($match->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => "Trận đấu không ở trạng thái chờ xác nhận (hiện tại: {$match->status})",
            ]);
        }

        // Rule: Creator cannot confirm for opponent unless user is admin
        if ($match->creator_id === $user->id && ! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Người tạo trận không thể tự ý xác nhận thay đối thủ!',
            ]);
        }

        return DB::transaction(function () use ($match, $user, $by) {
            $match->update([
                'status' => 'confirmed',
                'confirmed_at' => Carbon::now(),
                'confirmed_by' => $by,
            ]);

            MatchHistory::create([
                'billiard_match_id' => $match->id,
                'user_id' => $user->id,
                'action' => 'confirmed',
                'note' => "Kết quả đã được xác nhận bởi {$user->name} ({$by})",
            ]);

            // Notify creator
            if ($match->creator_id !== $user->id) {
                UserNotification::create([
                    'user_id' => $match->creator_id,
                    'billiard_match_id' => $match->id,
                    'type' => 'match_confirmed',
                    'title' => 'Trận đấu đã được xác nhận!',
                    'message' => "{$user->name} đã xác nhận kết quả trận đấu #{$match->id} (Kèo: {$match->prize}).",
                    'link' => "/matches/{$match->id}",
                ]);
            }

            $this->telegramBot->sendMatchStatusUpdate($match, "Trận đấu đã được {$user->name} xác nhận chính thức!");

            return true;
        });
    }

    /**
     * Opponent disputes the match.
     *
     * @throws ValidationException
     */
    public function disputeMatch(BilliardMatch $match, User $user, string $reason, ?string $evidencePath = null): bool
    {
        if ($match->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => 'Chỉ có thể khiếu nại trận đấu đang ở trạng thái chờ xác nhận!',
            ]);
        }

        if (! $match->isParticipating($user) && ! $user->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Chỉ người tham gia trận đấu mới có quyền khiếu nại!',
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reason' => 'Bắt buộc phải cung cấp lý do khi từ chối kết quả!',
            ]);
        }

        return DB::transaction(function () use ($match, $user, $reason, $evidencePath) {
            $match->update([
                'status' => 'disputed',
                'disputed_at' => Carbon::now(),
                'dispute_reason' => $reason,
                'dispute_evidence_path' => $evidencePath,
            ]);

            MatchHistory::create([
                'billiard_match_id' => $match->id,
                'user_id' => $user->id,
                'action' => 'disputed',
                'note' => "{$user->name} khiếu nại: \"{$reason}\"",
                'metadata' => [
                    'reason' => $reason,
                    'evidence_path' => $evidencePath,
                ],
            ]);

            // Notify Creator
            UserNotification::create([
                'user_id' => $match->creator_id,
                'billiard_match_id' => $match->id,
                'type' => 'match_disputed',
                'title' => 'Trận đấu bị khiếu nại!',
                'message' => "{$user->name} đã khiếu nại kết quả trận đấu #{$match->id}. Lý do: {$reason}",
                'link' => "/matches/{$match->id}",
            ]);

            // Notify all Admins
            $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $user->id) {
                    UserNotification::create([
                        'user_id' => $admin->id,
                        'billiard_match_id' => $match->id,
                        'type' => 'match_disputed',
                        'title' => 'Có tranh chấp trận đấu mới cần xử lý!',
                        'message' => "Trận #{$match->id} giữa {$match->team1_names} và {$match->team2_names} đang có tranh chấp.",
                        'link' => "/matches/{$match->id}",
                    ]);
                }
            }

            $this->telegramBot->sendMatchStatusUpdate($match, "Trận đấu đã bị {$user->name} khiếu nại. Quản trị viên sẽ xem xét!");

            return true;
        });
    }

    /**
     * Admin resolves a disputed match.
     *
     * @throws ValidationException
     */
    public function adminResolveMatch(
        BilliardMatch $match,
        User $admin,
        string $resolution,
        ?int $newWinnerId = null,
        string $adminNotes = ''
    ): bool {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => 'Chỉ quản trị viên mới có quyền xử lý tranh chấp!',
            ]);
        }

        return DB::transaction(function () use ($match, $admin, $resolution, $adminNotes) {
            $oldValues = [
                'status' => $match->status,
                'winner_id' => $match->winner_id,
                'loser_id' => $match->loser_id,
            ];

            if ($resolution === 'cancel') {
                $match->update([
                    'status' => 'cancelled',
                    'admin_notes' => $adminNotes,
                ]);

                $historyAction = 'cancelled';
                $actionNote = "Quản trị viên {$admin->name} đã hủy trận đấu. Ghi chú: {$adminNotes}";
            } elseif ($resolution === 'reverse_winner') {
                $newWinner = $match->player1_id === $match->winner_id ? $match->player2_id : $match->player1_id;
                $newLoser = $match->winner_id;

                $match->update([
                    'status' => 'resolved',
                    'winner_id' => $newWinner,
                    'loser_id' => $newLoser,
                    'confirmed_at' => Carbon::now(),
                    'confirmed_by' => 'admin',
                    'admin_notes' => $adminNotes,
                ]);

                $historyAction = 'resolved';
                $actionNote = "Quản trị viên {$admin->name} đảo ngược người thắng. Ghi chú: {$adminNotes}";
            } else {
                // confirm_initial
                $match->update([
                    'status' => 'resolved',
                    'confirmed_at' => Carbon::now(),
                    'confirmed_by' => 'admin',
                    'admin_notes' => $adminNotes,
                ]);

                $historyAction = 'resolved';
                $actionNote = "Quản trị viên {$admin->name} giữ nguyên kết quả ban đầu. Ghi chú: {$adminNotes}";
            }

            MatchHistory::create([
                'billiard_match_id' => $match->id,
                'user_id' => $admin->id,
                'action' => $historyAction,
                'note' => $actionNote,
                'metadata' => [
                    'old' => $oldValues,
                    'resolution' => $resolution,
                    'admin_notes' => $adminNotes,
                ],
            ]);

            // Notify all players
            $allPlayers = array_values(array_filter([
                $match->player1,
                $match->player1Partner,
                $match->player2,
                $match->player2Partner,
            ]));

            foreach ($allPlayers as $player) {
                UserNotification::create([
                    'user_id' => $player->id,
                    'billiard_match_id' => $match->id,
                    'type' => 'match_resolved',
                    'title' => 'Tranh chấp trận đấu đã được xử lý!',
                    'message' => "Quản trị viên đã đưa ra quyết định cho trận đấu #{$match->id}: {$actionNote}",
                    'link' => "/matches/{$match->id}",
                ]);
            }

            $this->telegramBot->sendMatchStatusUpdate($match, "Quyết định của quản trị viên: {$actionNote}");

            return true;
        });
    }

    /**
     * Auto-confirm matches that exceeded 24 hours without response.
     */
    public function autoConfirmExpiredMatches(): int
    {
        $hasExpired = BilliardMatch::where('status', 'pending')
            ->whereNotNull('auto_confirm_at')
            ->where('auto_confirm_at', '<=', Carbon::now())
            ->exists();

        if (! $hasExpired) {
            return 0;
        }

        $expiredMatches = BilliardMatch::where('status', 'pending')
            ->whereNotNull('auto_confirm_at')
            ->where('auto_confirm_at', '<=', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredMatches as $match) {
            DB::transaction(function () use ($match) {
                $match->update([
                    'status' => 'confirmed',
                    'confirmed_at' => Carbon::now(),
                    'confirmed_by' => 'auto_24h',
                ]);

                MatchHistory::create([
                    'billiard_match_id' => $match->id,
                    'user_id' => null,
                    'action' => 'auto_confirmed',
                    'note' => 'Hệ thống tự động xác nhận sau 24 giờ không nhận được phản hồi từ đối thủ.',
                ]);

                // Notify all players
                $allPlayers = array_values(array_filter([
                    $match->player1,
                    $match->player1Partner,
                    $match->player2,
                    $match->player2Partner,
                ]));

                foreach ($allPlayers as $player) {
                    UserNotification::create([
                        'user_id' => $player->id,
                        'billiard_match_id' => $match->id,
                        'type' => 'match_confirmed',
                        'title' => 'Trận đấu đã được tự động xác nhận sau 24h',
                        'message' => "Trận đấu #{$match->id} đã được tự động xác nhận theo quy tắc 24h.",
                        'link' => "/matches/{$match->id}",
                    ]);
                }

                $this->telegramBot->sendMatchStatusUpdate($match, '⏰ Trận đấu đã tự động xác nhận sau 24 giờ không có khiếu nại!');
            });

            $count++;
        }

        return $count;
    }
}
