<?php

use App\Models\BilliardMatch;
use App\Models\User;
use App\Services\BilliardStatsService;
use Carbon\Carbon;

test('it calculates correct player statistics, win rates, and streaks', function () {
    $player = User::factory()->create();
    $opponent = User::factory()->create();

    // 3 wins, then 1 loss
    for ($i = 0; $i < 3; $i++) {
        BilliardMatch::factory()->confirmed()->create([
            'player1_id' => $player->id,
            'player2_id' => $opponent->id,
            'winner_id' => $player->id,
            'loser_id' => $opponent->id,
            'played_at' => Carbon::now()->subDays(10 - $i),
        ]);
    }

    BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $player->id,
        'player2_id' => $opponent->id,
        'winner_id' => $opponent->id,
        'loser_id' => $player->id,
        'played_at' => Carbon::now()->subDays(1),
    ]);

    $statsService = app(BilliardStatsService::class);
    $stats = $statsService->getPlayerStats($player);

    expect($stats['total_matches'])->toBe(4);
    expect($stats['wins'])->toBe(3);
    expect($stats['losses'])->toBe(1);
    expect($stats['win_rate'])->toBe(75.0);
    expect($stats['longest_win_streak'])->toBe(3);
    expect($stats['current_streak_type'])->toBe('loss');
    expect($stats['current_streak_count'])->toBe(1);
});

test('it calculates head-to-head statistics between two players accurately', function () {
    $player1 = User::factory()->create();
    $player2 = User::factory()->create();

    // Player 1 wins 2, Player 2 wins 1
    BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
    ]);

    BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
    ]);

    BilliardMatch::factory()->confirmed()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player2->id,
        'loser_id' => $player1->id,
    ]);

    $statsService = app(BilliardStatsService::class);
    $h2h = $statsService->getHeadToHead($player1, $player2);

    expect($h2h['total_matches'])->toBe(3);
    expect($h2h['user1_wins'])->toBe(2);
    expect($h2h['user2_wins'])->toBe(1);
    expect($h2h['user1_win_rate'])->toBe(66.7);
    expect($h2h['user2_win_rate'])->toBe(33.3);
});

test('it identifies top player and loser of the month', function () {
    $winner = User::factory()->create(['name' => 'Chiến Thần']);
    $loser = User::factory()->create(['name' => 'Cây ATM']);

    // 4 matches in current month
    for ($i = 0; $i < 4; $i++) {
        BilliardMatch::factory()->confirmed()->create([
            'player1_id' => $winner->id,
            'player2_id' => $loser->id,
            'winner_id' => $winner->id,
            'loser_id' => $loser->id,
            'played_at' => Carbon::now()->startOfMonth()->addDays($i),
        ]);
    }

    $statsService = app(BilliardStatsService::class);
    $topPlayer = $statsService->getTopPlayer('month');
    $loserOfMonth = $statsService->getLoserOfTheMonth();

    expect($topPlayer['user']->id)->toBe($winner->id);
    expect($topPlayer['wins'])->toBe(4);

    expect($loserOfMonth['user']->id)->toBe($loser->id);
    expect($loserOfMonth['losses'])->toBe(4);
    expect($loserOfMonth['humorous_title'])->not->toBeEmpty();
});
