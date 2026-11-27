<div wire:poll.30s>
    <!-- Top Alert Banner if pending match requires user confirmation -->
    @if($pendingActionMatches->isNotEmpty())
        @foreach($pendingActionMatches as $pendingMatch)
            @php
                $remainingSecs = $pendingMatch->getRemainingSecondsForAutoConfirm();
                $hours = floor($remainingSecs / 3600);
                $mins = floor(($remainingSecs % 3600) / 60);
            @endphp
            <div class="fixed top-16 inset-x-0 z-30 bg-amber-500 text-white px-4 py-2 shadow-sm text-xs font-medium">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span>
                            <b>{{ $pendingMatch->creator->name }}</b> vừa ghi nhận trận đấu bạn thua (Kèo:
                            <b>{{ $pendingMatch->prize }}</b>).
                            Còn <b>{{ $hours }}h {{ $mins }}m</b> trước khi tự động xác nhận!
                        </span>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" wire:click="quickConfirm({{ $pendingMatch->id }})"
                            class="bg-white text-amber-900 font-bold px-2.5 py-1 rounded text-xs hover:bg-gray-100 transition cursor-pointer">
                            Chấp nhận
                        </button>
                        <a href="{{ route('matches.show', $pendingMatch->id) }}"
                            class="bg-amber-600 text-white font-medium px-2.5 py-1 rounded text-xs hover:bg-amber-700 transition">
                            Khiếu nại / Xem
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <!-- Bell Icon and Dropdown -->
    <div class="relative">
        <button type="button" wire:click="toggleDropdown"
            class="relative p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition cursor-pointer"
            title="Thông báo">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                </path>
            </svg>

            @if($unreadCount > 0)
                <span
                    class="absolute top-1 right-1 flex items-center justify-center rounded-full h-4 w-4 bg-red-600 text-[10px] font-bold text-white">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            @endif
        </button>

        @if($isOpen)
            <div class="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl bg-white border border-gray-200 shadow-xl py-2 z-50">
                <div class="flex items-center justify-between px-4 pb-2.5 border-b border-gray-200">
                    <div class="font-bold text-sm text-gray-900 flex items-center gap-2">
                        <span>Thông báo</span>
                        @if($unreadCount > 0)
                            <span
                                class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold">{{ $unreadCount }}</span>
                        @endif
                    </div>
                    <button type="button" wire:click="markAllAsRead"
                        class="text-xs font-medium text-primary hover:underline cursor-pointer">
                        Đánh dấu đã đọc
                    </button>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                    @forelse($notifications as $noti)
                        <div class="p-3.5 hover:bg-gray-50 transition {{ !$noti->is_read ? 'bg-primary-soft/40' : '' }}">
                            <div class="flex items-start justify-between gap-2.5">
                                <a href="{{ $noti->link ? url($noti->link) : '#' }}" class="block flex-1">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $noti->title }}
                                    </div>
                                    <div class="text-xs sm:text-sm text-gray-600 mt-0.5 leading-snug">{{ $noti->message }}</div>
                                    <span
                                        class="text-xs text-gray-400 mt-1 block">{{ $noti->created_at->diffForHumans() }}</span>
                                </a>

                                @if($noti->billiard_match_id && $noti->type === 'match_pending_confirmation')
                                    <button type="button" wire:click="quickConfirm({{ $noti->billiard_match_id }})"
                                        class="px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold shrink-0 cursor-pointer shadow-sm">
                                        Chấp nhận
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">
                            Chưa có thông báo nào.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</div>