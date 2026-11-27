<div class="space-y-4">
    @php
        $filterClass = 'min-h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus-visible:outline-2 focus-visible:outline-primary';
        $currentUser = auth()->user();
    @endphp

    <section aria-label="Lọc lịch sử trận đấu" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-xs sm:grid-cols-2 sm:p-4 xl:grid-cols-4">
        <div class="space-y-1.5">
            <label for="history-status" class="block text-sm font-semibold text-gray-700">Trạng thái</label>
            <select id="history-status" wire:model.live="status" class="{{ $filterClass }}">
                <option value="all">Tất cả trạng thái</option>
                <option value="confirmed">Đã xác nhận</option>
                <option value="pending">Chờ xác nhận</option>
                <option value="disputed">Khiếu nại</option>
                <option value="cancelled">Đã hủy</option>
            </select>
        </div>

        <div class="space-y-1.5">
            <label for="history-player" class="block text-sm font-semibold text-gray-700">Cơ thủ</label>
            <select id="history-player" wire:model.live="playerId" class="{{ $filterClass }}">
                <option value="">Tất cả cơ thủ</option>
                @foreach($players as $player)
                    <option value="{{ $player->id }}">{{ $player->name }} ({{ '@'.$player->username }})</option>
                @endforeach
            </select>
        </div>

        @auth
            <div class="space-y-1.5">
                <label for="history-result" class="block text-sm font-semibold text-gray-700">Trận của tôi</label>
                <select id="history-result" wire:model.live="filterResult" class="{{ $filterClass }}">
                    <option value="all">Tất cả trận</option>
                    <option value="my_wins">Trận tôi thắng</option>
                    <option value="my_losses">Trận tôi thua</option>
                </select>
            </div>
        @else
            <div class="space-y-1.5">
                <label for="history-time-range" class="block text-sm font-semibold text-gray-700">Thời gian</label>
                <select id="history-time-range" wire:model.live="timeRange" class="{{ $filterClass }}">
                    <option value="all">Toàn bộ thời gian</option>
                    <option value="today">Hôm nay</option>
                    <option value="week">Tuần này</option>
                    <option value="month">Tháng này</option>
                </select>
            </div>
        @endauth

        <div class="space-y-1.5">
            <label for="history-search" class="block text-sm font-semibold text-gray-700">Tìm kèo hoặc ghi chú</label>
            <input id="history-search" type="search" wire:model.live.debounce.300ms="search"
                placeholder="Nhập từ khóa" class="{{ $filterClass }}">
        </div>
    </section>

    @if($matches->isEmpty())
        <p class="rounded-xl border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-600">
            Không tìm thấy trận đấu phù hợp.
        </p>
    @else
        <ul class="grid items-start gap-3 xl:grid-cols-2">
            @foreach($matches as $game)
                @php
                    $isConfirmed = in_array($game->status, ['confirmed', 'resolved'], true);
                    $isPending = $game->status === 'pending';
                    [$statusLabel, $statusClass] = match ($game->status) {
                        'confirmed' => ['Đã xác nhận', 'bg-green-100 text-green-800'],
                        'resolved' => ['Đã xử lý', 'bg-green-100 text-green-800'],
                        'pending' => ['Chờ xác nhận', 'bg-amber-100 text-amber-800'],
                        'disputed' => ['Khiếu nại', 'bg-red-100 text-red-800'],
                        'cancelled' => ['Đã hủy', 'bg-gray-100 text-gray-700'],
                        default => [$game->status, 'bg-gray-100 text-gray-700'],
                    };
                @endphp
                @include('matches.partials.calendar-match', ['match' => [
                    'history_id' => $game->id,
                    'time' => $game->played_at->format('d/m/Y · H:i'),
                    'status_label' => $statusLabel,
                    'status_class' => $statusClass,
                    'team1_names' => $game->team1_names,
                    'team2_names' => $game->team2_names,
                    'is_confirmed' => $isConfirmed,
                    'is_team1_winner' => $game->winner_id === $game->player1_id || $game->winner_partner_id === $game->player1_id,
                    'is_team2_winner' => $game->winner_id === $game->player2_id || $game->winner_partner_id === $game->player2_id,
                    'prize' => $game->prize,
                    'prize_paid' => $isConfirmed ? $game->isPrizePaid() : null,
                    'remaining_minutes' => $isPending ? intdiv($game->getRemainingSecondsForAutoConfirm(), 60) : null,
                    'can_confirm' => $currentUser && $game->isWaitingForUser($currentUser),
                    'url' => route('matches.show', $game->id),
                ]])
            @endforeach
        </ul>
    @endif

    @if($matches->hasPages())
        <div class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 shadow-xs sm:px-4">
            {{ $matches->links() }}
        </div>
    @endif
</div>
