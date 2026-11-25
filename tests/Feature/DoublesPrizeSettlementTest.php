<?php

use App\Models\BilliardMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('doubles match initializes both prize_status and prize_partner_status as unpaid', function () {
    $an = User::factory()->create(['name' => 'An']);
    $thanh = User::factory()->create(['name' => 'Thanh']);
    $tho = User::factory()->create(['name' => 'Thọ']);
    $dat = User::factory()->create(['name' => 'Đạt']);

    $match = BilliardMatch::factory()->create([
        'creator_id' => $an->id,
        'match_type' => 'doubles',
        'player1_id' => $an->id,
        'player1_partner_id' => $thanh->id,
        'player2_id' => $tho->id,
        'player2_partner_id' => $dat->id,
        'winner_id' => $an->id,
        'winner_partner_id' => $thanh->id,
        'loser_id' => $tho->id,
        'loser_partner_id' => $dat->id,
        'prize' => '1 ly trà đào',
        'prize_status' => 'unpaid',
        'prize_partner_status' => 'unpaid',
        'status' => 'confirmed',
    ]);

    expect($match->isDoubles())->toBeTrue()
        ->and($match->prize_status)->toBe('unpaid')
        ->and($match->prize_partner_status)->toBe('unpaid')
        ->and($match->getCounterpartForUser($tho)->id)->toBe($an->id)
        ->and($match->getCounterpartForUser($dat)->id)->toBe($thanh->id)
        ->and($match->getCounterpartForUser($an)->id)->toBe($tho->id)
        ->and($match->getCounterpartForUser($thanh)->id)->toBe($dat->id);

    // Debt counters
    expect($tho->fresh()->unpaid_debts_count)->toBe(1)
        ->and($dat->fresh()->unpaid_debts_count)->toBe(1)
        ->and($an->fresh()->unpaid_receivables_count)->toBe(1)
        ->and($thanh->fresh()->unpaid_receivables_count)->toBe(1);
});

test('pair 1 and pair 2 settle debts completely independently', function () {
    $an = User::factory()->create(['name' => 'An']);
    $thanh = User::factory()->create(['name' => 'Thanh']);
    $tho = User::factory()->create(['name' => 'Thọ']);
    $dat = User::factory()->create(['name' => 'Đạt']);

    $match = BilliardMatch::factory()->create([
        'creator_id' => $an->id,
        'match_type' => 'doubles',
        'player1_id' => $an->id,
        'player1_partner_id' => $thanh->id,
        'player2_id' => $tho->id,
        'player2_partner_id' => $dat->id,
        'winner_id' => $an->id,
        'winner_partner_id' => $thanh->id,
        'loser_id' => $tho->id,
        'loser_partner_id' => $dat->id,
        'prize' => '1 ly cà phê muối',
        'prize_status' => 'unpaid',
        'prize_partner_status' => 'unpaid',
        'status' => 'confirmed',
    ]);

    // 1. Thọ pays An (Pair 1)
    $this->actingAs($tho)->post(route('matches.pay-prize', $match->id), [
        'note' => 'Thọ đã chuyển khoản cho An',
        'pair_slot' => 'primary',
    ])->assertRedirect();

    $match->refresh();
    expect($match->prize_status)->toBe('paid')
        ->and($match->prize_partner_status)->toBe('unpaid')
        ->and($tho->fresh()->unpaid_debts_count)->toBe(0)
        ->and($tho->fresh()->paid_debts_count)->toBe(1)
        ->and($dat->fresh()->unpaid_debts_count)->toBe(1); // Đạt still owes Thanh!

    // 2. An confirms Thọ's payment OK
    $this->actingAs($an)->post(route('matches.confirm-prize', $match->id), [
        'note' => 'An đã nhận đủ',
        'pair_slot' => 'primary',
    ])->assertRedirect();

    $match->refresh();
    expect($match->prize_status)->toBe('confirmed')
        ->and($match->prize_partner_status)->toBe('unpaid')
        ->and($an->fresh()->unpaid_receivables_count)->toBe(0)
        ->and($thanh->fresh()->unpaid_receivables_count)->toBe(1) // Thanh still waiting for Đạt!
        ->and($match->isPrizeConfirmed())->toBeFalse(); // Match as a whole is not fully confirmed until both pairs confirm!

    // 3. Đạt pays Thanh (Pair 2)
    $this->actingAs($dat)->post(route('matches.pay-prize', $match->id), [
        'note' => 'Đạt mua nước cho Thanh',
        'pair_slot' => 'partner',
    ])->assertRedirect();

    $match->refresh();
    expect($match->prize_partner_status)->toBe('paid');

    // 4. Thanh rejects Đạt's payment (wrong item)
    $this->actingAs($thanh)->post(route('matches.reject-prize', $match->id), [
        'reason' => 'Chưa thấy nước nhé bạn ơi',
        'pair_slot' => 'partner',
    ])->assertRedirect();

    $match->refresh();
    expect($match->prize_partner_status)->toBe('rejected')
        ->and($match->prize_status)->toBe('confirmed') // Pair 1 remains confirmed!
        ->and($dat->fresh()->unpaid_debts_count)->toBe(1);

    // 5. Đạt pays again
    $this->actingAs($dat)->post(route('matches.pay-prize', $match->id), [
        'note' => 'Đã mang ly nước đến bàn',
        'pair_slot' => 'partner',
    ])->assertRedirect();

    // 6. Thanh confirms Đạt's payment
    $this->actingAs($thanh)->post(route('matches.confirm-prize', $match->id), [
        'note' => 'Thanh đã nhận đủ nhé',
        'pair_slot' => 'partner',
    ])->assertRedirect();

    $match->refresh();
    expect($match->prize_partner_status)->toBe('confirmed')
        ->and($match->prize_status)->toBe('confirmed')
        ->and($match->isPrizeConfirmed())->toBeTrue()
        ->and($dat->fresh()->unpaid_debts_count)->toBe(0)
        ->and($thanh->fresh()->unpaid_receivables_count)->toBe(0);
});

test('dashboard displays personalized cross-split counterpart debts and receivables', function () {
    $an = User::factory()->create(['name' => 'An Nguyễn']);
    $thanh = User::factory()->create(['name' => 'Thanh Lê']);
    $tho = User::factory()->create(['name' => 'Thọ Trần']);
    $dat = User::factory()->create(['name' => 'Đạt Phạm']);

    $match = BilliardMatch::factory()->create([
        'creator_id' => $an->id,
        'match_type' => 'doubles',
        'player1_id' => $an->id,
        'player1_partner_id' => $thanh->id,
        'player2_id' => $tho->id,
        'player2_partner_id' => $dat->id,
        'winner_id' => $an->id,
        'winner_partner_id' => $thanh->id,
        'loser_id' => $tho->id,
        'loser_partner_id' => $dat->id,
        'prize' => '1 ly trà đào cam sả',
        'prize_status' => 'unpaid',
        'prize_partner_status' => 'unpaid',
        'status' => 'confirmed',
    ]);

    // Thọ's dashboard
    $resTho = $this->actingAs($tho)->get(route('dashboard'));
    $resTho->assertOk();
    $resTho->assertSee('Kèo đôi tách chéo');
    $resTho->assertSee('Bạn nợ riêng:');
    $resTho->assertSee('An Nguyễn');
    $resTho->assertSee('Trả kèo cho An Nguyễn');

    // Đạt's dashboard
    $resDat = $this->actingAs($dat)->get(route('dashboard'));
    $resDat->assertOk();
    $resDat->assertSee('Kèo đôi tách chéo');
    $resDat->assertSee('Bạn nợ riêng:');
    $resDat->assertSee('Thanh Lê');
    $resDat->assertSee('Trả kèo cho Thanh Lê');

    // An's dashboard (Receivables)
    $resAn = $this->actingAs($an)->get(route('dashboard'));
    $resAn->assertOk();
    $resAn->assertSee('Kèo đôi tách chéo');
    $resAn->assertSee('Thọ Trần');
    $resAn->assertSee('nợ riêng bạn');

    // Thanh's dashboard (Receivables)
    $resThanh = $this->actingAs($thanh)->get(route('dashboard'));
    $resThanh->assertOk();
    $resThanh->assertSee('Kèo đôi tách chéo');
    $resThanh->assertSee('Đạt Phạm');
    $resThanh->assertSee('nợ riêng bạn');
});

test('match show page displays both independent cross-pair cards for doubles', function () {
    $an = User::factory()->create(['name' => 'An Nguyễn']);
    $thanh = User::factory()->create(['name' => 'Thanh Lê']);
    $tho = User::factory()->create(['name' => 'Thọ Trần']);
    $dat = User::factory()->create(['name' => 'Đạt Phạm']);

    $match = BilliardMatch::factory()->create([
        'creator_id' => $an->id,
        'match_type' => 'doubles',
        'player1_id' => $an->id,
        'player1_partner_id' => $thanh->id,
        'player2_id' => $tho->id,
        'player2_partner_id' => $dat->id,
        'winner_id' => $an->id,
        'winner_partner_id' => $thanh->id,
        'loser_id' => $tho->id,
        'loser_partner_id' => $dat->id,
        'prize' => '2 ly sinh tố bơ',
        'prize_status' => 'paid',
        'prize_partner_status' => 'unpaid',
        'status' => 'confirmed',
    ]);

    $res = $this->actingAs($an)->get(route('matches.show', $match->id));
    $res->assertOk();
    $res->assertSee('Kèo đôi tách chéo độc lập');
    $res->assertSee('Cặp 1: Thọ Trần ➡️ An Nguyễn');
    $res->assertSee('Cặp 2: Đạt Phạm ➡️ Thanh Lê');
    $res->assertSee('Đã trả (Chờ OK)');
    $res->assertSee('Chưa trả');
});
