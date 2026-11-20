<div class="space-y-4">
    <!-- Filter Bar -->
    <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            
            <!-- Trạng thái -->
            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Trạng thái</label>
                <select wire:model.live="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="confirmed">Đã xác nhận (Confirmed)</option>
                    <option value="pending">Chờ xác nhận (Pending)</option>
                    <option value="disputed">Đang tranh chấp (Disputed)</option>
                    <option value="cancelled">Đã hủy (Cancelled)</option>
                </select>
            </div>

            <!-- Cơ thủ -->
            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Cơ thủ</label>
                <select wire:model.live="playerId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Tất cả cơ thủ</option>
                    @foreach($players as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (@<span>{{ $p->username }}</span>)</option>
                    @endforeach
                </select>
            </div>

            <!-- Trận của tôi / Thời gian -->
            @auth
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Trận của tôi</label>
                    <select wire:model.live="filterResult" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Tất cả trận</option>
                        <option value="my_wins">Chỉ trận tôi Thắng</option>
                        <option value="my_losses">Chỉ trận tôi Thua</option>
                    </select>
                </div>
            @else
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Thời gian</label>
                    <select wire:model.live="timeRange" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Toàn bộ thời gian</option>
                        <option value="today">Hôm nay</option>
                        <option value="week">Tuần này</option>
                        <option value="month">Tháng này</option>
                    </select>
                </div>
            @endauth

            <!-- Tìm kiếm -->
            <div>
                <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">Tìm kiếm kèo / ghi chú</label>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Tìm theo tên kèo..." 
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
        </div>
    </div>

    <!-- Match List -->
    <div class="space-y-3">
        @forelse($matches as $m)
            @php
                $isPending = ($m->status === 'pending');
                $isDisputed = ($m->status === 'disputed');
                $isConfirmed = in_array($m->status, ['confirmed', 'resolved']);

                $currentUser = auth()->user();
                $isWaitingForMe = $currentUser ? $m->isWaitingForUser($currentUser) : false;
            @endphp

            <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-5 shadow-sm hover:border-gray-300 transition space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2 text-xs sm:text-sm">
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold
                            {{ $m->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($m->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                            {{ $m->status === 'confirmed' ? 'Đã xác nhận' : ($m->status === 'pending' ? 'Chờ xác nhận' : ($m->status === 'resolved' ? 'Đã xử lý' : 'Khiếu nại')) }}
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-500 font-medium">{{ $m->played_at->format('d/m/Y H:i') }}</span>
                        <span class="text-gray-400">•</span>
                        <span class="text-gray-500">Tạo bởi: <b class="text-gray-800">{{ $m->creator->name }}</b></span>
                    </div>

                    <span class="text-xs sm:text-sm font-medium text-gray-800 bg-gray-100 px-3 py-1 rounded-md self-start sm:self-auto">
                        Kèo: <b>{{ $m->prize }}</b>
                    </span>
                </div>

                <!-- Matchup row -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-y border-gray-100 gap-3">
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-lg font-bold text-gray-900">{{ $m->team1_names }}</span>
                            @if(($m->winner_id === $m->player1_id || $m->winner_partner_id === $m->player1_id) && $isConfirmed)
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-bold">Thắng</span>
                            @endif
                        </div>
                        <span class="text-xs font-black text-gray-400">VS</span>
                        <div class="flex items-center gap-2">
                            <span class="text-base sm:text-lg font-bold text-gray-900">{{ $m->team2_names }}</span>
                            @if(($m->winner_id === $m->player2_id || $m->winner_partner_id === $m->player2_id) && $isConfirmed)
                                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-bold">Thắng</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-auto">
                        @if($isWaitingForMe)
                            <button 
                                type="button" 
                                wire:click="quickConfirm({{ $m->id }})" 
                                class="bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-semibold px-3 py-1.5 rounded-lg transition cursor-pointer"
                            >
                                Xác nhận
                            </button>
                        @endif

                        <a href="{{ route('matches.show', $m->id) }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">
                            Chi tiết →
                        </a>
                    </div>
                </div>

                <!-- Countdown if pending -->
                @if($isPending)
                    @php
                        $remainingSecs = $m->getRemainingSecondsForAutoConfirm();
                        $hours = floor($remainingSecs / 3600);
                        $mins = floor(($remainingSecs % 3600) / 60);
                    @endphp
                    <div class="text-xs sm:text-sm text-amber-800 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                        <span>Tự động xác nhận sau: <b>{{ $hours }}h {{ $mins }}m</b></span>
                        <span class="text-gray-500">Hạn chót: {{ $m->auto_confirm_at?->format('H:i d/m') }}</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white border border-gray-200 rounded-lg p-10 text-center text-sm text-gray-500">
                Không tìm thấy trận đấu nào phù hợp.
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div>
        {{ $matches->links() }}
    </div>
</div>
