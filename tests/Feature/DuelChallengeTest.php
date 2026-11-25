<?php

use App\Livewire\DuelChallengeAlert;
use App\Models\BilliardChallenge;
use App\Models\BilliardMatch;
use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    TelegramBotConfig::truncate();
    TelegramBotConfig::create([
        'bot_token' => '123456:FAKE_TOKEN_FOR_TEST',
        'bot_username' => 'TestBilliardBot',
        'group_chat_id' => '-10099887766',
        'is_active' => true,
    ]);
});

test('user can send a duel challenge to an opponent', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenger = User::factory()->create(['name' => 'Nguyễn Văn An', 'username' => 'an']);
    $opponent = User::factory()->create([
        'name' => 'Trần Văn Bình',
        'username' => 'binh',
        'telegram_id' => '99887766',
    ]);

    Livewire::actingAs($challenger)
        ->test(DuelChallengeAlert::class)
        ->set('selectedOpponentId', $opponent->id)
        ->set('challengePrize', '1 ly cà phê muối')
        ->set('challengeMessage', 'Ra bàn số 3 chiến ngay!')
        ->call('sendChallenge')
        ->assertHasNoErrors();

    $challenge = BilliardChallenge::where('challenger_id', $challenger->id)
        ->where('opponent_id', $opponent->id)
        ->first();

    expect($challenge)->not->toBeNull();
    expect($challenge->prize)->toBe('1 ly cà phê muối');
    expect($challenge->message)->toBe('Ra bàn số 3 chiến ngay!');
    expect($challenge->status)->toBe('pending');

    // Telegram notification sent to opponent
    Http::assertSent(function ($request) use ($opponent) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $opponent->telegram_id
            && str_contains($request['text'], 'BÁO ĐỘNG SOS: BẠN VỪA BỊ KHIÊU CHIẾN');
    });
});

test('user cannot challenge themselves', function () {
    $user = User::factory()->create(['name' => 'An', 'username' => 'an']);

    Livewire::actingAs($user)
        ->test(DuelChallengeAlert::class)
        ->set('selectedOpponentId', $user->id)
        ->set('challengePrize', 'Kèo nước ngọt')
        ->call('sendChallenge')
        ->assertHasErrors(['selectedOpponentId']);

    expect(BilliardChallenge::count())->toBe(0);
});

test('opponent sees SOS emergency challenge alert and can accept it', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenger = User::factory()->create(['name' => 'An', 'username' => 'an', 'telegram_id' => '112233']);
    $opponent = User::factory()->create(['name' => 'Bình', 'username' => 'binh']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $challenger->id,
        'opponent_id' => $opponent->id,
        'prize' => '1 ly trà sữa trân châu',
        'message' => 'Dám solo không?',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($opponent)
        ->test(DuelChallengeAlert::class)
        ->assertSee('BÁO ĐỘNG SOS: LỜI KHIÊU CHIẾN KHẨN CẤP!')
        ->assertSee('1 ly trà sữa trân châu')
        ->assertSee('Dám solo không?')
        ->call('acceptChallenge', $challenge->id);

    $challenge->refresh();
    expect($challenge->status)->toBe('accepted');
    expect($challenge->accepted_at)->not->toBeNull();
    expect($challenge->match_id)->not->toBeNull();

    $match = BilliardMatch::find($challenge->match_id);
    expect($match)->not->toBeNull();
    expect($match->prize)->toBe('1 ly trà sữa trân châu');

    // Challenger was notified
    Http::assertSent(function ($request) use ($challenger) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $challenger->telegram_id
            && str_contains($request['text'], 'ĐÃ TIẾP CHIÊU');
    });
});

test('opponent can decline challenge', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenger = User::factory()->create(['name' => 'An', 'username' => 'an', 'telegram_id' => '112233']);
    $opponent = User::factory()->create(['name' => 'Bình', 'username' => 'binh']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $challenger->id,
        'opponent_id' => $opponent->id,
        'prize' => '1 tô phở bò',
        'message' => 'Ra bàn 1 ngay!',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    Livewire::actingAs($opponent)
        ->test(DuelChallengeAlert::class)
        ->call('declineChallenge', $challenge->id);

    $challenge->refresh();
    expect($challenge->status)->toBe('declined');
    expect($challenge->declined_at)->not->toBeNull();

    // Challenger notified of decline
    Http::assertSent(function ($request) use ($challenger) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $challenger->telegram_id
            && str_contains($request['text'], 'ĐỐI THỦ ĐÃ TỪ CHỐI');
    });
});

test('telegram bot callback can accept a duel challenge', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenger = User::factory()->create(['name' => 'An', 'username' => 'an', 'telegram_id' => '112233']);
    $opponent = User::factory()->create(['name' => 'Bình', 'username' => 'binh', 'telegram_id' => '445566']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $challenger->id,
        'opponent_id' => $opponent->id,
        'prize' => '1 ly nước mía',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $botService = app(TelegramBotService::class);
    $matchService = app(BilliardMatchService::class);
    $statsService = app(BilliardStatsService::class);

    $botService->handleWebhook([
        'callback_query' => [
            'id' => 'cb_123',
            'from' => ['id' => 445566],
            'data' => "accept_challenge_{$challenge->id}",
            'message' => ['chat' => ['id' => 445566]],
        ],
    ], $matchService, $statsService);

    $challenge->refresh();
    expect($challenge->status)->toBe('accepted');
    expect($challenge->match_id)->not->toBeNull();
});
