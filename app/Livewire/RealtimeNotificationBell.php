<?php

namespace App\Livewire;

use App\Models\BilliardMatch;
use App\Models\UserNotification;
use App\Services\BilliardMatchService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RealtimeNotificationBell extends Component
{
    public int $unreadCount = 0;

    public bool $isOpen = false;

    public function toggleDropdown(): void
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen) {
            $this->markAllAsRead();
        }
    }

    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            UserNotification::where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => Carbon::now()]);
            $this->unreadCount = 0;
        }
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
                $this->dispatch('matchUpdated');
            } catch (\Throwable $e) {
                session()->flash('error', $e->getMessage());
            }
        }
    }

    public function render()
    {
        $user = Auth::user();

        if (! $user) {
            return view('livewire.realtime-notification-bell', [
                'notifications' => collect(),
                'pendingActionMatches' => collect(),
            ]);
        }

        $this->unreadCount = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $notifications = UserNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Check if there are any matches where user is opponent and status is pending
        $pendingActionMatches = BilliardMatch::where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->where('player1_id', $user->id)
                    ->orWhere('player2_id', $user->id);
            })
            ->with(['creator', 'winner', 'loser'])
            ->get()
            ->filter(fn ($m) => $m->isWaitingForUser($user));

        return view('livewire.realtime-notification-bell', [
            'notifications' => $notifications,
            'pendingActionMatches' => $pendingActionMatches,
        ]);
    }
}
