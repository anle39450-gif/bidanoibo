<?php

use App\Models\BilliardMatch;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Http;

test('telegram bot handles link code via /start command', function () {
    $user = User::factory()->create([
        'username' => 'an',
        'telegram_link_code' => 'TESTCODE123',
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 999888],
            'from' => ['id' => 999888, 'username' => 'an_telegram'],
            'text' => '/start TESTCODE123',
        ],
    ], $matchService, $statsService);

    $user->refresh();
    expect($user->telegram_id)->toBe('999888');
    expect($user->telegram_username)->toBe('an_telegram');
    expect($user->telegram_link_code)->toBeNull();
});

test('telegram bot allows quick match confirmation callback', function () {
    $player1 = User::factory()->create(['username' => 'an']);
    $player2 = User::factory()->create([
        'username' => 'binh',
        'telegram_id' => '123456',
    ]);

    $match = BilliardMatch::factory()->create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'pending',
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'callback_query' => [
            'id' => 'cb_123',
            'from' => ['id' => 123456],
            'message' => ['chat' => ['id' => 123456]],
            'data' => "confirm_{$match->id}",
        ],
    ], $matchService, $statsService);

    $match->refresh();
    expect($match->status)->toBe('confirmed');
    expect($match->confirmed_by)->toBe('opponent');
});

test('telegram bot handles doubles match creation command and callback', function () {
    $u1 = User::factory()->create(['username' => 'an', 'telegram_id' => '111']);
    $u2 = User::factory()->create(['username' => 'binh']);
    $u3 = User::factory()->create(['username' => 'cuong']);
    $u4 = User::factory()->create(['username' => 'dung']);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // 1. Send command /an binh vs cuong dung
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 111],
            'from' => ['id' => 111],
            'text' => '/an binh vs cuong dung',
        ],
    ], $matchService, $statsService);

    // 2. Tap winning button for Team 1
    $botService->handleWebhook([
        'callback_query' => [
            'id' => 'cb_doubles_1',
            'from' => ['id' => 111],
            'message' => ['chat' => ['id' => 111]],
            'data' => "quick_doubles_{$u1->id}_{$u2->id}_{$u3->id}_{$u4->id}_1",
        ],
    ], $matchService, $statsService);

    $match = BilliardMatch::where('match_type', 'doubles')->first();
    expect($match)->not->toBeNull();
    expect($match->player1_id)->toBe($u1->id);
    expect($match->player1_partner_id)->toBe($u2->id);
    expect($match->player2_id)->toBe($u3->id);
    expect($match->player2_partner_id)->toBe($u4->id);
    expect($match->winner_id)->toBe($u1->id);
    expect($match->winner_partner_id)->toBe($u2->id);
    expect($match->loser_id)->toBe($u3->id);
    expect($match->loser_partner_id)->toBe($u4->id);
});

test('user getTelegramLinkCode ensures a code is always returned', function () {
    $user = User::factory()->create(['telegram_link_code' => null]);
    $code = $user->getTelegramLinkCode();

    expect($code)->not->toBeEmpty();
    expect(strlen($code))->toBe(10);
});

test('user can log in directly on telegram using /login command', function () {
    $user = User::factory()->create([
        'username' => 'anbida',
        'password' => bcrypt('secret999'),
        'telegram_id' => null,
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // Send /login anbida secret999
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 777888],
            'from' => ['id' => 777888, 'username' => 'an_tele'],
            'text' => '/login anbida secret999',
        ],
    ], $matchService, $statsService);

    $user->refresh();
    expect($user->telegram_id)->toBe('777888');
    expect($user->telegram_username)->toBe('an_tele');
});

test('user can log out on telegram using /logout command', function () {
    $user = User::factory()->create([
        'username' => 'binhbida',
        'telegram_id' => '555666',
        'telegram_username' => 'binh_tele',
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // Send /logout
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 555666],
            'from' => ['id' => 555666],
            'text' => '/logout',
        ],
    ], $matchService, $statsService);

    $user->refresh();
    expect($user->telegram_id)->toBeNull();
    expect($user->telegram_username)->toBeNull();
});

test('authenticated user can change password on telegram using /password command', function () {
    $user = User::factory()->create([
        'username' => 'cuongbida',
        'password' => Hash::make('oldpass123'),
        'telegram_id' => '999111',
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // Send /password newpass789
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 999111],
            'from' => ['id' => 999111],
            'text' => '/password newpass789',
        ],
    ], $matchService, $statsService);

    $user->refresh();
    expect(Hash::check('newpass789', $user->password))->toBeTrue();
});
