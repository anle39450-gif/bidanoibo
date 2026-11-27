<?php

use App\Models\BilliardChallenge;
use App\Models\BilliardSideBet;
use App\Models\TelegramBotConfig;
use App\Models\User;
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

test('user can place a side bet on an accepted challenge', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $player1 = User::factory()->create(['name' => 'Lương', 'username' => 'luong']);
    $player2 = User::factory()->create(['name' => 'Thọ', 'username' => 'tho']);
    $bettor1 = User::factory()->create(['name' => 'Đạt', 'username' => 'dat']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn', 'username' => 'tuan', 'telegram_id' => '11223344']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($bettor1)->postJson("/challenges/{$challenge->id}/side-bets", [
        'bet_on_team' => 'challenger',
        'target_id' => $bettor2->id,
        'prize' => '1 cốc cà phê',
        'notes' => 'Kèo giao lưu bên lề',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $sideBet = BilliardSideBet::where('billiard_challenge_id', $challenge->id)
        ->where('creator_id', $bettor1->id)
        ->where('target_id', $bettor2->id)
        ->first();

    expect($sideBet)->not->toBeNull();
    expect($sideBet->bet_on_team)->toBe('challenger');
    expect($sideBet->prize)->toBe('1 cốc cà phê');
    expect($sideBet->notes)->toBe('Kèo giao lưu bên lề');
    expect($sideBet->status)->toBe('pending');

    // Telegram notification sent to bettor2 with inline buttons
    Http::assertSent(function ($request) use ($bettor2) {
        return str_contains($request->url(), 'sendMessage')
            && (string) $request['chat_id'] === $bettor2->telegram_id
            && str_contains($request['text'], 'KÈO NGOÀI BÊN LỀ');
    });
});

test('user cannot bet against themselves', function () {
    $player1 = User::factory()->create(['name' => 'Lương', 'username' => 'luong']);
    $player2 = User::factory()->create(['name' => 'Thọ', 'username' => 'tho']);
    $bettor = User::factory()->create(['name' => 'Đạt', 'username' => 'dat']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($bettor)->postJson("/challenges/{$challenge->id}/side-bets", [
        'bet_on_team' => 'challenger',
        'target_id' => $bettor->id,
        'prize' => '1 cốc cà phê',
    ]);

    $response->assertStatus(422);
});

test('one user can place multiple side bets on the same challenge', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $player1 = User::factory()->create(['name' => 'Lương']);
    $player2 = User::factory()->create(['name' => 'Thọ']);
    $bettor1 = User::factory()->create(['name' => 'Đạt']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn']);
    $bettor3 = User::factory()->create(['name' => 'Thanh']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    // Bet 1: Đạt bets on Lương against Tuấn
    $this->actingAs($bettor1)->postJson("/challenges/{$challenge->id}/side-bets", [
        'bet_on_team' => 'challenger',
        'target_id' => $bettor2->id,
        'prize' => '1 cốc cà phê',
    ])->assertStatus(200);

    // Bet 2: Đạt also bets on Lương against Thanh for 1 ly nước ép
    $this->actingAs($bettor1)->postJson("/challenges/{$challenge->id}/side-bets", [
        'bet_on_team' => 'challenger',
        'target_id' => $bettor3->id,
        'prize' => '1 ly nước ép',
    ])->assertStatus(200);

    $sideBets = BilliardSideBet::where('billiard_challenge_id', $challenge->id)
        ->where('creator_id', $bettor1->id)
        ->get();

    expect($sideBets->count())->toBe(2);
});

test('target user can accept a side bet', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $player1 = User::factory()->create(['name' => 'Lương']);
    $player2 = User::factory()->create(['name' => 'Thọ']);
    $bettor1 = User::factory()->create(['name' => 'Đạt', 'telegram_id' => '11111']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn', 'telegram_id' => '22222']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $sideBet = BilliardSideBet::create([
        'billiard_challenge_id' => $challenge->id,
        'creator_id' => $bettor1->id,
        'target_id' => $bettor2->id,
        'bet_on_team' => 'challenger',
        'prize' => '1 cốc cà phê',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($bettor2)->postJson("/side-bets/{$sideBet->id}/accept");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $sideBet->refresh();
    expect($sideBet->status)->toBe('accepted');
});

test('target user can decline a side bet', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $player1 = User::factory()->create(['name' => 'Lương']);
    $player2 = User::factory()->create(['name' => 'Thọ']);
    $bettor1 = User::factory()->create(['name' => 'Đạt']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $sideBet = BilliardSideBet::create([
        'billiard_challenge_id' => $challenge->id,
        'creator_id' => $bettor1->id,
        'target_id' => $bettor2->id,
        'bet_on_team' => 'challenger',
        'prize' => '1 cốc cà phê',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($bettor2)->postJson("/side-bets/{$sideBet->id}/decline");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $sideBet->refresh();
    expect($sideBet->status)->toBe('declined');
});

test('creator can cancel a pending side bet', function () {
    $player1 = User::factory()->create(['name' => 'Lương']);
    $player2 = User::factory()->create(['name' => 'Thọ']);
    $bettor1 = User::factory()->create(['name' => 'Đạt']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $sideBet = BilliardSideBet::create([
        'billiard_challenge_id' => $challenge->id,
        'creator_id' => $bettor1->id,
        'target_id' => $bettor2->id,
        'bet_on_team' => 'challenger',
        'prize' => '1 cốc cà phê',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($bettor1)->postJson("/side-bets/{$sideBet->id}/cancel");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $sideBet->refresh();
    expect($sideBet->status)->toBe('cancelled');
});

test('side bets are automatically resolved when challenge concludes', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $player1 = User::factory()->create(['name' => 'Lương', 'username' => 'luong']);
    $player2 = User::factory()->create(['name' => 'Thọ', 'username' => 'tho']);
    $bettor1 = User::factory()->create(['name' => 'Đạt', 'username' => 'dat']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn', 'username' => 'tuan']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    // Bet 1: Đạt bet on Challenger (Lương). Tuấn was target and accepted.
    $sideBet = BilliardSideBet::create([
        'billiard_challenge_id' => $challenge->id,
        'creator_id' => $bettor1->id,
        'target_id' => $bettor2->id,
        'bet_on_team' => 'challenger',
        'prize' => '1 cốc cà phê',
        'status' => 'accepted',
    ]);

    // Main challenge finishes: Player 1 (Lương / challenger) wins!
    $response = $this->actingAs($player1)->postJson("/challenges/{$challenge->id}/resolve", [
        'winner_id' => $player1->id,
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $sideBet->refresh();
    expect($sideBet->status)->toBe('resolved');
    expect($sideBet->winner_id)->toBe($bettor1->id); // Đạt won because challenger won
    expect($sideBet->loser_id)->toBe($bettor2->id);  // Tuấn lost
    expect($sideBet->prize_status)->toBe('unpaid');
});

test('side bet debts can be paid and confirmed', function () {
    $player1 = User::factory()->create(['name' => 'Lương']);
    $player2 = User::factory()->create(['name' => 'Thọ']);
    $bettor1 = User::factory()->create(['name' => 'Đạt']);
    $bettor2 = User::factory()->create(['name' => 'Tuấn']);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc trà sữa',
        'status' => 'resolved',
        'accepted_at' => now(),
    ]);

    // Side bet: bettor1 won, bettor2 lost and owes prize
    $sideBet = BilliardSideBet::create([
        'billiard_challenge_id' => $challenge->id,
        'creator_id' => $bettor1->id,
        'target_id' => $bettor2->id,
        'bet_on_team' => 'challenger',
        'prize' => '1 cốc cà phê',
        'status' => 'resolved',
        'winner_id' => $bettor1->id,
        'loser_id' => $bettor2->id,
        'prize_status' => 'unpaid',
    ]);

    // Loser pays
    $this->actingAs($bettor2)->postJson("/side-bets/{$sideBet->id}/pay", [
        'notes' => 'Đã mời cà phê Highland',
    ])->assertStatus(200)->assertJson(['success' => true]);

    $sideBet->refresh();
    expect($sideBet->prize_status)->toBe('paid');
    expect($sideBet->prize_notes)->toBe('Đã mời cà phê Highland');

    // Winner confirms
    $this->actingAs($bettor1)->postJson("/side-bets/{$sideBet->id}/confirm", [
        'feedback' => 'Đã uống cà phê rất ngon',
    ])->assertStatus(200)->assertJson(['success' => true]);

    $sideBet->refresh();
    expect($sideBet->prize_status)->toBe('confirmed');
    expect($sideBet->prize_feedback)->toBe('Đã uống cà phê rất ngon');
});

test('side bets work with doubles challenge and calendar view', function () {
    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $p1 = User::factory()->create(['name' => 'An']);
    $p2 = User::factory()->create(['name' => 'Thanh']);
    $p3 = User::factory()->create(['name' => 'Thọ']);
    $p4 = User::factory()->create(['name' => 'Đạt']);
    $spectator1 = User::factory()->create(['name' => 'Lương']);
    $spectator2 = User::factory()->create(['name' => 'Tuấn']);

    // Doubles challenge: Team 1 (An & Thanh) vs Team 2 (Thọ & Đạt)
    $challenge = BilliardChallenge::create([
        'challenger_id' => $p1->id,
        'challenger_partner_id' => $p2->id,
        'opponent_id' => $p3->id,
        'opponent_partner_id' => $p4->id,
        'prize' => '2 cốc cà phê',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    // Spectator Lương places side bet betting on Team 1 against Tuấn
    $this->actingAs($spectator1)->postJson("/challenges/{$challenge->id}/side-bets", [
        'bet_on_team' => 'challenger',
        'target_id' => $spectator2->id,
        'prize' => '1 bát phở',
        'notes' => 'Tin tưởng An và Thanh!',
    ])->assertStatus(200);

    $sideBet = BilliardSideBet::where('billiard_challenge_id', $challenge->id)->first();
    expect($sideBet)->not->toBeNull();
    expect($sideBet->creator_team_name)->toContain('An');
    expect($sideBet->target_team_name)->toContain('Thọ');

    // Check calendar page renders the challenge with side bet
    $response = $this->actingAs($spectator1)->get('/calendar?tab=upcoming');
    $response->assertStatus(200);
    $response->assertSeeText('Kèo ngoài');
    $response->assertSee('1 bát phở');

    // Check dashboard page renders pending invite for spectator2
    $response2 = $this->actingAs($spectator2)->get('/dashboard');
    $response2->assertStatus(200);
    $response2->assertSee('Lời rủ vào kèo ngoài đang chờ bạn chốt');
    $response2->assertSee('1 bát phở');

    // Spectator 2 accepts side bet
    $this->actingAs($spectator2)->postJson("/side-bets/{$sideBet->id}/accept")->assertStatus(200);

    // Resolve doubles challenge: Team 1 (An & Thanh) wins!
    $this->actingAs($p1)->postJson("/challenges/{$challenge->id}/resolve", [
        'winner_id' => $p1->id,
    ])->assertStatus(200);

    $sideBet->refresh();
    expect($sideBet->status)->toBe('resolved');
    expect($sideBet->winner_id)->toBe($spectator1->id); // Lương won
    expect($sideBet->loser_id)->toBe($spectator2->id);  // Tuấn lost

    // Now spectator2 has unpaid side bet debt on dashboard
    $response3 = $this->actingAs($spectator2)->get('/dashboard');
    $response3->assertStatus(200);
    $response3->assertSee('Kèo ngoài bạn thua chưa trả');
    $response3->assertSee('1 bát phở');

    // Spectator 1 has receivable on dashboard
    $response4 = $this->actingAs($spectator1)->get('/dashboard');
    $response4->assertStatus(200);
    $response4->assertSee('Kèo ngoài đối thủ chưa trả cho bạn');
    $response4->assertSee('1 bát phở');
});
