<?php

use App\Models\BilliardMatch;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\BilliardMatchService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

test('a member can create a match and opponent receives 24h confirmation deadline', function () {
    $player1 = User::factory()->create(['name' => 'An', 'username' => 'an']);
    $player2 = User::factory()->create(['name' => 'Bình', 'username' => 'binh']);

    $matchService = app(BilliardMatchService::class);

    $match = $matchService->createMatch([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'prize' => 'Kèo cà phê muối',
        'notes' => 'Chạm 3 ván',
    ], $player1);

    expect($match->status)->toBe('pending');
    expect($match->winner_id)->toBe($player1->id);
    expect($match->loser_id)->toBe($player2->id);
    expect($match->auto_confirm_at)->not->toBeNull();
    expect($match->notified_at->diffInHours($match->auto_confirm_at))->toEqual(24);

    // Opponent received notification
    $notification = UserNotification::where('user_id', $player2->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe('match_pending_confirmation');
});

test('a player cannot create a match against themselves', function () {
    $player = User::factory()->create();
    $matchService = app(BilliardMatchService::class);

    $this->expectException(ValidationException::class);

    $matchService->createMatch([
        'player1_id' => $player->id,
        'player2_id' => $player->id,
        'winner_id' => $player->id,
        'prize' => 'Kèo cà phê',
    ], $player);
});

test('creator cannot self-confirm on behalf of opponent', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $matchService = app(BilliardMatchService::class);

    $match = $matchService->createMatch([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'prize' => 'Kèo cà phê',
    ], $player1);

    $this->expectException(ValidationException::class);

    // Creator tries to confirm
    $matchService->confirmMatch($match, $player1);
});

test('opponent can accept and confirm match result', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $matchService = app(BilliardMatchService::class);

    $match = $matchService->createMatch([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'prize' => 'Kèo cà phê muối',
    ], $player1);

    $result = $matchService->confirmMatch($match, $player2);

    expect($result)->toBeTrue();
    $match->refresh();
    expect($match->status)->toBe('confirmed');
    expect($match->confirmed_by)->toBe('opponent');
    expect($match->confirmed_at)->not->toBeNull();
});

test('opponent can dispute match with mandatory reason', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $matchService = app(BilliardMatchService::class);

    $match = $matchService->createMatch([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'prize' => 'Kèo cà phê muối',
    ], $player1);

    $matchService->disputeMatch($match, $player2, 'Tôi mới là người dọn bàn bi 8 trước!');

    $match->refresh();
    expect($match->status)->toBe('disputed');
    expect($match->dispute_reason)->toBe('Tôi mới là người dọn bàn bi 8 trước!');
});

test('auto-confirm command confirms matches past 24 hours', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    // Create match that expired 24h window
    $match = BilliardMatch::factory()->create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'pending',
        'notified_at' => Carbon::now()->subHours(25),
        'auto_confirm_at' => Carbon::now()->subHours(1),
    ]);

    $matchService = app(BilliardMatchService::class);
    $confirmedCount = $matchService->autoConfirmExpiredMatches();

    expect($confirmedCount)->toBe(1);
    $match->refresh();
    expect($match->status)->toBe('confirmed');
    expect($match->confirmed_by)->toBe('auto_24h');
});

test('admin can resolve disputed matches with audit history', function () {
    $admin = User::factory()->admin()->create();
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::factory()->disputed()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
    ]);

    $matchService = app(BilliardMatchService::class);

    // Admin decides to reverse winner based on review
    $matchService->adminResolveMatch($match, $admin, 'reverse_winner', null, 'Đã kiểm tra video quay lại bàn bida.');

    $match->refresh();
    expect($match->status)->toBe('resolved');
    expect($match->winner_id)->toBe($player2->id);
    expect($match->loser_id)->toBe($player1->id);
    expect($match->admin_notes)->toContain('Đã kiểm tra video');
});

test('a member can create a doubles match and team 2 receives notifications', function () {
    $player1 = User::factory()->create(['name' => 'An']);
    $player1Partner = User::factory()->create(['name' => 'Bình']);
    $player2 = User::factory()->create(['name' => 'Cường']);
    $player2Partner = User::factory()->create(['name' => 'Dũng']);

    $matchService = app(BilliardMatchService::class);

    $match = $matchService->createMatch([
        'match_type' => 'doubles',
        'player1_id' => $player1->id,
        'player1_partner_id' => $player1Partner->id,
        'player2_id' => $player2->id,
        'player2_partner_id' => $player2Partner->id,
        'winner_id' => $player1->id,
        'winner_partner_id' => $player1Partner->id,
        'loser_id' => $player2->id,
        'loser_partner_id' => $player2Partner->id,
        'prize' => 'Kèo cà phê muối',
    ], $player1);

    expect($match->isDoubles())->toBeTrue();
    expect($match->status)->toBe('pending');
    expect($match->winner_id)->toBe($player1->id);
    expect($match->winner_partner_id)->toBe($player1Partner->id);
    expect($match->loser_id)->toBe($player2->id);
    expect($match->loser_partner_id)->toBe($player2Partner->id);

    // Both opponents in Team 2 receive notifications
    expect(UserNotification::where('user_id', $player2->id)->exists())->toBeTrue();
    expect(UserNotification::where('user_id', $player2Partner->id)->exists())->toBeTrue();
});

test('loser can pay prize for confirmed match', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'played_at' => Carbon::now(),
        'prize' => 'Kèo cà phê muối',
        'status' => 'confirmed',
        'prize_status' => 'unpaid',
    ]);

    $matchService = app(BilliardMatchService::class);
    $matchService->settlePrize($match, $player2, 'Đã bao cà phê');

    $match->refresh();
    expect($match->isPrizePaid())->toBeTrue();
    expect($match->prize_status)->toBe('paid');
    expect($match->prize_paid_by)->toBe($player2->id);
    expect($match->prize_notes)->toBe('Đã bao cà phê');
    expect($match->prize_paid_at)->not->toBeNull();

    // Check winner received notification
    $notification = UserNotification::where('user_id', $player1->id)->where('type', 'prize_settled')->first();
    expect($notification)->not->toBeNull();
});

test('unrelated user cannot pay prize', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();
    $stranger = User::factory()->create();

    $match = BilliardMatch::create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'played_at' => Carbon::now(),
        'prize' => 'Kèo cà phê',
        'status' => 'confirmed',
        'prize_status' => 'unpaid',
    ]);

    $matchService = app(BilliardMatchService::class);

    $this->expectException(ValidationException::class);
    $matchService->settlePrize($match, $stranger);
});

test('cannot pay prize if match is still pending', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    $match = BilliardMatch::create([
        'creator_id' => $player1->id,
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'played_at' => Carbon::now(),
        'prize' => 'Kèo cà phê',
        'status' => 'pending',
        'prize_status' => 'unpaid',
    ]);

    $matchService = app(BilliardMatchService::class);

    $this->expectException(ValidationException::class);
    $matchService->settlePrize($match, $player2);
});

test('user can pay prize via web route', function () {
    $winner = User::factory()->create();
    $loser = User::factory()->create();

    $match = BilliardMatch::create([
        'creator_id' => $winner->id,
        'player1_id' => $winner->id,
        'player2_id' => $loser->id,
        'winner_id' => $winner->id,
        'loser_id' => $loser->id,
        'played_at' => Carbon::now(),
        'prize' => 'Bữa trưa',
        'status' => 'confirmed',
        'prize_status' => 'unpaid',
    ]);

    $response = $this->actingAs($loser)->post(route('matches.pay-prize', $match->id), [
        'note' => 'Đã bao ăn trưa',
    ]);

    $response->assertSessionHas('success');
    $match->refresh();
    expect($match->isPrizePaid())->toBeTrue();
    expect($match->prize_paid_by)->toBe($loser->id);
    expect($match->prize_notes)->toBe('Đã bao ăn trưa');
});
