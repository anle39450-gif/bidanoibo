<?php

namespace App\Services;

use App\Models\BilliardMatch;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiPlayerAnalysisService
{
    protected ?string $apiKey;

    protected string $model;

    public function __construct(
        protected BilliardStatsService $statsService
    ) {
        $this->apiKey = config('services.openai.api_key') ?: env('API_KEY') ?: env('OPENAI_API_KEY');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    /**
     * Get or generate AI analysis for a player's performance.
     *
     * @return array{
     *     title: string,
     *     assessment: string,
     *     strengths: string,
     *     advice: string,
     *     prediction: string,
     *     generated_at: string,
     *     is_ai: bool
     * }
     */
    public function getAnalysisForUser(User $user, bool $forceRefresh = false): array
    {
        $cacheKey = "player_ai_analysis_{$user->id}";

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($user) {
            $metrics = $this->collectPlayerMetrics($user);

            if (! empty($this->apiKey)) {
                $aiResult = $this->callOpenAiChatGpt($metrics);
                if ($aiResult) {
                    return array_merge($aiResult, [
                        'generated_at' => now()->format('H:i d/m/Y'),
                        'is_ai' => true,
                    ]);
                }
            }

            // Fallback rule-based analysis if OpenAI call is skipped or fails
            return array_merge($this->generateFallbackAnalysis($metrics), [
                'generated_at' => now()->format('H:i d/m/Y'),
                'is_ai' => false,
            ]);
        });
    }

    /**
     * Gather comprehensive stats & metrics for the prompt.
     *
     * @return array<string, mixed>
     */
    public function collectPlayerMetrics(User $user): array
    {
        $stats = $this->statsService->getPlayerStats($user);

        // Recent 5 match results
        $recentMatches = BilliardMatch::confirmed()
            ->where(function ($q) use ($user) {
                $q->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id)
                    ->orWhere('player1_partner_id', $user->id)
                    ->orWhere('player2_partner_id', $user->id);
            })
            ->orderBy('played_at', 'desc')
            ->take(5)
            ->get();

        $recentForm = [];
        foreach ($recentMatches as $m) {
            $isWin = ($m->winner_id === $user->id || $m->winner_partner_id === $user->id);
            $recentForm[] = $isWin ? 'W' : 'L';
        }

        // Top rivals
        $rivalCounts = [];
        foreach ($recentMatches as $m) {
            $opponents = [];
            if ($m->player1_id === $user->id || $m->player1_partner_id === $user->id) {
                if ($m->player2) {
                    $opponents[] = $m->player2->name;
                }
                if ($m->player2Partner) {
                    $opponents[] = $m->player2Partner->name;
                }
            } else {
                if ($m->player1) {
                    $opponents[] = $m->player1->name;
                }
                if ($m->player1Partner) {
                    $opponents[] = $m->player1Partner->name;
                }
            }
            foreach ($opponents as $oppName) {
                $rivalCounts[$oppName] = ($rivalCounts[$oppName] ?? 0) + 1;
            }
        }
        arsort($rivalCounts);
        $topRivals = array_slice(array_keys($rivalCounts), 0, 3);

        return [
            'name' => $user->name,
            'username' => $user->username,
            'total_matches' => $stats['total_matches'] ?? 0,
            'wins' => $stats['wins'] ?? 0,
            'losses' => $stats['losses'] ?? 0,
            'win_rate' => $stats['win_rate'] ?? 0.0,
            'streak_type' => $stats['current_streak_type'] ?? null,
            'streak_count' => $stats['current_streak_count'] ?? 0,
            'recent_form' => implode('-', $recentForm) ?: 'Chưa có trận nào',
            'top_rivals' => implode(', ', $topRivals) ?: 'Chưa có đối thủ thường xuyên',
            'unpaid_debts_count' => $user->unpaid_debts_count,
            'unpaid_receivables_count' => $user->unpaid_receivables_count,
        ];
    }

    /**
     * Call OpenAI ChatGPT API using gpt-4o-mini (cheapest model).
     *
     * @param  array<string, mixed>  $metrics
     * @return array{title: string, assessment: string, strengths: string, advice: string, prediction: string}|null
     */
    protected function callOpenAiChatGpt(array $metrics): ?array
    {
        try {
            $systemPrompt = 'Bạn là AI Huấn Luyện Viên Bida (Billiards AI Coach) chuyên nghiệp kiêm bình luận viên dí dỏm của công ty. '
                .'Nhiệm vụ của bạn là dựa trên các chỉ số thi đấu thực tế của cơ thủ để đưa ra bản nhận định phong độ ngắn gọn, sắc sảo, '
                .'mang tính khích lệ nhưng cũng có chút cà khịa hài hước văn minh. '
                .'Bắt buộc trả về định dạng JSON thuần túy gồm 5 trường: '
                .'1. "title": Biệt danh hoặc danh hiệu phong độ (dưới 10 chữ, ví dụ: "Chiến thần phong độ huỷ diệt", "Cơ thủ đang tìm lại cảm giác bi"). '
                .'2. "assessment": Nhận định tổng quan phong độ và lối đánh (2-3 câu). '
                .'3. "strengths": Điểm mạnh nổi bật (1-2 câu). '
                .'4. "advice": Lời khuyên chiến thuật hoặc mẹo tâm lý (1-2 câu, có thể nhắc trả kèo hoặc mời cà phê nếu đang nợ kèo). '
                .'5. "prediction": Dự báo kết quả trận đấu tiếp theo (1 câu).';

            $userPrompt = "Dưới đây là các chỉ số thi đấu của cơ thủ:\n"
                ."- Tên cơ thủ: {$metrics['name']} (@{$metrics['username']})\n"
                ."- Tổng số trận: {$metrics['total_matches']} trận\n"
                ."- Thắng: {$metrics['wins']} trận | Thua: {$metrics['losses']} trận\n"
                ."- Tỷ lệ thắng (Win Rate): {$metrics['win_rate']}%\n"
                .'- Chuỗi hiện tại: '.($metrics['streak_type'] === 'win' ? "Thắng {$metrics['streak_count']} trận liên tiếp" : ($metrics['streak_type'] === 'loss' ? "Thua {$metrics['streak_count']} trận liên tiếp" : 'Không có chuỗi'))."\n"
                ."- Phong độ 5 trận gần nhất: {$metrics['recent_form']}\n"
                ."- Đối thủ hay gặp: {$metrics['top_rivals']}\n"
                ."- Kèo nợ đang cần trả: {$metrics['unpaid_debts_count']} kèo\n"
                ."- Kèo đối thủ đang nợ bạn: {$metrics['unpaid_receivables_count']} kèo\n\n"
                .'Hãy phân tích và trả về đúng JSON theo yêu cầu.';

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'response_format' => ['type' => 'json_object'],
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['choices'][0]['message']['content'] ?? null;
                if ($content) {
                    $json = json_decode($content, true);
                    if (isset($json['title'], $json['assessment'], $json['strengths'], $json['advice'], $json['prediction'])) {
                        return [
                            'title' => trim((string) $json['title']),
                            'assessment' => trim((string) $json['assessment']),
                            'strengths' => trim((string) $json['strengths']),
                            'advice' => trim((string) $json['advice']),
                            'prediction' => trim((string) $json['prediction']),
                        ];
                    }
                }
            }

            Log::warning('OpenAI API request failed or returned invalid response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('OpenAI Player Analysis Exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Intelligent local fallback analysis when API key is unavailable or offline.
     *
     * @param  array<string, mixed>  $metrics
     * @return array{title: string, assessment: string, strengths: string, advice: string, prediction: string}
     */
    protected function generateFallbackAnalysis(array $metrics): array
    {
        $winRate = (float) $metrics['win_rate'];
        $streakType = $metrics['streak_type'];
        $streakCount = (int) $metrics['streak_count'];
        $totalMatches = (int) $metrics['total_matches'];
        $name = $metrics['name'];

        if ($totalMatches === 0) {
            return [
                'title' => 'Tân Binh Đầy Tiềm Năng',
                'assessment' => "{$name} chưa ghi nhận trận thi đấu chính thức nào trên hệ thống. Đây là thời điểm tuyệt vời để khởi động và khẳng định vị thế trên bảng xếp hạng!",
                'strengths' => 'Sức trẻ và yếu tố bất ngờ sẽ là vũ khí bí mật trước mọi đối thủ lâu năm.',
                'advice' => 'Hãy chủ động gửi một lời khiêu chiến đơn hoặc tham gia đánh đôi để lấy cảm giác bi đầu tiên.',
                'prediction' => 'Trận ra quân đầu tiên sẽ mang lại nhiều cảm xúc và bất ngờ.',
            ];
        }

        if ($winRate >= 70 && $streakType === 'win') {
            return [
                'title' => 'Chiến Thần Đang Ở Đỉnh Cao Phong Độ',
                'assessment' => "Với tỷ lệ thắng ấn tượng {$winRate}% và chuỗi {$streakCount} trận toàn thắng, {$name} đang là nỗi khiếp sợ cho bất kỳ ai bước vào bàn bida.",
                'strengths' => 'Khả năng điều bi chính xác, tâm lý thi đấu thép và tận dụng tối đa sai lầm của đối thủ.',
                'advice' => 'Tiếp tục duy trì sự tập trung tối đa, đừng chủ quan trước những pha bi phòng thủ kín kẽ.',
                'prediction' => 'Khả năng cao sẽ kéo dài chuỗi bất bại nếu giữ vững nhịp thi đấu hiện tại.',
            ];
        }

        if ($winRate >= 50) {
            return [
                'title' => 'Cơ Thủ Bản Lĩnh & Ổn Định',
                'assessment' => "{$name} đang duy trì phong độ vững vàng với tỷ lệ thắng {$winRate}% qua {$totalMatches} trận đấu. Một đối thủ đáng gờm luôn biết cách lật ngược thế cờ.",
                'strengths' => 'Lối chơi linh hoạt, cân bằng tốt giữa tấn công ghi điểm và phòng thủ an toàn.',
                'advice' => 'Cải thiện thêm các pha dọn bàn then chốt và tính toán góc cộng bi để nâng tỷ lệ dứt điểm.',
                'prediction' => 'Sẽ có những trận đấu kịch tính và cơ hội thắng lợi rất sáng cửa ở các ván tới.',
            ];
        }

        return [
            'title' => 'Mãnh Hổ Ẩn Mình Chờ Thời Khắc Bùng Nổ',
            'assessment' => "Dù đang trải qua giai đoạn thử thách với tỷ lệ thắng {$winRate}%, tiềm năng của {$name} là không thể xem thường. Cảm giác bi đang dần quay trở lại qua từng ván đấu.",
            'strengths' => 'Tinh thần kiên trì, không bỏ cuộc và luôn sẵn sàng nhận mọi lời thách đấu sòng phẳng.',
            'advice' => 'Hãy thư giãn cổ tay, ngắm kỹ điểm tiếp xúc bi cái và nhớ trả sòng phẳng các kèo nước để giải xui!',
            'prediction' => 'Một chiến thắng giải tỏa tâm lý đang ở rất gần trong những trận đấu tiếp theo.',
        ];
    }
}
