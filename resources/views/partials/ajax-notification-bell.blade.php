<div x-data="{
    open: false,
    unreadCount: 0,
    notifications: [],
    loading: false,
    loaded: false,
    markingRead: false,
    loadError: false,
    markError: false,
    confirmingId: null,
    confirmMessage: '',
    confirmError: false,
    pollTimer: null,
    init() {
        this.fetchNotifications();
        this.pollTimer = setInterval(() => this.fetchNotifications(), 30000);
    },
    destroy() {
        clearInterval(this.pollTimer);
    },
    closePanel(restoreFocus = false) {
        this.open = false;
        if (restoreFocus) this.$refs.bell.focus();
    },
    async fetchNotifications() {
        if (this.loading || this.markingRead || this.confirmingId !== null) return;
        this.loading = true;
        try {
            const res = await fetch('{{ route('notifications.index') }}', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            this.unreadCount = data.unreadCount;
            this.notifications = data.notifications;
            this.loaded = true;
            this.loadError = false;
        } catch {
            this.loadError = true;
        } finally {
            this.loading = false;
        }
    },
    async markAllAsRead() {
        if (this.markingRead || this.loading || this.confirmingId !== null || !this.unreadCount) return;
        this.markingRead = true;
        this.markError = false;
        try {
            const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            const res = await fetch('{{ route('notifications.mark-read') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error();
            this.unreadCount = 0;
            this.notifications.forEach(n => n.is_read = true);
        } catch {
            this.markError = true;
        } finally {
            this.markingRead = false;
        }
    },
    async quickConfirmMatch(notification) {
        if (!notification.can_confirm || this.loading || this.markingRead || this.confirmingId !== null) return;

        this.confirmingId = notification.match_id;
        let refresh = false;
        try {
            const confirmed = await window.confirmSiteAction({
                title: 'Chấp nhận kết quả?',
                text: notification.confirm_text,
                buttons: ['Quay lại', 'Chấp nhận'],
            });
            if (!confirmed) return;

            refresh = true;
            this.confirmMessage = '';
            this.confirmError = false;
            const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
            const res = await fetch(notification.confirm_url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                this.confirmError = true;
                this.confirmMessage = data.message || 'Chưa xác nhận được kết quả. Vui lòng thử lại.';
                return;
            }

            this.notifications.forEach((item) => {
                if (item.match_id !== notification.match_id || !item.is_pending_match) return;
                if (!item.is_read) this.unreadCount = Math.max(0, this.unreadCount - 1);
                item.is_read = true;
                item.can_confirm = false;
                item.title = 'Cập nhật kết quả trận đấu';
                item.message = 'Trận #' + notification.match_id + ' · Đã xác nhận.';
            });
            this.confirmMessage = 'Đã xác nhận kết quả trận đấu.';
            this.$nextTick(() => { if (this.open) this.$refs.heading.focus({ preventScroll: true }); });
            window.Livewire?.dispatch?.('matchUpdated');
        } catch {
            this.confirmError = true;
            this.confirmMessage = 'Chưa xác nhận được kết quả. Vui lòng thử lại.';
        } finally {
            this.confirmingId = null;
            if (refresh) this.fetchNotifications();
        }
    }
}">

    <div class="relative" @click.outside="if (confirmingId === null) closePanel()" @keydown.escape="if (open && confirmingId === null) { $event.stopPropagation(); closePanel(true); }">
        <button type="button" x-ref="bell" @click="open = !open; if (open) fetchNotifications()"
            :aria-expanded="open" aria-controls="notification-panel"
            :aria-label="unreadCount ? 'Thông báo, chưa đọc ' + unreadCount : 'Thông báo'"
            :class="open ? 'border-primary/15 bg-primary-soft text-primary' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-primary'"
            class="relative grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg border transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                </path>
            </svg>

            <span x-show="unreadCount > 0" style="display: none;" aria-hidden="true"
                x-text="unreadCount > 9 ? '9+' : unreadCount"
                class="absolute -top-1.5 -right-1.5 grid h-4.5 min-w-4.5 place-items-center rounded-full bg-primary px-1 text-[10px] font-bold text-white ring-2 ring-white">
            </span>
        </button>

        <section id="notification-panel" aria-labelledby="notification-heading" x-show="open" style="display: none;"
            x-transition.opacity.duration.150ms
            class="fixed inset-x-3 top-16 z-50 mt-2 flex max-h-[min(30rem,calc(100dvh-5.5rem))] flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl sm:absolute sm:inset-x-auto sm:top-full sm:right-0 sm:w-96">
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-100 bg-gray-50/75 px-3.5 py-2.5 sm:px-4">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 id="notification-heading" x-ref="heading" tabindex="-1" class="text-sm font-bold text-gray-900 outline-none">Thông báo</h2>
                    <span x-show="unreadCount > 0" x-text="unreadCount"
                        class="rounded-full bg-primary-soft px-2 py-0.5 text-[11px] font-semibold text-primary tabular-nums"></span>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" @click="markAllAsRead()" :disabled="!unreadCount || loading || markingRead || confirmingId !== null"
                        class="min-h-9 cursor-pointer rounded-lg px-2 text-xs font-semibold text-primary transition hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary disabled:cursor-default disabled:text-gray-500 disabled:hover:bg-transparent">
                        <span x-text="markingRead ? 'Đang đánh dấu…' : 'Đọc tất cả'"></span>
                    </button>
                    <button type="button" @click="closePanel(true)" aria-label="Đóng thông báo"
                        class="grid size-9 cursor-pointer place-items-center rounded-lg text-gray-500 transition hover:bg-gray-200/70 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-primary">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>
            </div>

            <p x-show="markError" role="alert" class="border-b border-red-100 bg-primary-soft px-4 py-2 text-xs text-primary">
                Chưa thể đánh dấu đã đọc. Vui lòng thử lại.
            </p>

            <div aria-live="polite">
                <p x-show="confirmMessage" x-text="confirmMessage"
                    :class="confirmError ? 'bg-primary-soft text-primary' : 'bg-green-50 text-green-800'"
                    class="border-b border-gray-100 px-4 py-2 text-xs font-medium"></p>
            </div>

            <template x-if="!loaded && !loadError">
                <p role="status" class="px-4 py-10 text-center text-sm text-gray-600">Đang tải thông báo…</p>
            </template>
            <template x-if="loadError && !loaded">
                <div class="flex flex-col items-center gap-2 px-4 py-8 text-center text-sm text-gray-600">
                    <p role="status">Không tải được thông báo.</p>
                    <button type="button" @click="fetchNotifications()" :disabled="loading"
                        class="min-h-9 cursor-pointer rounded-lg px-3 font-semibold text-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-60">
                        Thử lại
                    </button>
                </div>
            </template>
            <template x-if="loaded && notifications.length === 0">
                <div class="flex flex-col items-center gap-3 px-4 py-10 text-center text-sm font-medium text-gray-600">
                    <div class="grid size-10 place-items-center rounded-full bg-primary-soft text-primary">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13 6 5h12l3 8v5a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1v-5Zm0 0h5l2 3h4l2-3h5" />
                        </svg>
                    </div>
                    <p>Chưa có thông báo nào.</p>
                </div>
            </template>

            <ul x-show="notifications.length > 0"
                class="min-h-0 space-y-2 overflow-y-auto overscroll-contain p-2.5 [scrollbar-width:thin]">
                <template x-for="noti in notifications" :key="noti.id">
                    <li class="flex items-start gap-2.5 rounded-xl border p-2.5 transition sm:gap-3 sm:p-3"
                        :class="noti.is_read ? 'border-gray-100 bg-white hover:bg-gray-50' : 'border-primary/10 bg-primary-soft/60 hover:bg-primary-soft'">
                        <span aria-hidden="true" class="grid size-8 shrink-0 place-items-center rounded-lg"
                            :class="noti.can_confirm ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600'">
                            <svg x-show="noti.can_confirm" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="12" cy="12" r="9" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2" />
                            </svg>
                            <svg x-show="!noti.can_confirm" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h10M7 11h10m-10 4h6M5 3h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H8l-5 2V5a2 2 0 0 1 2-2Z" />
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a :href="noti.link" class="block rounded-sm wrap-anywhere focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                <div class="flex items-baseline justify-between gap-2">
                                    <span class="text-sm leading-snug font-semibold text-gray-900" x-text="noti.title"></span>
                                    <span x-show="!noti.is_read" class="size-2 shrink-0 self-start mt-1.5 rounded-full bg-primary" aria-hidden="true"></span>
                                    <span x-show="!noti.is_read" class="sr-only">Chưa đọc</span>
                                </div>
                                <p class="mt-1 text-[13px] leading-relaxed text-gray-600" x-text="noti.message"></p>
                            </a>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                                <span class="text-xs font-medium text-gray-500" x-text="noti.created_at"></span>
                                <template x-if="noti.can_confirm">
                                    <div class="ml-auto flex flex-wrap items-center gap-1.5">
                                        <a :href="noti.link" class="inline-flex min-h-8 items-center rounded-lg px-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">Xem trận</a>
                                        <button type="button" @click="quickConfirmMatch(noti)"
                                            :aria-disabled="loading || markingRead || confirmingId !== null"
                                            class="min-h-8 cursor-pointer rounded-lg bg-primary px-2.5 text-xs font-semibold text-white hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary aria-disabled:cursor-wait aria-disabled:opacity-60">
                                            Chấp nhận
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>
        </section>
    </div>
</div>
