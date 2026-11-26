<?php

use App\Models\BilliardChallenge;
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

test('telegram bot designates doubles prize payers by typing order (viet truoc viet sau)', function () {
    $an = User::factory()->create(['username' => 'an', 'name' => 'An', 'telegram_id' => '1001']);
    $thanh = User::factory()->create(['username' => 'thanh', 'name' => 'Thanh', 'telegram_id' => '1002']);
    $tho = User::factory()->create(['username' => 'tho', 'name' => 'Thọ', 'telegram_id' => '1003']);
    $dat = User::factory()->create(['username' => 'dat', 'name' => 'Đạt', 'telegram_id' => '1004']);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // Case 1: Typing order An Thanh vs Thọ Đạt with custom prize
    // Đội 1: An (viết trước), Thanh (viết sau)
    // Đội 2: Thọ (viết trước), Đạt (viết sau)
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 1001],
            'from' => ['id' => 1001],
            'text' => '/an thanh vs tho dat 2 ly cà phê muối',
        ],
    ], $matchService, $statsService);

    // Đội 1 thắng
    $botService->handleWebhook([
        'callback_query' => [
            'id' => 'cb_order_1',
            'from' => ['id' => 1001],
            'message' => ['chat' => ['id' => 1001]],
            'data' => "quick_doubles_{$an->id}_{$thanh->id}_{$tho->id}_{$dat->id}_1",
        ],
    ], $matchService, $statsService);

    $match1 = BilliardMatch::where('match_type', 'doubles')->latest('id')->first();
    expect($match1)->not->toBeNull()
        ->and($match1->prize)->toBe('2 ly cà phê muối')
        ->and($match1->winner_id)->toBe($an->id) // Viết trước Đội 1
        ->and($match1->winner_partner_id)->toBe($thanh->id) // Viết sau Đội 1
        ->and($match1->loser_id)->toBe($tho->id) // Viết trước Đội 2
        ->and($match1->loser_partner_id)->toBe($dat->id) // Viết sau Đội 2
        ->and($match1->prize_status)->toBe('unpaid')
        ->and($match1->prize_partner_status)->toBe('unpaid');

    // Cross-split checks:
    // Cặp 1: Thọ (viết trước thua) trả cho An (viết trước thắng)
    expect($match1->getCounterpartForUser($tho)->id)->toBe($an->id);
    // Cặp 2: Đạt (viết sau thua) trả cho Thanh (viết sau thắng)
    expect($match1->getCounterpartForUser($dat)->id)->toBe($thanh->id);

    // Case 2: User changes typing order in Telegram: Đạt Thọ (Đạt trước, Thọ sau)
    // To designate that Đạt pays An, and Thọ pays Thanh!
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 1001],
            'from' => ['id' => 1001],
            'text' => '/an thanh vs dat tho 1 nồi lẩu',
        ],
    ], $matchService, $statsService);

    $botService->handleWebhook([
        'callback_query' => [
            'id' => 'cb_order_2',
            'from' => ['id' => 1001],
            'message' => ['chat' => ['id' => 1001]],
            'data' => "quick_doubles_{$an->id}_{$thanh->id}_{$dat->id}_{$tho->id}_1",
        ],
    ], $matchService, $statsService);

    $match2 = BilliardMatch::where('match_type', 'doubles')->latest('id')->first();
    expect($match2)->not->toBeNull()
        ->and($match2->prize)->toBe('1 nồi lẩu')
        ->and($match2->loser_id)->toBe($dat->id) // Đạt viết trước
        ->and($match2->loser_partner_id)->toBe($tho->id); // Thọ viết sau

    // Now Đạt pays An, Thọ pays Thanh!
    expect($match2->getCounterpartForUser($dat)->id)->toBe($an->id);
    expect($match2->getCounterpartForUser($tho)->id)->toBe($thanh->id);
});

test('telegram bot allows issuing doubles duel challenge via /duel command', function () {
    $an = User::factory()->create(['username' => 'an', 'name' => 'An', 'telegram_id' => '2001']);
    $thanh = User::factory()->create(['username' => 'thanh', 'name' => 'Thanh', 'telegram_id' => '2002']);
    $tho = User::factory()->create(['username' => 'tho', 'name' => 'Thọ', 'telegram_id' => '2003']);
    $dat = User::factory()->create(['username' => 'dat', 'name' => 'Đạt', 'telegram_id' => '2004']);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // An issues doubles challenge: partner Thanh vs Tho Dat
    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 2001],
            'from' => ['id' => 2001],
            'text' => '/duel thanh vs tho dat 1 ly trà đào',
        ],
    ], $matchService, $statsService);

    $challenge = BilliardChallenge::where('challenge_type', 'doubles')->latest('id')->first();
    expect($challenge)->not->toBeNull()
        ->and($challenge->challenger_id)->toBe($an->id)
        ->and($challenge->challenger_partner_id)->toBe($thanh->id)
        ->and($challenge->opponent_id)->toBe($tho->id)
        ->and($challenge->opponent_partner_id)->toBe($dat->id)
        ->and($challenge->prize)->toBe('1 ly trà đào');
});

test('telegram bot allows resolving accepted doubles challenge via resolve_challenge callback', function () {
    $an = User::factory()->create(['username' => 'an', 'name' => 'An', 'telegram_id' => '3001']);
    $thanh = User::factory()->create(['username' => 'thanh', 'name' => 'Thanh', 'telegram_id' => '3002']);
    $tho = User::factory()->create(['username' => 'tho', 'name' => 'Thọ', 'telegram_id' => '3003']);
    $dat = User::factory()->create(['username' => 'dat', 'name' => 'Đạt', 'telegram_id' => '3004']);

    $challenge = BilliardChallenge::create([
        'challenge_type' => 'doubles',
        'challenger_id' => $an->id,
        'challenger_partner_id' => $thanh->id,
        'opponent_id' => $tho->id,
        'opponent_partner_id' => $dat->id,
        'prize' => '2 ly sinh tố bơ',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    // An (Team 1) declares Team 1 won
    $botService->handleWebhook([
        'callback_query' => [
            'id' => 'cb_resolve_1',
            'from' => ['id' => 3001],
            'message' => ['chat' => ['id' => 3001]],
            'data' => "resolve_challenge_{$challenge->id}_1",
        ],
    ], $matchService, $statsService);

    $challenge->refresh();
    expect($challenge->status)->toBe('completed');
    expect($challenge->match_id)->not->toBeNull();

    $match = BilliardMatch::find($challenge->match_id);
    expect($match)->not->toBeNull()
        ->and($match->winner_id)->toBe($an->id)
        ->and($match->winner_partner_id)->toBe($thanh->id)
        ->and($match->loser_id)->toBe($tho->id)
        ->and($match->loser_partner_id)->toBe($dat->id)
        ->and($match->prize)->toBe('2 ly sinh tố bơ')
        ->and($match->status)->toBe('confirmed');
});

test('telegram bot displays upcoming challenges and resolve buttons via /upcoming command', function () {
    $an = User::factory()->create(['username' => 'an', 'name' => 'An', 'telegram_id' => '4001']);
    $tho = User::factory()->create(['username' => 'tho', 'name' => 'Thọ', 'telegram_id' => '4002']);

    $challenge = BilliardChallenge::create([
        'challenge_type' => 'singles',
        'challenger_id' => $an->id,
        'opponent_id' => $tho->id,
        'prize' => '1 bát phở bò',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'message' => [
            'chat' => ['id' => 4001],
            'from' => ['id' => 4001],
            'text' => '/upcoming',
        ],
    ], $matchService, $statsService);

    // Verify Telegram sent message with inline keyboard containing resolve_challenge buttons
    Http::assertSent(function ($request) use ($challenge) {
        $data = $request->data();
        $text = $data['text'] ?? '';
        $replyMarkup = json_decode($data['reply_markup'] ?? '{}', true);

        if (str_contains($text, '1 bát phở bò')) {
            $buttons = $replyMarkup['inline_keyboard'][0] ?? [];

            return ($buttons[0]['callback_data'] ?? '') === "resolve_challenge_{$challenge->id}_1";
        }

        return false;
    });
});
