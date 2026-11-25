<?php

use App\Models\BilliardChallenge;
use App\Models\BilliardMatch;
use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    TelegramBotConfig::truncate();
    TelegramBotConfig::create([
        'bot_token' => '123456:FAKE_TOKEN_FOR_TEST',
        'bot_username' => 'TestBilliardBot',
        'group_chat_id' => '-10099887766',
        'is_active' => true,
    ]);

    $this->challenger = User::factory()->create(['name' => 'Nguyễn Văn An', 'username' => 'an']);
    $this->partner = User::factory()->create(['name' => 'Lê Văn Bình', 'username' => 'binh']);
    $this->opponent1 = User::factory()->create(['name' => 'Trần Văn Cường', 'username' => 'cuong', 'telegram_id' => '111222333']);
    $this->opponent2 = User::factory()->create(['name' => 'Phạm Văn Dũng', 'username' => 'dung', 'telegram_id' => '444555666']);
    $this->outsider = User::factory()->create(['name' => 'Người Ngoài', 'username' => 'ngoai']);
});

test('user can send a 2vs2 doubles challenge via AJAX', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $response = $this->actingAs($this->challenger)->postJson(route('challenges.store'), [
        'challenge_type' => 'doubles',
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => '2 ly sinh tố bơ',
        'message' => 'Lập team 2vs2 ra bàn số 4 chiến ngay!',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $challenge = BilliardChallenge::where('challenger_id', $this->challenger->id)
        ->where('opponent_id', $this->opponent1->id)
        ->first();

    expect($challenge)->not->toBeNull();
    expect($challenge->isDoubles())->toBeTrue();
    expect($challenge->challenger_partner_id)->toBe($this->partner->id);
    expect($challenge->opponent_partner_id)->toBe($this->opponent2->id);
    expect($challenge->challenger_team_names)->toBe('Nguyễn Văn An & Lê Văn Bình');
    expect($challenge->opponent_team_names)->toBe('Trần Văn Cường & Phạm Văn Dũng');
    expect($challenge->prize)->toBe('2 ly sinh tố bơ');
    expect($challenge->status)->toBe('pending');

    // In-app notifications
    expect(UserNotification::where('user_id', $this->opponent1->id)->count())->toBe(1);
    expect(UserNotification::where('user_id', $this->opponent2->id)->count())->toBe(1);
    expect(UserNotification::where('user_id', $this->partner->id)->count())->toBe(1);

    // Telegram DMs sent to opponents
    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === '111222333'
            && str_contains($request['text'], 'BÁO ĐỘNG SOS: LỜI KHIÊU CHIẾN ĐÔI 2VS2');
    });

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === '444555666'
            && str_contains($request['text'], 'BÁO ĐỘNG SOS: LỜI KHIÊU CHIẾN ĐÔI 2VS2');
    });
});

test('doubles challenge validation fails if players are duplicated or missing', function () {
    // Missing partner
    $res1 = $this->actingAs($this->challenger)->postJson(route('challenges.store'), [
        'challenge_type' => 'doubles',
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo nước',
    ]);
    $res1->assertStatus(422);

    // Duplicate players (opponent1 and opponent2 are same user)
    $res2 = $this->actingAs($this->challenger)->postJson(route('challenges.store'), [
        'challenge_type' => 'doubles',
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent1->id,
        'prize' => 'Kèo nước',
    ]);
    $res2->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Các cơ thủ tham gia trận đôi 2vs2 phải là 4 người khác nhau hoàn toàn!',
        ]);

    // Challenger sets self as opponent
    $res3 = $this->actingAs($this->challenger)->postJson(route('challenges.store'), [
        'challenge_type' => 'doubles',
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->challenger->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo nước',
    ]);
    $res3->assertStatus(422);
});

test('both opponent and opponent partner receive SOS emergency checkPending', function () {
    $challenge = BilliardChallenge::create([
        'challenge_type' => 'doubles',
        'challenger_id' => $this->challenger->id,
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo bữa tối lẩu bò',
        'message' => 'Team 2 bạn dám tiếp chiêu không?',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    // Opponent 1 checkPending
    $res1 = $this->actingAs($this->opponent1)->getJson(route('challenges.check'));
    $res1->assertOk()
        ->assertJson([
            'has_challenge' => true,
            'challenge' => [
                'id' => $challenge->id,
                'is_doubles' => true,
                'challenger_team_names' => 'Nguyễn Văn An & Lê Văn Bình',
                'opponent_team_names' => 'Trần Văn Cường & Phạm Văn Dũng',
                'prize' => 'Kèo bữa tối lẩu bò',
            ],
        ]);

    // Opponent 2 checkPending
    $res2 = $this->actingAs($this->opponent2)->getJson(route('challenges.check'));
    $res2->assertOk()
        ->assertJson([
            'has_challenge' => true,
            'challenge' => [
                'id' => $challenge->id,
                'is_doubles' => true,
                'challenger_team_names' => 'Nguyễn Văn An & Lê Văn Bình',
                'opponent_team_names' => 'Trần Văn Cường & Phạm Văn Dũng',
            ],
        ]);
});

test('opponent partner can accept doubles challenge', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenge = BilliardChallenge::create([
        'challenge_type' => 'doubles',
        'challenger_id' => $this->challenger->id,
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo bia hơi',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $response = $this->actingAs($this->opponent2)->postJson(route('challenges.accept', $challenge->id));
    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $challenge->refresh();
    expect($challenge->status)->toBe('accepted')
        ->and($challenge->accepted_at)->not->toBeNull();

    expect(UserNotification::where('user_id', $this->challenger->id)->where('type', 'duel_accepted')->count())->toBe(1);
    expect(UserNotification::where('user_id', $this->partner->id)->where('type', 'duel_accepted')->count())->toBe(1);
});

test('opponent partner can decline doubles challenge', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenge = BilliardChallenge::create([
        'challenge_type' => 'doubles',
        'challenger_id' => $this->challenger->id,
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo bia hơi',
        'status' => 'pending',
        'expires_at' => now()->addMinutes(30),
    ]);

    $response = $this->actingAs($this->opponent2)->postJson(route('challenges.decline', $challenge->id));
    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $challenge->refresh();
    expect($challenge->status)->toBe('declined');
});

test('participant can resolve doubles challenge and creates confirmed doubles match', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $challenge = BilliardChallenge::create([
        'challenge_type' => 'doubles',
        'challenger_id' => $this->challenger->id,
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo 4 ly chè',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    // Partner of challenger records that Challenger Team won
    $response = $this->actingAs($this->partner)->postJson(route('challenges.resolve', $challenge->id), [
        'winner_id' => $this->challenger->id,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $challenge->refresh();
    expect($challenge->status)->toBe('completed')
        ->and($challenge->match_id)->not->toBeNull();

    $match = BilliardMatch::find($challenge->match_id);
    expect($match)->not->toBeNull();
    expect($match->isDoubles())->toBeTrue();
    expect($match->winner_id)->toBe($this->challenger->id);
    expect($match->winner_partner_id)->toBe($this->partner->id);
    expect($match->loser_id)->toBe($this->opponent1->id);
    expect($match->loser_partner_id)->toBe($this->opponent2->id);
    expect($match->status)->toBe('confirmed');
    expect($match->prize)->toBe('Kèo 4 ly chè');

    // In-app notifications
    expect(UserNotification::where('user_id', $this->challenger->id)->where('type', 'duel_won')->count())->toBe(1);
    expect(UserNotification::where('user_id', $this->partner->id)->where('type', 'duel_won')->count())->toBe(1);
    expect(UserNotification::where('user_id', $this->opponent1->id)->where('type', 'duel_lost')->count())->toBe(1);
    expect(UserNotification::where('user_id', $this->opponent2->id)->where('type', 'duel_lost')->count())->toBe(1);
});

test('outsider cannot resolve doubles challenge', function () {
    $challenge = BilliardChallenge::create([
        'challenge_type' => 'doubles',
        'challenger_id' => $this->challenger->id,
        'challenger_partner_id' => $this->partner->id,
        'opponent_id' => $this->opponent1->id,
        'opponent_partner_id' => $this->opponent2->id,
        'prize' => 'Kèo cà phê',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($this->outsider)->postJson(route('challenges.resolve', $challenge->id), [
        'winner_id' => $this->challenger->id,
    ]);

    $response->assertStatus(403);
});
