<?php

use App\Models\BilliardChallenge;
use App\Models\BilliardMatch;
use App\Models\TelegramBotConfig;
use App\Models\User;
use App\Services\OpenAiPlayerAnalysisService;
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

test('challenge calculates matchup analysis and win prediction percentage', function () {
    $player1 = User::factory()->create(['name' => 'Lương', 'username' => 'luong']);
    $player2 = User::factory()->create(['name' => 'Thọ', 'username' => 'tho']);

    // Create matches where Player 1 has 3 wins, Player 2 has 1 win
    BilliardMatch::factory()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'confirmed',
        'played_at' => now()->subDays(3),
    ]);

    BilliardMatch::factory()->create([
        'player1_id' => $player1->id,
        'player2_id' => $player2->id,
        'winner_id' => $player1->id,
        'loser_id' => $player2->id,
        'status' => 'confirmed',
        'played_at' => now()->subDays(2),
    ]);

    $challenge = BilliardChallenge::create([
        'challenger_id' => $player1->id,
        'opponent_id' => $player2->id,
        'prize' => '1 cốc cà phê',
        'status' => 'accepted',
        'accepted_at' => now(),
    ]);

    $analysis = $challenge->getMatchupAnalysis();

    expect($analysis)->toHaveKeys(['challenger', 'opponent', 'h2h', 'prediction']);
    expect($analysis['prediction']['challenger_pct'] + $analysis['prediction']['opponent_pct'])->toBe(100);
    // Player 1 has 2-0 H2H against Player 2, so challenger_pct should be favored (> 50%)
    expect($analysis['prediction']['challenger_pct'])->toBeGreaterThan($analysis['prediction']['opponent_pct']);
    expect($analysis['h2h']['challenger_wins'])->toBe(2);
    expect($analysis['h2h']['opponent_wins'])->toBe(0);
});

test('openai player analysis service uses gpt-4o-mini and returns valid structure', function () {
    $user = User::factory()->create(['name' => 'Đạt Cơ Thủ', 'username' => 'dat']);
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Thiện Xạ Phong Độ Thăng Hoa',
                            'assessment' => 'Đạt đang thể hiện lối đánh vô cùng chắc chắn và bản lĩnh.',
                            'strengths' => 'Khả năng điều bi cái chuẩn xác và tâm lý thi đấu điềm tĩnh.',
                            'advice' => 'Tiếp tục duy trì phong độ hiện tại và chuẩn bị thể lực tốt.',
                            'prediction' => 'Sẽ có một chiến thắng áp đảo trong ván đấu tiếp theo.',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    /** @var OpenAiPlayerAnalysisService $service */
    $service = app(OpenAiPlayerAnalysisService::class);
    $result = $service->getAnalysisForUser($user, forceRefresh: true);

    expect($result)->toHaveKeys(['title', 'assessment', 'strengths', 'advice', 'prediction', 'is_ai', 'generated_at']);
    expect($result['title'])->toBe('Thiện Xạ Phong Độ Thăng Hoa');
    expect($result['is_ai'])->toBeTrue();

    // Verify OpenAI request payload
    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request['model'] === 'gpt-4o-mini'
            && isset($request['response_format']);
    });
});

test('openai player analysis service falls back gracefully when api fails or offline', function () {
    $user = User::factory()->create(['name' => 'Tuấn Tân Binh', 'username' => 'tuan']);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'Rate limit exceeded'], 429),
    ]);

    /** @var OpenAiPlayerAnalysisService $service */
    $service = app(OpenAiPlayerAnalysisService::class);
    $result = $service->getAnalysisForUser($user, forceRefresh: true);

    expect($result)->toHaveKeys(['title', 'assessment', 'strengths', 'advice', 'prediction', 'is_ai']);
    expect($result['is_ai'])->toBeFalse();
    expect($result['title'])->not->toBeEmpty();
});

test('dashboard hides ai suggestions while the analysis endpoint remains available', function () {
    $user = User::factory()->create(['name' => 'An', 'username' => 'an']);
    config(['services.openai.api_key' => 'test-key']);

    Http::fake([
        'https://api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Cơ Thủ Chiến Lược',
                            'assessment' => 'Phong độ rất ổn định qua các trận gần đây.',
                            'strengths' => 'Đường bi sắc nét.',
                            'advice' => 'Giữ vững nhịp độ thi đấu.',
                            'prediction' => 'Chiến thắng trong tầm tay.',
                        ]),
                    ],
                ],
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk()
        ->assertDontSee('Gợi ý cải thiện (AI)')
        ->assertDontSee('Phong độ rất ổn định qua các trận gần đây.');
    Http::assertNothingSent();

    $this->postJson('/dashboard/ai-analysis/refresh', ['refresh' => false])
        ->assertOk()
        ->assertJsonPath('analysis.title', 'Cơ Thủ Chiến Lược');
    $this->postJson('/dashboard/ai-analysis/refresh', ['refresh' => false])->assertOk();
    Http::assertSentCount(1);

    $this->postJson('/dashboard/ai-analysis/refresh')
        ->assertOk()
        ->assertJsonPath('analysis.title', 'Cơ Thủ Chiến Lược');
    Http::assertSentCount(2);
});
