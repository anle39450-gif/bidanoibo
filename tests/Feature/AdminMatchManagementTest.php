<?php

use App\Models\BilliardMatch;
use App\Models\MatchHistory;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->member = User::factory()->create(['role' => 'member']);
    $this->player2 = User::factory()->create(['role' => 'member']);
});

test('non-admin user is forbidden from admin match actions', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);

    $this->actingAs($this->member)->post(route('admin.matches.cancel', $match->id))->assertForbidden();
    $this->actingAs($this->member)->post(route('admin.matches.toggle-hidden', $match->id))->assertForbidden();
    $this->actingAs($this->member)->post(route('admin.matches.trash', $match->id))->assertForbidden();
    $this->actingAs($this->member)->post(route('admin.trash.empty'))->assertForbidden();
});

test('admin can view admin panel with all matches and trash bin count', function () {
    $activeMatch = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
        'prize' => 'Kèo cà phê muối test admin',
    ]);

    $trashedMatch = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
        'prize' => 'Kèo trashed match test',
    ]);
    $trashedMatch->delete();

    $response = $this->actingAs($this->admin)->get(route('admin.index'));
    $response->assertOk();
    $response->assertSee('Quản Lý Toàn Bộ Trận Đấu');
    $response->assertSee('Thùng Rác Trận Đấu');
    $response->assertSee('Kèo cà phê muối test admin');
    $response->assertSee('Kèo trashed match test');
    $response->assertSee('1 trận'); // in trash count
});

test('admin can cancel a match with optional reason', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
        'status' => 'confirmed',
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.matches.cancel', $match->id), [
        'reason' => 'Trận đấu nhầm tỷ số, admin hủy để đấu lại',
    ]);

    $response->assertSessionHas('success');
    $match->refresh();

    expect($match->status)->toBe('cancelled')
        ->and($match->isCancelled())->toBeTrue();

    // Check MatchHistory
    $history = MatchHistory::where('billiard_match_id', $match->id)
        ->where('action', 'admin_cancelled')
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->user_id)->toBe($this->admin->id)
        ->and($history->note)->toContain('Trận đấu nhầm tỷ số, admin hủy để đấu lại');
});

test('admin can toggle match hidden status', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
        'is_hidden' => false,
    ]);

    // 1. Hide match
    $this->actingAs($this->admin)->post(route('admin.matches.toggle-hidden', $match->id));
    $match->refresh();
    expect($match->is_hidden)->toBeTrue();

    // 2. Unhide match
    $this->actingAs($this->admin)->post(route('admin.matches.toggle-hidden', $match->id));
    $match->refresh();
    expect($match->is_hidden)->toBeFalse();
});

test('hidden match is excluded from non-admin calendar and returns 404 on show', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
        'is_hidden' => true,
        'prize' => 'Kèo bí mật không ai thấy',
        'played_at' => Carbon::today(),
    ]);

    // Regular member show route -> 404
    $this->actingAs($this->member)->get(route('matches.show', $match->id))->assertNotFound();

    // Regular member calendar data -> does not see the match
    $response = $this->actingAs($this->member)->getJson(route('calendar.data', [
        'year' => Carbon::today()->year,
        'month' => Carbon::today()->month,
    ]));
    $response->assertOk();
    $data = $response->json();
    $matchesForToday = $data['matchesByDate'][Carbon::today()->format('Y-m-d')] ?? [];
    $found = collect($matchesForToday)->contains(fn ($m) => $m['id'] === $match->id);
    expect($found)->toBeFalse();

    // Admin can view the hidden match
    $this->actingAs($this->admin)->get(route('matches.show', $match->id))->assertOk()->assertSee('Đang ẩn');
});

test('admin can move match to trash (soft delete)', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);

    $response = $this->actingAs($this->admin)->post(route('admin.matches.trash', $match->id));
    $response->assertSessionHas('success');

    expect(BilliardMatch::find($match->id))->toBeNull()
        ->and(BilliardMatch::withTrashed()->find($match->id)->trashed())->toBeTrue();

    $history = MatchHistory::where('billiard_match_id', $match->id)
        ->where('action', 'moved_to_trash')
        ->first();
    expect($history)->not->toBeNull();
});

test('trashed match is 404 for non-admin but admin can view it with restore options', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);
    $match->delete();

    // Non-admin -> 404
    $this->actingAs($this->member)->get(route('matches.show', $match->id))->assertNotFound();

    // Admin -> 200 with Trash indicator
    $this->actingAs($this->admin)->get(route('matches.show', $match->id))
        ->assertOk()
        ->assertSee('Trận đấu này đang nằm trong Thùng rác')
        ->assertSee('Khôi phục trận');
});

test('admin can restore a match from trash', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);
    $match->delete();

    $response = $this->actingAs($this->admin)->post(route('admin.trash.restore', $match->id));
    $response->assertSessionHas('success');

    $match->refresh();
    expect($match->trashed())->toBeFalse();

    $history = MatchHistory::where('billiard_match_id', $match->id)
        ->where('action', 'restored_from_trash')
        ->first();
    expect($history)->not->toBeNull();
});

test('admin can permanently force delete a match from trash', function () {
    $match = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);
    $matchId = $match->id;
    $match->delete();

    $response = $this->actingAs($this->admin)->post(route('admin.trash.force-delete', $matchId));
    $response->assertSessionHas('success');

    expect(BilliardMatch::withTrashed()->find($matchId))->toBeNull();
});

test('admin can empty trash completely', function () {
    $match1 = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);
    $match2 = BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $this->member->id,
        'player2_id' => $this->player2->id,
        'winner_id' => $this->member->id,
        'loser_id' => $this->player2->id,
    ]);
    $match1->delete();
    $match2->delete();

    expect(BilliardMatch::onlyTrashed()->count())->toBe(2);

    $response = $this->actingAs($this->admin)->post(route('admin.trash.empty'));
    $response->assertSessionHas('success');

    expect(BilliardMatch::onlyTrashed()->count())->toBe(0);
});
