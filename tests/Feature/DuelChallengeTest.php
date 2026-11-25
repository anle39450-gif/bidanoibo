<?php

use App\Models\BilliardChallenge;
use App\Models\BilliardMatch;
use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
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

test('user can send a duel challenge to an opponent via pure AJAX', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenger = User::factory()->create(['name' => 'Nguyễn Văn An', 'username' => 'an']);
    $opponent = User::factory()->create([
        'name' => 'Trần Văn Bình',
        'username' => 'binh',
        'telegram_id' => '99887766',
    ]);

    $response = $this->actingAs($challenger)->postJson('/challenges', [
        'opponent_id' => $opponent->id,
        'prize' => '1 ly cà phê muối',
        'message' => 'Ra bàn số 3 chiến ngay!',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

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

test('user cannot challenge themselves via AJAX', function () {
    $user = User::factory()->create(['name' => 'An', 'username' => 'an']);

    $response = $this->actingAs($user)->postJson('/challenges', [
        'opponent_id' => $user->id,
        'prize' => 'Kèo nước ngọt',
    ]);

    $response->assertStatus(422);
    expect(BilliardChallenge::count())->toBe(0);
});

test('opponent checks pending challenges via AJAX and sees SOS info', function () {
    $challenger = User::factory()->create(['name' => 'An', 'username' => 'an']);
    $opponent = User::factory()->create(['name' => 'Bình', 'username' => 'binh']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $challenger->id,
        'opponent_id' => $opponent->id,
        'prize' => '1 ly trà sữa trân châu',
        'message' => 'Dám solo không?',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $response = $this->actingAs($opponent)->getJson('/challenges/check');

    $response->assertStatus(200)
        ->assertJson([
            'has_challenge' => true,
            'challenge' => [
                'id' => $challenge->id,
                'challenger_name' => 'An',
                'prize' => '1 ly trà sữa trân châu',
                'message' => 'Dám solo không?',
            ],
        ]);
});

test('opponent can accept challenge via pure AJAX and it becomes an upcoming match', function () {
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

    $response = $this->actingAs($opponent)->postJson("/challenges/{$challenge->id}/accept");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'redirect_url' => route('calendar', ['tab' => 'upcoming']),
        ]);

    $challenge->refresh();
    expect($challenge->status)->toBe('accepted');
    expect($challenge->accepted_at)->not->toBeNull();
    // Match is NOT completed yet; it is waiting to be played
    expect($challenge->match_id)->toBeNull();

    // Challenger was notified
    Http::assertSent(function ($request) use ($challenger) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $challenger->telegram_id
            && str_contains($request['text'], 'ĐÃ TIẾP CHIÊU');
    });
});

test('accepted challenge appears in calendar upcoming matches and allows participants to pick winner', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenger = User::factory()->create(['name' => 'An', 'username' => 'an', 'telegram_id' => '112233']);
    $opponent = User::factory()->create(['name' => 'Bình', 'username' => 'binh', 'telegram_id' => '445566']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $challenger->id,
        'opponent_id' => $opponent->id,
        'prize' => 'Kèo cà phê muối',
        'message' => 'Solo ra đòn đi!',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    // View calendar with upcoming tab
    $calResponse = $this->actingAs($opponent)->get('/calendar?tab=upcoming');
    $calResponse->assertStatus(200);
    $calResponse->assertSee('Trận đấu sắp diễn ra');
    $calResponse->assertSee('Kèo cà phê muối');
    $calResponse->assertSee('An thắng');
    $calResponse->assertSee('Bình thắng');

    // Challenger resolves match: Opponent won!
    $resolveResponse = $this->actingAs($challenger)->postJson("/challenges/{$challenge->id}/resolve", [
        'winner_id' => $opponent->id,
    ]);

    $resolveResponse->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $challenge->refresh();
    expect($challenge->status)->toBe('completed');
    expect($challenge->match_id)->not->toBeNull();

    $match = BilliardMatch::find($challenge->match_id);
    expect($match)->not->toBeNull();
    expect($match->winner_id)->toBe($opponent->id);
    expect($match->loser_id)->toBe($challenger->id);
    expect($match->status)->toBe('confirmed');
    expect($match->prize)->toBe('Kèo cà phê muối');

    // Stats updated: opponent has 1 win, challenger has 1 loss and 1 debt
    expect($opponent->refresh()->wins_count)->toBe(1);
    expect($challenger->refresh()->losses_count)->toBe(1);
    expect($challenger->unpaid_debts_count)->toBe(1);

    // Group notified of result
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && str_contains($request['text'], 'KẾT QUẢ KHIÊU CHIẾN: Bình CHIẾN THẮNG');
    });
});

test('admin can resolve challenge outcome and pick winner', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $admin = User::factory()->admin()->create(['name' => 'Admin Boss']);
    $player1 = User::factory()->create(['name' => 'Player 1']);
    $player2 = User::factory()->create(['name' => 'Player 2']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 bát bún bò',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($admin)->postJson("/challenges/{$challenge->id}/resolve", [
        'winner_id' => $player1->id,
    ]);

    $response->assertStatus(200)->assertJson(['success' => true]);

    $challenge->refresh();
    expect($challenge->status)->toBe('completed');
    expect($challenge->match->winner_id)->toBe($player1->id);
});

test('outsider cannot resolve challenge and gets 403', function () {
    $player1 = User::factory()->create(['name' => 'Player 1']);
    $player2 = User::factory()->create(['name' => 'Player 2']);
    $outsider = User::factory()->create(['name' => 'Outsider']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => 'Kèo cà phê',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($outsider)->postJson("/challenges/{$challenge->id}/resolve", [
        'winner_id' => $player1->id,
    ]);

    $response->assertStatus(403);
});

test('opponent can decline challenge via pure AJAX', function () {
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

    $response = $this->actingAs($opponent)->postJson("/challenges/{$challenge->id}/decline");
    $response->assertStatus(200)->assertJson(['success' => true]);

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

test('telegram bot callback can accept a duel challenge into upcoming matches', function () {
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
    expect($challenge->accepted_at)->not->toBeNull();
});
