<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\BilliardMatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateMatchModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public string $matchType = 'singles'; // 'singles' or 'doubles'

    public ?int $opponentId = null;

    public ?int $teammateId = null; // In doubles: teammate of current user (team 1)

    public ?int $opponent2Id = null; // In doubles: second opponent (team 2)

    public string $winnerChoice = 'me'; // 'me' or 'opponent'

    public string $prize = 'Kèo cà phê muối';

    public string $notes = '';

    public ?string $playedAtDate = null;

    public $evidence = null;

    public array $quickPrizes = [
        'Kèo cà phê muối',
        'Kèo Rockstar',
        'Kèo trà sữa',
        'Kèo ăn uống',
        'Kèo bao thuốc',
        'Kèo vui không thưởng',
    ];

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'openCreateMatchModal' => 'openModal',
    ];

    public function mount(): void
    {
        $this->playedAtDate = Carbon::now()->format('Y-m-d\TH:i');
    }

    #[On('openCreateMatchModal')]
    public function openModal(?int $opponentId = null): void
    {
        $this->isOpen = true;
        if ($opponentId) {
            $this->opponentId = $opponentId;
        }
        $this->playedAtDate = Carbon::now()->format('Y-m-d\TH:i');
        $this->resetErrorBag();
    }

    public function setMatchType(string $type): void
    {
        $this->matchType = in_array($type, ['singles', 'doubles']) ? $type : 'singles';
        $this->resetErrorBag();
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->reset(['opponentId', 'teammateId', 'opponent2Id', 'notes', 'evidence']);
        $this->matchType = 'singles';
        $this->winnerChoice = 'me';
        $this->prize = 'Kèo cà phê muối';
    }

    public function selectPrize(string $prize): void
    {
        $this->prize = $prize;
    }

    public function save(BilliardMatchService $matchService): void
    {
        $currentUser = Auth::user();
        if (! $currentUser) {
            $this->addError('auth', 'Vui lòng đăng nhập để ghi nhận trận đấu!');

            return;
        }

        if ($this->matchType === 'doubles') {
            $this->validate([
                'teammateId' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id])],
                'opponentId' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id, (int) $this->teammateId])],
                'opponent2Id' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id, (int) $this->teammateId, (int) $this->opponentId])],
                'winnerChoice' => ['required', 'in:me,opponent'],
                'prize' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'evidence' => ['nullable', 'image', 'max:10240'],
            ], [
                'teammateId.required' => 'Vui lòng chọn đồng đội của bạn!',
                'teammateId.not_in' => 'Đồng đội không thể là chính bạn!',
                'opponentId.required' => 'Vui lòng chọn đối thủ 1!',
                'opponentId.not_in' => 'Đối thủ không thể trùng với bạn hoặc đồng đội!',
                'opponent2Id.required' => 'Vui lòng chọn đối thủ 2!',
                'opponent2Id.not_in' => 'Đối thủ 2 không thể trùng với các người chơi khác!',
                'prize.required' => 'Vui lòng nhập phần thưởng hoặc chọn kèo!',
            ]);

            $player1Id = $currentUser->id;
            $player1PartnerId = (int) $this->teammateId;
            $player2Id = (int) $this->opponentId;
            $player2PartnerId = (int) $this->opponent2Id;

            if ($this->winnerChoice === 'me') {
                $winnerId = $player1Id;
                $winnerPartnerId = $player1PartnerId;
                $loserId = $player2Id;
                $loserPartnerId = $player2PartnerId;
            } else {
                $winnerId = $player2Id;
                $winnerPartnerId = $player2PartnerId;
                $loserId = $player1Id;
                $loserPartnerId = $player1PartnerId;
            }
        } else {
            $this->validate([
                'opponentId' => ['required', 'exists:users,id', Rule::notIn([$currentUser->id])],
                'winnerChoice' => ['required', 'in:me,opponent'],
                'prize' => ['required', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'evidence' => ['nullable', 'image', 'max:10240'],
            ], [
                'opponentId.required' => 'Vui lòng chọn đối thủ!',
                'opponentId.not_in' => 'Không thể tạo trận đấu với chính mình!',
                'prize.required' => 'Vui lòng nhập phần thưởng hoặc chọn kèo!',
            ]);

            $player1Id = $currentUser->id;
            $player1PartnerId = null;
            $player2Id = (int) $this->opponentId;
            $player2PartnerId = null;

            $winnerId = ($this->winnerChoice === 'me') ? $player1Id : $player2Id;
            $winnerPartnerId = null;
            $loserId = ($winnerId === $player1Id) ? $player2Id : $player1Id;
            $loserPartnerId = null;
        }

        $evidencePath = null;
        if ($this->evidence) {
            $evidencePath = $this->evidence->store('evidence', 'public');
        }

        try {
            $match = $matchService->createMatch([
                'match_type' => $this->matchType,
                'player1_id' => $player1Id,
                'player1_partner_id' => $player1PartnerId,
                'player2_id' => $player2Id,
                'player2_partner_id' => $player2PartnerId,
                'winner_id' => $winnerId,
                'winner_partner_id' => $winnerPartnerId,
                'loser_id' => $loserId,
                'loser_partner_id' => $loserPartnerId,
                'played_at' => $this->playedAtDate ? Carbon::parse($this->playedAtDate) : Carbon::now(),
                'prize' => $this->prize,
                'notes' => $this->notes,
                'evidence_path' => $evidencePath,
                'created_from' => 'web',
            ], $currentUser);

            session()->flash('success', "Ghi nhận trận đấu #{$match->id} thành công! Đã gửi thông báo xác nhận đến đối thủ.");
            $this->closeModal();
            $this->dispatch('matchCreated');

            // Redirect to match detail or refresh
            $this->redirect(route('matches.show', $match->id));
        } catch (\Throwable $e) {
            $this->addError('submit', $e->getMessage());
        }
    }

    public function getCurrentUserIdProperty(): ?int
    {
        return Auth::id();
    }

    public function render()
    {
        $opponents = User::when(Auth::id(), fn ($q) => $q->where('id', '!=', Auth::id()))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.create-match-modal', [
            'opponents' => $opponents,
        ]);
    }
}
