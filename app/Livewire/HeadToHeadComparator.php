<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\BilliardStatsService;
use Livewire\Component;

class HeadToHeadComparator extends Component
{
    public User $member;

    public ?int $selectedOpponentId = null;

    public function mount(User $member, ?int $defaultOpponentId = null): void
    {
        $this->member = $member;

        if ($defaultOpponentId) {
            $this->selectedOpponentId = $defaultOpponentId;
        } else {
            // Pick first opponent available
            $first = User::where('id', '!=', $member->id)->where('is_active', true)->first();
            $this->selectedOpponentId = $first?->id;
        }
    }

    public function selectOpponent(int $opponentId): void
    {
        $this->selectedOpponentId = $opponentId;
    }

    public function render(BilliardStatsService $statsService)
    {
        $opponents = User::where('id', '!=', $this->member->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedOpponent = $this->selectedOpponentId ? User::find($this->selectedOpponentId) : null;
        $h2hData = $selectedOpponent ? $statsService->getHeadToHead($this->member, $selectedOpponent) : null;

        return view('livewire.head-to-head-comparator', [
            'opponents' => $opponents,
            'selectedOpponent' => $selectedOpponent,
            'h2hData' => $h2hData,
        ]);
    }
}
