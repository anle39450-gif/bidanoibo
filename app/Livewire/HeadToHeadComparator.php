<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\BilliardStatsService;
use Illuminate\View\View;
use Livewire\Component;

class HeadToHeadComparator extends Component
{
    public User $member;

    public int $selectedOpponentId = 0;

    public function mount(User $member, ?int $defaultOpponentId = null): void
    {
        $this->member = $member;

        $this->selectedOpponentId = $defaultOpponentId ?? 0;
    }

    public function selectOpponent(int $opponentId): void
    {
        $this->selectedOpponentId = $opponentId;
    }

    public function render(BilliardStatsService $statsService): View
    {
        $opponents = User::where('id', '!=', $this->member->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedOpponent = $this->selectedOpponentId ? $opponents->find($this->selectedOpponentId) : null;
        $h2hData = $selectedOpponent ? $statsService->getHeadToHead($this->member, $selectedOpponent) : null;

        return view('livewire.head-to-head-comparator', [
            'opponents' => $opponents,
            'selectedOpponent' => $selectedOpponent,
            'h2hData' => $h2hData,
        ]);
    }
}
