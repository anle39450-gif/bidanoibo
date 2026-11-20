<?php

namespace App\Services;

use App\Models\BilliardMatch;
use App\Models\TelegramBotConfig;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected ?string $token;

    public function __construct()
    {
        $config = TelegramBotConfig::current();
        $this->token = $config->bot_token ?: config('services.telegram.bot_token');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->token);
    }

    protected function client(): PendingRequest
    {
        return Http::withoutVerifying();
    }

    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): bool
    {
        if (! $this->isConfigured()) {
            Log::info("Telegram bot not configured. Message would be: {$text}");

            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = $this->client()->post("https://api.telegram.org/bot{$this->token}/sendMessage", $payload);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('Telegram sendMessage error: '.$e->getMessage());

            return false;
        }
    }

    public function sendMatchConfirmationRequest(BilliardMatch $match, User $recipient): bool
    {
        if (! $recipient->telegram_id) {
            return false;
        }

        $creator = $match->creator;
        $winner = $match->winner;
        $loser = $match->loser;
        $deadline = $match->auto_confirm_at?->format('H:i d/m/Y') ?? 'sau 24h';
        $siteUrl = url("/matches/{$match->id}");

        $text = "🎱 <b>TRẬN ĐẤU MỚI CẦN BẠN XÁC NHẬN!</b>\n\n";
        $text .= "👤 <b>Người ghi nhận:</b> {$creator->name}\n";

        if ($match->isDoubles()) {
            $text .= "⚔️ <b>Thể thức:</b> Đánh đôi 2vs2\n";
            $text .= "👥 <b>Đội 1:</b> {$match->player1?->name} & {$match->player1Partner?->name}\n";
            $text .= "👥 <b>Đội 2:</b> {$match->player2?->name} & {$match->player2Partner?->name}\n";
            $text .= "🏆 <b>Khai báo Thắng:</b> {$match->winner?->name} & {$match->winnerPartner?->name}\n";
        } else {
            $text .= "⚔️ <b>Trận đấu:</b> {$match->player1?->name} vs {$match->player2?->name}\n";
            $text .= "🏆 <b>Khai báo Thắng:</b> {$winner?->name}\n";
            $text .= "💀 <b>Khai báo Thua:</b> {$loser?->name}\n";
        }

        $text .= "🎁 <b>Kèo/Phần thưởng:</b> <b>{$match->prize}</b>\n";

        if ($match->notes) {
            $text .= "📝 <b>Ghi chú:</b> {$match->notes}\n";
        }

        $text .= "\n⏱️ <b>Hạn chót phản hồi:</b> <i>{$deadline}</i>\n";
        $text .= "<i>(Nếu không phản hồi sau 24h, hệ thống sẽ tự động xác nhận kết quả trên!)</i>\n";

        $replyMarkup = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Chấp nhận kết quả', 'callback_data' => "confirm_{$match->id}"],
                    ['text' => '❌ Khiếu nại kết quả', 'callback_data' => "dispute_{$match->id}"],
                ],
                [
                    ['text' => '🌐 Xem chi tiết trên Website', 'url' => $siteUrl],
                ],
            ],
        ];

        return $this->sendMessage($recipient->telegram_id, $text, $replyMarkup);
    }

    public function sendMatchStatusUpdate(BilliardMatch $match, string $message): void
    {
        foreach ([$match->player1, $match->player2] as $player) {
            if ($player && $player->telegram_id) {
                $siteUrl = url("/matches/{$match->id}");
                $text = "🎱 <b>CẬP NHẬT TRẬN ĐẤU (#{$match->id})</b>\n\n";
                $text .= "⚔️ {$match->player1->name} vs {$match->player2->name}\n";
                $text .= "🎁 Kèo: {$match->prize}\n";
                $text .= "📢 {$message}\n\n";
                $text .= "<a href=\"{$siteUrl}\">👉 Xem chi tiết trên website</a>";

                $this->sendMessage($player->telegram_id, $text);
            }
        }
    }

    /**
     * Handle Telegram incoming Webhook updates.
     */
    public function handleWebhook(array $update, BilliardMatchService $matchService, BilliardStatsService $statsService): void
    {
        // 1. Handle Callback Query (Inline buttons)
        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query'], $matchService);

            return;
        }

        // 2. Handle Message
        if (isset($update['message'])) {
            $this->handleMessage($update['message'], $matchService, $statsService);
        }
    }

    protected function handleCallbackQuery(array $callbackQuery, BilliardMatchService $matchService): void
    {
        $callbackId = $callbackQuery['id'];
        $fromId = $callbackQuery['from']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $message = $callbackQuery['message'] ?? [];
        $chatId = $message['chat']['id'] ?? $fromId;

        $user = User::where('telegram_id', (string) $fromId)->first();

        if (! $user) {
            $this->answerCallbackQuery($callbackId, '⚠️ Bạn chưa liên kết tài khoản website!');

            return;
        }

        if (str_starts_with($data, 'confirm_')) {
            $matchId = (int) substr($data, 8);
            $match = BilliardMatch::find($matchId);

            if (! $match) {
                $this->answerCallbackQuery($callbackId, '❌ Trận đấu không tồn tại!');

                return;
            }

            if ($match->status !== 'pending') {
                $this->answerCallbackQuery($callbackId, "Trận đấu đã ở trạng thái: {$match->status}");

                return;
            }

            if (! $match->isWaitingForUser($user) && ! $user->isAdmin()) {
                $this->answerCallbackQuery($callbackId, '⚠️ Chỉ đối thủ mới có quyền xác nhận kết quả này!');

                return;
            }

            $matchService->confirmMatch($match, $user, 'opponent');
            $this->answerCallbackQuery($callbackId, '✅ Bạn đã xác nhận kết quả thành công!');
            $this->sendMessage($chatId, "✅ <b>Trận đấu #{$match->id} đã được xác nhận chính thức!</b>\n🏆 Người thắng: {$match->winner->name}\n🎁 Kèo: {$match->prize}");

            return;
        }

        if (str_starts_with($data, 'dispute_')) {
            $matchId = (int) substr($data, 9);
            $match = BilliardMatch::find($matchId);

            if (! $match) {
                $this->answerCallbackQuery($callbackId, '❌ Trận đấu không tồn tại!');

                return;
            }

            $siteUrl = url("/matches/{$match->id}");
            $this->answerCallbackQuery($callbackId, 'Vui lòng cung cấp lý do khiếu nại!');
            $this->sendMessage($chatId, "⚠️ <b>Để khiếu nại trận đấu #{$match->id}:</b>\nKhiếu nại bắt buộc phải có lý do cụ thể và bằng chứng.\n👉 Hãy nhấn vào liên kết sau để gửi khiếu nại và tải ảnh bằng chứng:\n<a href=\"{$siteUrl}\">Gửi khiếu nại trận đấu #{$match->id}</a>");

            return;
        }

        // Quick Winner Selection Callback for Telegram match creation
        if (str_starts_with($data, 'quick_win_')) {
            // format: quick_win_{player1_id}_{player2_id}_{winner_id}_{prize_code}
            $parts = explode('_', substr($data, 10));
            if (count($parts) >= 3) {
                $p1Id = (int) $parts[0];
                $p2Id = (int) $parts[1];
                $winnerId = (int) $parts[2];
                $loserId = ($winnerId === $p1Id) ? $p2Id : $p1Id;

                $p1 = User::find($p1Id);
                $p2 = User::find($p2Id);
                $winner = User::find($winnerId);

                if ($p1 && $p2 && $winner) {
                    $defaultPrize = 'Kèo cà phê muối';
                    $match = $matchService->createMatch([
                        'player1_id' => $p1Id,
                        'player2_id' => $p2Id,
                        'winner_id' => $winnerId,
                        'loser_id' => $loserId,
                        'played_at' => now(),
                        'prize' => $defaultPrize,
                        'notes' => 'Tạo nhanh qua Telegram Bot',
                        'created_from' => 'telegram',
                    ], $user);

                    $this->answerCallbackQuery($callbackId, '✅ Trận đấu đã được tạo!');
                    $this->sendMessage($chatId, "🎉 <b>GHI NHẬN TRẬN ĐẤU THÀNH CÔNG!</b>\n⚔️ {$p1->name} vs {$p2->name}\n🏆 Khai báo thắng: <b>{$winner->name}</b>\n🎁 Kèo: {$defaultPrize}\n\n⏱️ Đã gửi thông báo cho đối thủ xác nhận trong vòng 24 giờ.");

                    return;
                }
            }
        }

        // Quick Doubles Winner Selection Callback
        if (str_starts_with($data, 'quick_doubles_')) {
            // format: quick_doubles_{p1}_{p1p}_{p2}_{p2p}_{winning_team}
            $parts = explode('_', substr($data, 14));
            if (count($parts) >= 5) {
                $p1Id = (int) $parts[0];
                $p1PartnerId = (int) $parts[1];
                $p2Id = (int) $parts[2];
                $p2PartnerId = (int) $parts[3];
                $winningTeam = (int) $parts[4];

                $p1 = User::find($p1Id);
                $p1Partner = User::find($p1PartnerId);
                $p2 = User::find($p2Id);
                $p2Partner = User::find($p2PartnerId);

                if ($p1 && $p1Partner && $p2 && $p2Partner) {
                    $winnerId = ($winningTeam === 1) ? $p1Id : $p2Id;
                    $winnerPartnerId = ($winningTeam === 1) ? $p1PartnerId : $p2PartnerId;
                    $loserId = ($winningTeam === 1) ? $p2Id : $p1Id;
                    $loserPartnerId = ($winningTeam === 1) ? $p2PartnerId : $p1PartnerId;

                    $defaultPrize = 'Kèo 4 ly cà phê muối';
                    $match = $matchService->createMatch([
                        'match_type' => 'doubles',
                        'player1_id' => $p1Id,
                        'player1_partner_id' => $p1PartnerId,
                        'player2_id' => $p2Id,
                        'player2_partner_id' => $p2PartnerId,
                        'winner_id' => $winnerId,
                        'winner_partner_id' => $winnerPartnerId,
                        'loser_id' => $loserId,
                        'loser_partner_id' => $loserPartnerId,
                        'played_at' => now(),
                        'prize' => $defaultPrize,
                        'notes' => 'Tạo trận đôi 2vs2 qua Telegram Bot',
                        'created_from' => 'telegram',
                    ], $user);

                    $winnerNames = ($winningTeam === 1) ? "{$p1->name} & {$p1Partner->name}" : "{$p2->name} & {$p2Partner->name}";
                    $this->answerCallbackQuery($callbackId, '✅ Trận đánh đôi đã được tạo!');
                    $this->sendMessage($chatId, "🎉 <b>GHI NHẬN TRẬN ĐÁNH ĐÔI THÀNH CÔNG!</b>\n👥 <b>Đội 1:</b> {$p1->name} & {$p1Partner->name}\n👥 <b>Đội 2:</b> {$p2->name} & {$p2Partner->name}\n🏆 <b>Khai báo thắng:</b> <b>{$winnerNames}</b>\n🎁 <b>Kèo:</b> {$defaultPrize}\n\n⏱️ Đã gửi thông báo cho đội đối thủ xác nhận trong vòng 24 giờ.");

                    return;
                }
            }
        }
    }

    protected function handleMessage(array $msg, BilliardMatchService $matchService, BilliardStatsService $statsService): void
    {
        $chatId = $msg['chat']['id'];
        $text = trim($msg['text'] ?? '');
        $fromId = (string) ($msg['from']['id'] ?? '');
        $telegramUsername = $msg['from']['username'] ?? null;

        if (empty($text)) {
            return;
        }

        $tokens = preg_split('/\s+/', $text, 2);
        $firstToken = $tokens[0] ?? '';
        $rawCommand = strtolower(explode('@', $firstToken)[0]);

        // 1. Command /start
        if ($rawCommand === '/start') {
            $parts = explode(' ', $text);
            if (count($parts) > 1) {
                $code = trim($parts[1]);
                $user = User::where('telegram_link_code', $code)->first();
                if ($user) {
                    $user->update([
                        'telegram_id' => $fromId,
                        'telegram_username' => $telegramUsername,
                        'telegram_link_code' => null, // reset once linked
                    ]);
                    $this->sendMessage($chatId, "🎉 <b>LIÊN KẾT THÀNH CÔNG!</b>\nXin chào <b>{$user->name}</b> (@{$user->username})!\nTài khoản của bạn đã được kết nối với Telegram Bot Bida Nội Bộ.\nTừ bây giờ bạn sẽ nhận được thông báo trận đấu tức thời và có thể tạo trận đấu nhanh chóng!");

                    return;
                }
            }

            $user = User::where('telegram_id', $fromId)->first();
            $greeting = $user ? "Xin chào <b>{$user->name}</b>!" : 'Chào bạn!';
            $this->sendMessage($chatId, "🎱 <b>CHÀO MỪNG ĐẾN VỚI HỆ THỐNG BIDA NỘI BỘ</b>\n\n{$greeting}\n\nCác lệnh hữu ích:\n/help - Hướng dẫn sử dụng\n/stats - Xem thành tích của bạn\n/rank - Xem bảng xếp hạng\n/pending - Xem các trận đang chờ bạn xác nhận\n/history - Lịch sử đấu gần đây\n/duel [username] - Xem đối đầu với thành viên khác\n\n⚡ <b>Cú pháp tạo trận nhanh:</b> <code>/user1 user2</code> (VD: <code>/an binh</code>)");

            return;
        }

        // 2. Command /login [username] [password]
        if ($rawCommand === '/login') {
            $parts = preg_split('/\s+/', $text);
            if (count($parts) < 3) {
                $this->sendMessage(
                    $chatId,
                    "🔑 <b>ĐĂNG NHẬP TÀI KHOẢN TRÊN TELEGRAM</b>\n\n"
                    ."Vui lòng gửi tin nhắn theo cú pháp:\n"
                    ."<code>/login [username] [mật_khẩu]</code>\n\n"
                    ."<i>Ví dụ:</i> <code>/login admin password</code> hoặc <code>/login an password</code>\n\n"
                    .'<i>(Sau khi đăng nhập thành công, bạn có thể xóa tin nhắn chứa mật khẩu để bảo mật)</i>'
                );

                return;
            }

            $inputLogin = trim($parts[1]);
            $inputPassword = trim($parts[2]);

            $field = filter_var($inputLogin, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
            $targetUser = User::where($field, strtolower($inputLogin))->first();

            if (! $targetUser || ! Hash::check($inputPassword, $targetUser->password)) {
                $this->sendMessage($chatId, '❌ <b>Đăng nhập thất bại:</b> Tên đăng nhập hoặc mật khẩu không chính xác!');

                return;
            }

            if (! $targetUser->is_active) {
                $this->sendMessage($chatId, '⛔ <b>Tài khoản đã bị khóa:</b> Vui lòng liên hệ Quản trị viên để mở lại!');

                return;
            }

            // Unlink any other user currently linked to this Telegram ID (if any)
            User::where('telegram_id', $fromId)->where('id', '!=', $targetUser->id)->update([
                'telegram_id' => null,
                'telegram_username' => null,
            ]);

            // Link this Telegram account to targetUser
            $targetUser->update([
                'telegram_id' => $fromId,
                'telegram_username' => $telegramUsername,
            ]);

            $this->sendMessage(
                $chatId,
                "🎉 <b>ĐĂNG NHẬP THÀNH CÔNG!</b>\n\n"
                ."Xin chào <b>{$targetUser->name}</b> (@{$targetUser->username})!\n"
                ."Tài khoản Telegram của bạn đã được kết nối với hệ thống Bida Nội Bộ.\n\n"
                ."⚡ <i>Mẹo:</i> Bạn có thể xóa tin nhắn vừa gửi để bảo mật mật khẩu của bạn.\n\n"
                ."Bây giờ bạn có thể dùng các lệnh:\n"
                ."• <code>/stats</code> : Xem thành tích của bạn\n"
                ."• <code>/an binh</code> : Tạo trận đơn\n"
                ."• <code>/an binh vs cuong dung</code> : Tạo trận đôi\n"
                ."• <code>/logout</code> : Đăng xuất tài khoản\n"
                .'• <code>/help</code> : Xem hướng dẫn đầy đủ'
            );

            return;
        }

        // 3. Command /logout
        if ($rawCommand === '/logout') {
            $user = User::where('telegram_id', $fromId)->first();
            if ($user) {
                $user->update([
                    'telegram_id' => null,
                    'telegram_username' => null,
                ]);
                $this->sendMessage($chatId, "✅ <b>Đã đăng xuất!</b>\nTài khoản Telegram của bạn đã được hủy liên kết với tài khoản {$user->name} (@{$user->username}).\nGõ <code>/login username password</code> để đăng nhập lại bất kỳ lúc nào.");
            } else {
                $this->sendMessage($chatId, 'Bạn chưa đăng nhập tài khoản nào trên Telegram.');
            }

            return;
        }

        // Find user linked with this Telegram account
        $user = User::where('telegram_id', $fromId)->first();

        // 4. Command /password [new_password]
        if ($rawCommand === '/password') {
            if (! $user) {
                $this->sendMessage($chatId, '⚠️ Bạn cần đăng nhập tài khoản trước bằng lệnh: <code>/login username password</code>');

                return;
            }

            $parts = preg_split('/\s+/', $text);
            if (count($parts) < 2) {
                $this->sendMessage(
                    $chatId,
                    "🔐 <b>ĐỔI MẬT KHẨU TÀI KHOẢN</b>\n\n"
                    ."Cú pháp:\n"
                    ."<code>/password [mật_khẩu_mới]</code>\n\n"
                    ."<i>Ví dụ:</i> <code>/password 123456</code>\n\n"
                    .'<i>(Mật khẩu cần tối thiểu 6 ký tự. Hãy xóa tin nhắn sau khi gửi để bảo mật)</i>'
                );

                return;
            }

            $newPass = trim($parts[1]);
            if (strlen($newPass) < 6) {
                $this->sendMessage($chatId, '❌ Mật khẩu mới phải có tối thiểu 6 ký tự!');

                return;
            }

            $user->update([
                'password' => Hash::make($newPass),
            ]);

            $this->sendMessage(
                $chatId,
                "✅ <b>ĐỔI MẬT KHẨU THÀNH CÔNG!</b>\n\n"
                ."Mật khẩu mới cho tài khoản <b>{$user->name}</b> (@{$user->username}) đã được cập nhật.\n"
                ."Bạn có thể dùng mật khẩu này để đăng nhập trên Website hoặc Telegram.\n\n"
                .'⚡ <i>Khuyến nghị:</i> Hãy xóa tin nhắn vừa gửi để bảo mật tài khoản!'
            );

            return;
        }

        // 5. Command /help
        if ($rawCommand === '/help') {
            $helpText = "🎱 <b>HƯỚNG DẪN SỬ DỤNG BOT BIDA NỘI BỘ</b>\n\n";
            $helpText .= "🔑 <b>Đăng nhập / Đổi mật khẩu:</b>\n• <code>/login username password</code>\n• <code>/password [mật_khẩu_mới]</code>\n• <code>/logout</code>\n\n";
            $helpText .= "1️⃣ <b>Tạo trận ĐƠN (1vs1):</b>\n<code>/an binh</code>\nBot sẽ hiển thị nút chọn ai thắng để bạn bấm chọn!\n\n";
            $helpText .= "2️⃣ <b>Tạo trận ĐỔI (2vs2):</b>\n<code>/an binh vs cuong dung</code>\nBot hiển thị nút chọn Đội 1 Thắng hoặc Đội 2 Thắng!\n\n";
            $helpText .= "3️⃣ <b>Xác nhận kết quả:</b>\nĐối thủ nhận thông báo kèm nút [✅ Chấp nhận] và [❌ Khiếu nại] (Hạn chốt 24h).\n\n";
            $helpText .= "4️⃣ <b>Các lệnh tra cứu:</b>\n";
            $helpText .= "• <code>/stats</code> : Thống kê cá nhân\n";
            $helpText .= "• <code>/rank</code> : Bảng xếp hạng Top 5 và Loser tháng\n";
            $helpText .= "• <code>/history</code> : 5 trận gần nhất\n";
            $helpText .= "• <code>/pending</code> : Trận đang chờ xác nhận\n";
            $helpText .= "• <code>/duel an</code> : Lịch sử đối đầu với An\n";

            $this->sendMessage($chatId, $helpText);

            return;
        }

        if (! $user) {
            $this->sendMessage(
                $chatId,
                "⚠️ <b>Bạn chưa đăng nhập tài khoản website trên Telegram!</b>\n\n"
                ."👉 <b>Cách 1:</b> Gõ lệnh đăng nhập trực tiếp:\n"
                ."<code>/login username password</code> (VD: <code>/login admin password</code>)\n\n"
                .'👉 <b>Cách 2:</b> Vào website lấy mã cá nhân và gửi: <code>/start [mã]</code>'
            );

            return;
        }

        // 5. Command /stats
        if ($rawCommand === '/stats') {
            $stats = $statsService->getPlayerStats($user);
            $streakText = $stats['current_streak_type']
                ? ($stats['current_streak_type'] === 'win' ? "🔥 Chuỗi thắng {$stats['current_streak_count']} trận" : "❄️ Chuỗi thua {$stats['current_streak_count']} trận")
                : 'Chưa có chuỗi';

            $out = "📊 <b>THỐNG KÊ CỦA {$user->name}</b> (@{$user->username})\n\n";
            $out .= "🎱 Tổng số trận: <b>{$stats['total_matches']}</b>\n";
            $out .= "🏆 Số trận thắng: <b>{$stats['wins']}</b>\n";
            $out .= "💀 Số trận thua: <b>{$stats['losses']}</b>\n";
            $out .= "📈 Tỷ lệ thắng: <b>{$stats['win_rate']}%</b>\n";
            $out .= "⚡ Phong độ hiện tại: {$streakText}\n";
            $out .= "⭐ Chuỗi thắng dài nhất: <b>{$stats['longest_win_streak']}</b>\n";

            if (! empty($stats['badges'])) {
                $badgeNames = array_column($stats['badges'], 'name');
                $out .= "\n🎖️ <b>Danh hiệu vui:</b> ".implode(', ', $badgeNames);
            }

            $this->sendMessage($chatId, $out);

            return;
        }

        // 6. Command /rank
        if ($rawCommand === '/rank') {
            $leaderboard = $statsService->getLeaderboard('all')->take(5);
            $out = "🏆 <b>BẢNG XẾP HẠNG TOP 5 CƠ THỦ</b>\n\n";
            $medals = ['🥇', '🥈', '🥉', '4️⃣', '5️⃣'];

            foreach ($leaderboard as $idx => $item) {
                $medal = $medals[$idx] ?? '▫️';
                $out .= "{$medal} <b>{$item['user']->name}</b> (@{$item['user']->username})\n";
                $out .= "   Thắng: {$item['wins']}/{$item['total_matches']} ({$item['win_rate']}%)";
                if ($item['current_streak_type'] === 'win' && $item['current_streak_count'] >= 2) {
                    $out .= " | 🔥 W{$item['current_streak_count']}";
                }
                $out .= "\n";
            }

            $loser = $statsService->getLoserOfTheMonth();
            if ($loser) {
                $out .= "\n🤡 <b>LOSER CỦA THÁNG:</b> {$loser['user']->name} ({$loser['losses']} trận thua)\n<i>\"{$loser['humorous_title']}\"</i>";
            }

            $this->sendMessage($chatId, $out);

            return;
        }

        // 7. Command /pending
        if ($rawCommand === '/pending') {
            $pendingMatches = BilliardMatch::where('status', 'pending')
                ->where(function ($q) use ($user) {
                    $q->where('player1_id', $user->id)
                        ->orWhere('player2_id', $user->id);
                })
                ->with(['creator', 'player1', 'player2', 'winner'])
                ->get();

            if ($pendingMatches->isEmpty()) {
                $this->sendMessage($chatId, '✅ Bạn không có trận đấu nào đang chờ xác nhận!');

                return;
            }

            $out = "⏳ <b>CÁC TRẬN ĐANG CHỜ XÁC NHẬN:</b>\n\n";
            foreach ($pendingMatches as $m) {
                $isWaiting = $m->isWaitingForUser($user);
                $remaining = $m->getRemainingSecondsForAutoConfirm();
                $hours = floor($remaining / 3600);
                $mins = floor(($remaining % 3600) / 60);

                $out .= "• <b>#{$m->id}:</b> {$m->player1->name} vs {$m->player2->name}\n";
                $out .= "  🏆 Khai báo thắng: {$m->winner->name} | 🎁 Kèo: {$m->prize}\n";
                $out .= "  ⏱️ Còn {$hours}h {$mins}m trước khi tự động xác nhận.\n";

                if ($isWaiting) {
                    $out .= "  👉 Bạn cần xác nhận trận này!\n";
                }
                $out .= "\n";
            }

            $this->sendMessage($chatId, $out);

            return;
        }

        // 8. Command /history
        if ($rawCommand === '/history') {
            $matches = $user->allMatches()
                ->with(['player1', 'player2', 'winner', 'loser'])
                ->orderBy('played_at', 'desc')
                ->take(5)
                ->get();

            if ($matches->isEmpty()) {
                $this->sendMessage($chatId, 'Bạn chưa tham gia trận đấu nào!');

                return;
            }

            $out = "📜 <b>5 TRẬN ĐẤU GẦN NHẤT CỦA BẠN:</b>\n\n";
            foreach ($matches as $m) {
                $isWin = ($m->winner_id === $user->id);
                $icon = $isWin ? '🟢 THẮNG' : '🔴 THUA';
                $statusTag = strtoupper($m->status);
                $out .= "{$icon} [{$statusTag}] ngày {$m->played_at->format('d/m')}\n";
                $out .= "⚔️ {$m->player1->name} vs {$m->player2->name}\n";
                $out .= "🎁 Kèo: {$m->prize}\n\n";
            }

            $this->sendMessage($chatId, $out);

            return;
        }

        // 9. Command /duel username
        if ($rawCommand === '/duel') {
            $parts = explode(' ', $text);
            if (count($parts) < 2) {
                $this->sendMessage($chatId, 'Cú pháp: <code>/duel [username]</code> (Ví dụ: <code>/duel an</code>)');

                return;
            }
            $targetUsername = ltrim(trim($parts[1]), '@');
            $target = User::where('username', $targetUsername)->first();

            if (! $target) {
                $this->sendMessage($chatId, "❌ Không tìm thấy thành viên có username '{$targetUsername}'!");

                return;
            }

            $h2h = $statsService->getHeadToHead($user, $target);
            $out = "⚔️ <b>LỊCH SỬ ĐỐI ĐẦU: {$user->name} vs {$target->name}</b>\n\n";
            $out .= "🎱 Tổng số trận đã đấu: <b>{$h2h['total_matches']}</b>\n";
            $out .= "🏆 <b>{$user->name}:</b> {$h2h['user1_wins']} trận thắng ({$h2h['user1_win_rate']}%)\n";
            $out .= "🏆 <b>{$target->name}:</b> {$h2h['user2_wins']} trận thắng ({$h2h['user2_win_rate']}%)\n";

            $this->sendMessage($chatId, $out);

            return;
        }

        // 8. Match creation via:
        // - Đơn (1vs1): `/an binh` hoặc `/vs binh` hoặc `/tran an binh`
        // - Đôi (2vs2): `/an binh vs cuong dung` hoặc `/doi an binh cuong dung`
        if (str_starts_with($text, '/')) {
            $clean = ltrim($text, '/');

            // Strip common command words if prefixed
            foreach (['tran ', 'match ', 'game ', 'tao ', 'play ', 'doi ', '2v2 '] as $prefix) {
                if (str_starts_with(strtolower($clean), $prefix)) {
                    $clean = trim(substr($clean, strlen($prefix)));
                    break;
                }
            }

            $rawParts = preg_split('/\s+/', $clean);
            $noiseWords = ['vs', 'va', 'voi', '&', 'và', 'với'];
            $parts = array_values(array_filter($rawParts, fn ($p) => ! in_array(strtolower($p), $noiseWords) && $p !== ''));

            // If user typed 1 username (e.g. /vs binh or /binh), infer player 1 is the currently authenticated user
            if (count($parts) === 1 && strtolower($parts[0]) !== 'help') {
                $parts = [$user->username, $parts[0]];
            }

            if (count($parts) === 2) {
                $user1 = $this->findUserByUsername($parts[0]);
                $user2 = $this->findUserByUsername($parts[1]);

                if (! $user1 || ! $user2) {
                    $notFound = [];
                    if (! $user1) {
                        $notFound[] = '@'.$parts[0];
                    }
                    if (! $user2) {
                        $notFound[] = '@'.$parts[1];
                    }

                    $availableList = User::where('is_active', true)->pluck('username')->take(10)->implode(', ');
                    $hint = $availableList ? "\n📋 <b>Cơ thủ hiện có:</b> <code>{$availableList}</code>" : '';

                    $this->sendMessage(
                        $chatId,
                        '❌ <b>Không tìm thấy cơ thủ:</b> '.implode(', ', $notFound)."\n"
                        .$hint."\n\n"
                        .'💡 <i>Mẹo:</i> Hãy tạo tài khoản cho cơ thủ trên website trước (mục Quản trị > Thêm cơ thủ).'
                    );

                    return;
                }

                if ($user1->id === $user2->id) {
                    $this->sendMessage($chatId, '❌ Không thể tạo trận đấu với chính mình!');

                    return;
                }

                // Prompt with Inline Keyboard to pick winner!
                $kb = [
                    'inline_keyboard' => [
                        [
                            ['text' => "🏆 {$user1->name} Thắng", 'callback_data' => "quick_win_{$user1->id}_{$user2->id}_{$user1->id}_cafe"],
                            ['text' => "🏆 {$user2->name} Thắng", 'callback_data' => "quick_win_{$user1->id}_{$user2->id}_{$user2->id}_cafe"],
                        ],
                    ],
                ];

                $this->sendMessage(
                    $chatId,
                    "🎱 <b>TẠO TRẬN ĐẤU ĐƠN (1vs1)</b>\n\n"
                    ."⚔️ <b>Cơ thủ:</b> {$user1->name} vs {$user2->name}\n"
                    ."🎁 <b>Kèo mặc định:</b> Kèo cà phê muối\n\n"
                    .'<b>Ai là người chiến thắng?</b> Bấm chọn ngay bên dưới:',
                    $kb
                );

                return;
            }

            if (count($parts) === 4) {
                // Doubles 2vs2
                $user1 = $this->findUserByUsername($parts[0]);
                $user2 = $this->findUserByUsername($parts[1]);
                $user3 = $this->findUserByUsername($parts[2]);
                $user4 = $this->findUserByUsername($parts[3]);

                if (! $user1 || ! $user2 || ! $user3 || ! $user4) {
                    $notFound = [];
                    if (! $user1) {
                        $notFound[] = '@'.$parts[0];
                    }
                    if (! $user2) {
                        $notFound[] = '@'.$parts[1];
                    }
                    if (! $user3) {
                        $notFound[] = '@'.$parts[2];
                    }
                    if (! $user4) {
                        $notFound[] = '@'.$parts[3];
                    }

                    $availableList = User::where('is_active', true)->pluck('username')->take(10)->implode(', ');
                    $hint = $availableList ? "\n📋 <b>Cơ thủ hiện có:</b> <code>{$availableList}</code>" : '';

                    $this->sendMessage(
                        $chatId,
                        '❌ <b>Không tìm thấy các cơ thủ:</b> '.implode(', ', $notFound)."\n"
                        .$hint."\n\n"
                        .'💡 <i>Mẹo:</i> Hãy tạo tài khoản cho cơ thủ trên website trước.'
                    );

                    return;
                }

                $ids = [$user1->id, $user2->id, $user3->id, $user4->id];
                if (count($ids) !== count(array_unique($ids))) {
                    $this->sendMessage($chatId, '❌ 4 cơ thủ trong trận đánh đôi phải khác nhau hoàn toàn!');

                    return;
                }

                $kb = [
                    'inline_keyboard' => [
                        [
                            ['text' => "🏆 Đội 1 ({$user1->name} & {$user2->name}) Thắng", 'callback_data' => "quick_doubles_{$user1->id}_{$user2->id}_{$user3->id}_{$user4->id}_1"],
                        ],
                        [
                            ['text' => "🏆 Đội 2 ({$user3->name} & {$user4->name}) Thắng", 'callback_data' => "quick_doubles_{$user1->id}_{$user2->id}_{$user3->id}_{$user4->id}_2"],
                        ],
                    ],
                ];

                $this->sendMessage(
                    $chatId,
                    "🎱 <b>TẠO TRẬN ĐÁNH ĐÔI (2vs2)</b>\n\n"
                    ."👥 <b>Đội 1:</b> {$user1->name} & {$user2->name}\n"
                    ."👥 <b>Đội 2:</b> {$user3->name} & {$user4->name}\n"
                    ."🎁 <b>Kèo mặc định:</b> Kèo 4 ly cà phê muối\n\n"
                    .'<b>Đội nào chiến thắng?</b> Bấm chọn ngay bên dưới:',
                    $kb
                );

                return;
            }

            // Unrecognized slash command
            $this->sendMessage(
                $chatId,
                "⚠️ <b>Cú pháp chưa chính xác!</b>\n\n"
                ."👉 <b>Tạo trận ĐƠN:</b> <code>/user1 user2</code> (VD: <code>/admin binh</code>)\n"
                ."👉 <b>Tạo nhanh với bạn:</b> <code>/vs [đối_thủ]</code> (VD: <code>/vs binh</code>)\n"
                ."👉 <b>Tạo trận ĐÔI:</b> <code>/u1 u2 vs u3 u4</code> (VD: <code>/an binh vs cuong dung</code>)\n\n"
                .'Gõ <code>/help</code> để xem hướng dẫn chi tiết.'
            );
        }
    }

    protected function answerCallbackQuery(string $callbackQueryId, string $text): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            $this->client()->post("https://api.telegram.org/bot{$this->token}/answerCallbackQuery", [
                'callback_query_id' => $callbackQueryId,
                'text' => $text,
                'show_alert' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('Telegram answerCallbackQuery error: '.$e->getMessage());
        }
    }

    protected function findUserByUsername(string $raw): ?User
    {
        $clean = ltrim(trim($raw), '@');
        $clean = explode('@', $clean)[0];

        if (empty($clean)) {
            return null;
        }

        return User::whereRaw('LOWER(username) = ?', [strtolower($clean)])->first();
    }

    public function setWebhook(string $url): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot token chưa được cấu hình!'];
        }

        $res = $this->client()->post("https://api.telegram.org/bot{$this->token}/setWebhook", [
            'url' => $url,
        ]);

        // Also register bot commands automatically
        $this->setMyCommands();

        return $res->json() ?: ['ok' => false];
    }

    public function setMyCommands(?array $commands = null): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot token chưa được cấu hình!'];
        }

        $commands = $commands ?: [
            ['command' => 'start', 'description' => 'Khởi động và liên kết tài khoản'],
            ['command' => 'login', 'description' => 'Đăng nhập tài khoản cá nhân'],
            ['command' => 'password', 'description' => 'Đổi mật khẩu tài khoản cá nhân'],
            ['command' => 'logout', 'description' => 'Đăng xuất khỏi Telegram Bot'],
            ['command' => 'help', 'description' => 'Hướng dẫn sử dụng và cú pháp ghi trận'],
            ['command' => 'vs', 'description' => 'Tạo trận nhanh với bạn (VD: /vs binh)'],
            ['command' => 'stats', 'description' => 'Xem thống kê cá nhân và tỷ lệ thắng'],
            ['command' => 'rank', 'description' => 'Bảng xếp hạng Top cơ thủ và Loser'],
            ['command' => 'pending', 'description' => 'Các trận đấu đang chờ bạn xác nhận'],
            ['command' => 'history', 'description' => 'Xem 5 trận đấu gần đây nhất'],
            ['command' => 'duel', 'description' => 'Xem lịch sử đối đầu (VD: /duel binh)'],
        ];

        $res = $this->client()->post("https://api.telegram.org/bot{$this->token}/setMyCommands", [
            'commands' => $commands,
        ]);

        return $res->json() ?: ['ok' => false];
    }

    public function deleteWebhook(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot token chưa được cấu hình!'];
        }

        $res = $this->client()->post("https://api.telegram.org/bot{$this->token}/deleteWebhook");

        return $res->json() ?: ['ok' => false];
    }

    public function getWebhookInfo(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'description' => 'Bot token chưa được cấu hình!'];
        }

        $res = $this->client()->get("https://api.telegram.org/bot{$this->token}/getWebhookInfo");

        return $res->json() ?: ['ok' => false];
    }

    public function getUpdates(int $offset = 0, int $timeout = 20): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $res = $this->client()->get("https://api.telegram.org/bot{$this->token}/getUpdates", [
                'offset' => $offset,
                'timeout' => $timeout,
            ]);

            return $res->json()['result'] ?? [];
        } catch (\Throwable $e) {
            Log::error('Telegram getUpdates error: '.$e->getMessage());

            return [];
        }
    }
}
