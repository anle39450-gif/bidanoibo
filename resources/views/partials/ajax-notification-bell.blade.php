<div x-data="{
    open: false,
    unreadCount: 0,
    notifications: [],
    pendingActionMatches: [],
    async fetchNotifications() {
        try {
            const res = await fetch('{{ route('notifications.index') }}', { credentials: 'same-origin' });
            if (res.ok) {
                const data = await res.json();
                this.unreadCount = data.unreadCount;
                this.notifications = data.notifications;
                this.pendingActionMatches = data.pendingActionMatches;
            }
        } catch(e) {}
    },
    async markAllAsRead() {
        try {
            const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            await fetch('{{ route('notifications.mark-read') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            this.unreadCount = 0;
            this.notifications.forEach(n => n.is_read = true);
        } catch(e) {}
    },
    async quickConfirmMatch(matchId) {
        try {
            const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            const res = await fetch(`/matches/${matchId}/confirm`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data.success) {
                this.pendingActionMatches = this.pendingActionMatches.filter(m => m.id !== matchId);
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi xác nhận');
            }
        } catch(e) {}
    }
}" x-init="fetchNotifications(); setInterval(() => fetchNotifications(), 30000);">

    <!-- Top Alert Banner if pending match requires user confirmation -->
    <template x-if="pendingActionMatches.length > 0">
        <div>
            <template x-for="pm in pendingActionMatches" :key="pm.id">
                <div class="fixed top-16 inset-x-0 z-30 bg-amber-500 text-white px-4 py-2 shadow-sm text-xs font-medium">
                    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span>
                                <b x-text="pm.creator_name"></b> vừa ghi nhận trận đấu bạn thua (Kèo:
                                <b x-text="pm.prize"></b>).
                                Còn <b x-text="pm.hours + 'h ' + pm.mins + 'm'"></b> trước khi tự động xác nhận!
                            </span>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <button type="button" @click="quickConfirmMatch(pm.id)"
                                class="bg-white text-amber-900 font-bold px-2.5 py-1 rounded text-xs hover:bg-gray-100 transition cursor-pointer">
                                Chấp nhận
                            </button>
                            <a :href="'/matches/' + pm.id"
                                class="bg-amber-600 text-white font-medium px-2.5 py-1 rounded text-xs hover:bg-amber-700 transition">
                                Khiếu nại / Xem
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </template>

    <!-- Bell Icon and Dropdown -->
    <div class="relative">
        <button type="button" @click="open = !open; if(open) markAllAsRead();"
            class="relative p-1.5 text-gray-400 hover:text-gray-600 rounded-md hover:bg-gray-100 transition cursor-pointer"
            title="Thông báo">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                </path>
            </svg>

            <span x-show="unreadCount > 0" style="display: none;"
                x-text="unreadCount > 9 ? '9+' : unreadCount"
                class="absolute top-1 right-1 flex items-center justify-center rounded-full h-4 w-4 bg-red-600 text-[10px] font-bold text-white">
            </span>
        </button>

        <div x-show="open" @click.away="open = false" style="display: none;"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl bg-white border border-gray-200 shadow-xl py-2 z-50">
            <div class="flex items-center justify-between px-4 pb-2.5 border-b border-gray-200">
                <div class="font-bold text-sm text-gray-900 flex items-center gap-2">
                    <span>Thông báo</span>
                    <span x-show="unreadCount > 0" x-text="unreadCount"
                        class="px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-xs font-bold"></span>
                </div>
                <button type="button" @click="markAllAsRead()"
                    class="text-xs font-medium text-blue-600 hover:underline cursor-pointer">
                    Đánh dấu đã đọc
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                <template x-if="notifications.length === 0">
                    <div class="p-6 text-center text-sm text-gray-500">
                        Chưa có thông báo nào.
                    </div>
                </template>
                <template x-for="noti in notifications" :key="noti.id">
                    <div class="p-3.5 hover:bg-gray-50 transition" :class="!noti.is_read ? 'bg-blue-50/40' : ''">
                        <div class="flex items-start justify-between gap-2.5">
                            <a :href="noti.link" class="block flex-1">
                                <div class="text-sm font-semibold text-gray-900" x-text="noti.title"></div>
                                <div class="text-xs sm:text-sm text-gray-600 mt-0.5 leading-snug" x-text="noti.message"></div>
                                <span class="text-xs text-gray-400 mt-1 block" x-text="noti.created_at"></span>
                            </a>
                            <template x-if="noti.match_id && noti.is_pending_match">
                                <button type="button" @click="quickConfirmMatch(noti.match_id)"
                                    class="px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-semibold shrink-0 cursor-pointer shadow-sm">
                                    Chấp nhận
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>