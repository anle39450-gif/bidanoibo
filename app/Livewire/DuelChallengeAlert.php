<?php

namespace App\Livewire;

use App\Models\BilliardChallenge;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\BilliardMatchService;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DuelChallengeAlert extends Component
{
    public bool $showSendModal = false;

    public ?int $selectedOpponentId = null;

    public string $challengePrize = 'Kèo cà phê muối';

    public string $challengeMessage = 'Ra bàn solo ngay nếu không muốn bị coi là sợ!';

    public array $quickPrizes = [
        'Kèo cà phê muối',
        'Kèo Rockstar',
        'Kèo trà sữa',
        'Kèo bữa trưa',
        'Kèo tiền bàn',
        'Kèo bia hơi',
    ];

    public ?int $lastAlertChallengeId = null;

    #[On('openSendChallengeModal')]
    public function openSendModal(?int $opponentId = null): void
    {
        $this->selectedOpponentId = $opponentId;
        $this->showSendModal = true;
    }

    public function closeSendModal(): void
    {
        $this->showSendModal = false;
        $this->resetValidation();
    }

    public function sendChallenge(TelegramBotService $telegramBot): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'selectedOpponentId' => ['required', 'integer', 'exists:users,id', 'different:user_id'],
            'challengePrize' => ['required', 'string', 'max:255'],
            'challengeMessage' => ['nullable', 'string', 'max:500'],
        ], [
            'selectedOpponentId.required' => 'Vui lòng chọn cơ thủ muốn thách đấu!',
            'challengePrize.required' => 'Vui lòng nhập món kèo thách đấu!',
        ]);

        if ($this->selectedOpponentId === $user->id) {
            $this->addError('selectedOpponentId', 'Bạn không thể tự khiêu chiến chính mình!');

            return;
        }

        $challenge = BilliardChallenge::create([
            'challenger_id' => $user->id,
            'opponent_id' => $this->selectedOpponentId,
            'prize' => $this->challengePrize,
            'message' => $this->challengeMessage,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(30),
        ]);

        $opponent = User::find($this->selectedOpponentId);

        if ($opponent) {
            UserNotification::create([
                'user_id' => $opponent->id,
                'type' => 'duel_challenge',
                'title' => '🚨 BÁO ĐỘNG KHIÊU CHIẾN!',
                'message' => "{$user->name} vừa gửi tối hậu thư thách đấu bạn với kèo: {$this->challengePrize}!",
                'link' => route('calendar'),
            ]);

            $telegramBot->sendDuelChallengeNotification($challenge);
        }

        $this->showSendModal = false;
        session()->flash('success', "Đã phát lệnh khiêu chiến tới {$opponent?->name}! Màn hình đối thủ sẽ nhấp nháy đỏ SOS!");
    }

    public function acceptChallenge(int $challengeId, BilliardMatchService $matchService, TelegramBotService $telegramBot): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $challenge = BilliardChallenge::where('id', $challengeId)
            ->where('opponent_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $challenge) {
            return;
        }

        $challenge->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $challenger = $challenge->challenger;

        // Auto-create pending match
        try {
            $match = $matchService->createMatch([
                'match_type' => 'singles',
                'player1_id' => $challenge->challenger_id,
                'player2_id' => $user->id,
                'winner_id' => $challenge->challenger_id, // Default creator winning placeholder, waiting opponent update
                'loser_id' => $user->id,
                'played_at' => now(),
                'prize' => $challenge->prize,
                'notes' => 'Trận đấu từ Lời Khiêu Chiến: '.($challenge->message ?: 'Tiếp chiêu trực tiếp'),
                'created_from' => 'challenge',
            ], $challenger ?: $user);

            $challenge->update(['match_id' => $match->id]);
        } catch (\Throwable $e) {
            // Match creation fallback
        }

        // Notify challenger
        if ($challenger) {
            UserNotification::create([
                'user_id' => $challenger->id,
                'type' => 'duel_accepted',
                'title' => '⚔️ ĐỐI THỦ ĐÃ TIẾP CHIÊU!',
                'message' => "{$user->name} đã đồng ý tiếp chiêu lời thách đấu kèo {$challenge->prize} của bạn!",
                'link' => route('calendar'),
            ]);

            if ($challenger->telegram_id) {
                $telegramBot->sendMessage(
                    $challenger->telegram_id,
                    "⚔️ <b>{$user->name} ĐÃ TIẾP CHIÊU THÁCH ĐẤU!</b>\n\n"
                    ."Kèo: <b>{$challenge->prize}</b>\n"
                    .'Đối thủ đã chấp nhận lời khiêu chiến. Ra bàn chiến ngay thôi!'
                );
            }
        }

        session()->flash('success', "Bạn đã dũng cảm TIẾP CHIÊU thách đấu của {$challenger?->name}! Chúc bạn thi đấu thăng hoa!");
    }

    public function declineChallenge(int $challengeId, TelegramBotService $telegramBot): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $challenge = BilliardChallenge::where('id', $challengeId)
            ->where('opponent_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $challenge) {
            return;
        }

        $challenge->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);

        $challenger = $challenge->challenger;

        if ($challenger) {
            UserNotification::create([
                'user_id' => $challenger->id,
                'type' => 'duel_declined',
                'title' => '🏳️ ĐỐI THỦ ĐÃ TỪ CHỐI!',
                'message' => "{$user->name} đã từ chối lời khiêu chiến kèo {$challenge->prize} của bạn.",
                'link' => route('calendar'),
            ]);

            if ($challenger->telegram_id) {
                $telegramBot->sendMessage(
                    $challenger->telegram_id,
                    "🏳️ <b>ĐỐI THỦ ĐÃ TỪ CHỐI THÁCH ĐẤU!</b>\n\n"
                    ."{$user->name} đã từ chối lời khiêu chiến kèo <i>{$challenge->prize}</i> của bạn."
                );
            }
        }

        session()->flash('error', "Bạn đã từ chối lời khiêu chiến của {$challenger?->name}!");
    }

    public function render()
    {
        $user = Auth::user();

        $incomingChallenge = null;
        $usersList = collect();

        if ($user) {
            $incomingChallenge = BilliardChallenge::where('opponent_id', $user->id)
                ->where('status', 'pending')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->with('challenger')
                ->latest()
                ->first();

            $usersList = User::where('is_active', true)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get();
        }

        return view('livewire.duel-challenge-alert', [
            'incomingChallenge' => $incomingChallenge,
            'usersList' => $usersList,
        ]);
    }
}
