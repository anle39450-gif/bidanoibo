<?php

use App\Models\BilliardMatch;
use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TelegramBotConfig::truncate();
    TelegramBotConfig::create([
        'bot_token' => '123456:FAKE_TOKEN_FOR_TEST',
        'bot_username' => 'TestBilliardBot',
        'group_chat_id' => '-10099887766',
        'is_active' => true,
    ]);
});

test('telegram bot builds daily report text correctly with unpaid debts', function () {
    $player1 = User::factory()->create(['name' => 'Nguyễn Văn An', 'username' => 'an']);
    $player2 = User::factory()->create(['name' => 'Trần Văn Bình', 'username' => 'binh']);

    // Match 1: An beat Binh, prize unpaid
    BilliardMatch::factory()->create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'confirmed',
        'prize' => '1 ly cà phê muối',
        'prize_status' => 'unpaid',
        'played_at' => now(),
    ]);

    $statsService = app(BilliardStatsService::class);
    $botService = app(TelegramBotService::class);

    $reportText = $botService->buildDailyReportText($statsService);

    expect($reportText)->toContain('BÁO CÁO BIDA NỘI BỘ HẰNG NGÀY');
    expect($reportText)->toContain('DANH SÁCH NỢ KÈO CHƯA TRẢ');
    expect($reportText)->toContain('Trần Văn Bình');
    expect($reportText)->toContain('Nguyễn Văn An');
    expect($reportText)->toContain('1 ly cà phê muối');
});

test('telegram bot sends daily report to group chat id', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $statsService = app(BilliardStatsService::class);
    $botService = app(TelegramBotService::class);

    $result = $botService->sendDailyReport($statsService);

    expect($result['success'])->toBeTrue();
    expect($result['sent_count'])->toBe(1);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '-10099887766'
            && str_contains($request['text'], 'BÁO CÁO BIDA NỘI BỘ');
    });
});

test('telegram bot handles /id command and returns chat information', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => -10011223344, 'type' => 'supergroup', 'title' => 'CLB Bida Công Ty'],
            'from' => ['id' => 123456, 'username' => 'cuong_bida'],
            'text' => '/id',
        ],
    ], $matchService, $statsService);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === -10011223344
            && str_contains($request['text'], '-10011223344')
            && str_contains($request['text'], 'Group Chat ID');
    });
});

test('telegram bot handles /report command and sends daily summary', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => -10011223344, 'type' => 'supergroup', 'title' => 'CLB Bida Công Ty'],
            'from' => ['id' => 123456, 'username' => 'cuong_bida'],
            'text' => '/report',
        ],
    ], $matchService, $statsService);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === -10011223344
            && str_contains($request['text'], 'BÁO CÁO BIDA NỘI BỘ');
    });
});

test('artisan billiard:daily-report command triggers report sending', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $exitCode = Artisan::call('billiard:daily-report');

    expect($exitCode)->toBe(0);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '-10099887766';
    });
});

test('admin can trigger telegram daily report via web button', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $response = $this->actingAs($admin)->post('/admin/telegram/daily-report');

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '-10099887766';
    });
});

test('non-admin cannot trigger telegram daily report', function () {
    $member = User::factory()->create([
        'role' => 'member',
        'is_active' => true,
    ]);

    $response = $this->actingAs($member)->post('/admin/telegram/daily-report');

    $response->assertForbidden();
});

test('admin can update bot config including group_chat_id', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->post('/admin/bot/config', [
        'bot_token' => '999:UPDATED_TOKEN',
        'bot_username' => 'UpdatedBot',
        'group_chat_id' => '-10055443322',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $config = TelegramBotConfig::current();
    expect($config->group_chat_id)->toBe('-10055443322');
    expect($config->bot_username)->toBe('UpdatedBot');
});

test('bot auto-binds group_chat_id when added to a group via my_chat_member', function () {
    Http::fake([
        'https://api.telegram.org/*/createChatInviteLink' => Http::response(['ok' => true, 'result' => ['invite_link' => 'https://t.me/+joinGroup123']], 200),
        'https://api.telegram.org/*/exportChatInviteLink' => Http::response(['ok' => true, 'result' => 'https://t.me/+joinGroup123'], 200),
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $connectedUser = User::factory()->create([
        'telegram_id' => '888999',
        'is_active' => true,
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'my_chat_member' => [
            'chat' => [
                'id' => -10044556677,
                'title' => 'CLB Bida Cty',
                'type' => 'supergroup',
            ],
            'new_chat_member' => [
                'status' => 'member',
            ],
        ],
    ], $matchService, $statsService);

    $config = TelegramBotConfig::current();
    expect($config->group_chat_id)->toBe('-10044556677');

    // Check greeting sent to the group
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === -10044556677
            && str_contains($request['text'], 'KẾT NỐI NHÓM THÀNH CÔNG');
    });

    // Check invitation sent to connected member
    Http::assertSent(function ($request) use ($connectedUser) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $connectedUser->telegram_id
            && str_contains($request['text'], 'THAM GIA NHÓM CHAT BIDA NỘI BỘ');
    });
});

test('bot auto-binds group when /setgroup command is sent in group', function () {
    Http::fake([
        'https://api.telegram.org/*/createChatInviteLink' => Http::response(['ok' => true, 'result' => ['invite_link' => 'https://t.me/+joinGroup123']], 200),
        'https://api.telegram.org/*/exportChatInviteLink' => Http::response(['ok' => true, 'result' => 'https://t.me/+joinGroup123'], 200),
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => -10033221100, 'type' => 'supergroup', 'title' => 'Bida VIP'],
            'from' => ['id' => 123456],
            'text' => '/setgroup',
        ],
    ], $matchService, $statsService);

    $config = TelegramBotConfig::current();
    expect($config->group_chat_id)->toBe('-10033221100');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === -10033221100
            && str_contains($request['text'], 'ĐÃ GÁN NHÓM THÀNH CÔNG');
    });
});

test('admin can trigger invite all connected users from web', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $connectedUser = User::factory()->create([
        'telegram_id' => '777666',
        'is_active' => true,
    ]);

    Http::fake([
        'https://api.telegram.org/*/createChatInviteLink' => Http::response(['ok' => true, 'result' => ['invite_link' => 'https://t.me/+joinGroup123']], 200),
        'https://api.telegram.org/*/exportChatInviteLink' => Http::response(['ok' => true, 'result' => 'https://t.me/+joinGroup123'], 200),
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $response = $this->actingAs($admin)->post('/admin/telegram/invite-all');

    $response->assertRedirect();
    $response->assertSessionHas('success');

    Http::assertSent(function ($request) use ($connectedUser) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $connectedUser->telegram_id
            && str_contains($request['text'], 'THAM GIA NHÓM CHAT BIDA NỘI BỘ');
    });
});
