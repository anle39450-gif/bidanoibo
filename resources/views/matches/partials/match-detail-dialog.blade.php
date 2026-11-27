<dialog id="match-detail-dialog" aria-labelledby="match-detail-title" data-match-route="{{ route('matches.show', ['match' => '__match__']) }}"
    class="m-auto w-[calc(100%-1.5rem)] max-w-[42rem] overflow-hidden rounded-2xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-gray-950/55 motion-safe:open:animate-match-dialog motion-safe:backdrop:animate-match-backdrop">
    <div class="flex max-h-[min(46rem,calc(100dvh-1.5rem))] flex-col">
        <header class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 sm:px-5">
            <h2 id="match-detail-title" class="text-lg font-bold text-gray-900">Chi tiết trận đấu</h2>
            <form method="dialog">
                <button type="submit" autofocus aria-label="Đóng chi tiết trận đấu"
                    class="grid size-9 cursor-pointer place-items-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">
                    <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </form>
        </header>

        <div data-match-detail-scroll class="min-h-0 overflow-y-auto overscroll-contain p-4 sm:p-5">
            <p data-match-detail-loading role="status" hidden class="py-10 text-center text-sm text-gray-600">Đang tải chi tiết trận đấu…</p>
            <div data-match-detail-error hidden class="space-y-3 py-8 text-center text-sm">
                <p role="alert" class="text-gray-700">Không tải được chi tiết trận đấu.</p>
                <button type="button" data-match-detail-retry class="min-h-9 cursor-pointer rounded-lg bg-primary-soft px-3 font-semibold text-primary hover:bg-red-100 focus-visible:outline-2 focus-visible:outline-primary">Thử lại</button>
            </div>
            <div data-match-detail-content></div>
        </div>

        <footer class="flex shrink-0 justify-end border-t border-gray-100 px-4 py-3 sm:px-5">
            <a data-full-match-page class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2 text-sm font-semibold text-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary">
                Mở trang đầy đủ <span aria-hidden="true">→</span>
            </a>
        </footer>
    </div>
</dialog>
