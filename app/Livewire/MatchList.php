<?php

namespace App\Livewire;

use App\Models\BilliardMatch;
use App\Models\User;
use App\Services\BilliardMatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MatchList extends Component
{
    use WithPagination;

    public string $status = 'all'; // all, pending, confirmed, disputed, resolved, cancelled

    public string $filterResult = 'all'; // all, my_wins, my_losses

    public ?int $playerId = null;

    public string $search = '';

    public string $timeRange = 'all'; // all, today, week, month

    protected $listeners = ['matchCreated' => '$refresh', 'matchUpdated' => '$refresh'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterResult(): void
    {
        $this->resetPage();
    }

    public function updatingPlayerId(): void
    {
        $this->resetPage();
    }

    public function updatingTimeRange(): void
    {
        $this->resetPage();
    }

    public function quickConfirm(int $matchId, BilliardMatchService $matchService): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $match = BilliardMatch::find($matchId);
        if ($match && $match->status === 'pending') {
            try {
                $matchService->confirmMatch($match, $user, 'opponent');
                session()->flash('success', "Đã xác nhận kết quả trận đấu #{$match->id}!");
            } catch (\Throwable $e) {
                session()->flash('error', $e->getMessage());
            }
        }
    }

    public function render()
    {
        $query = BilliardMatch::with(['creator', 'player1', 'player2', 'winner', 'loser'])
            ->orderBy('played_at', 'desc');

        $currentUser = Auth::user();

        if (! ($currentUser && $currentUser->isAdmin())) {
            $query->where('is_hidden', false);
        }

        // 1. Status Filter
        if ($this->status !== 'all') {
            if ($this->status === 'confirmed') {
                $query->whereIn('status', ['confirmed', 'resolved']);
            } else {
                $query->where('status', $this->status);
            }
        }

        // 2. Player Filter
        if ($this->playerId) {
            $query->where(function ($q) {
                $q->where('player1_id', $this->playerId)
                    ->orWhere('player2_id', $this->playerId);
            });
        }

        // 3. Current User Result Filter
        if ($currentUser && $this->filterResult !== 'all') {
            if ($this->filterResult === 'my_wins') {
                $query->where('winner_id', $currentUser->id);
            } elseif ($this->filterResult === 'my_losses') {
                $query->where('loser_id', $currentUser->id);
            }
        }

        // 4. Time Range Filter
        if ($this->timeRange === 'today') {
            $query->whereDate('played_at', Carbon::today());
        } elseif ($this->timeRange === 'week') {
            $query->where('played_at', '>=', Carbon::now()->startOfWeek());
        } elseif ($this->timeRange === 'month') {
            $query->where('played_at', '>=', Carbon::now()->startOfMonth());
        }

        // 5. Search
        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('prize', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $matches = $query->paginate(10);
        $players = User::where('is_active', true)->orderBy('name')->get();

        return view('livewire.match-list', [
            'matches' => $matches,
            'players' => $players,
        ]);
    }
}
