@if($paginator->hasPages())
    <nav aria-label="Phân trang {{ $paginationLabel }}" class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 px-3 py-2 text-xs">
        <span class="text-gray-600 tabular-nums">Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        <div class="flex items-center gap-1.5">
            @if($paginator->previousPageUrl())
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-2.5 font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-primary">Trang trước</a>
            @else
                <span class="inline-flex min-h-9 items-center px-2.5 text-gray-500" aria-disabled="true">Trang trước</span>
            @endif
            @if($paginator->nextPageUrl())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                    class="inline-flex min-h-9 items-center rounded-lg border border-primary/15 bg-primary-soft px-2.5 font-semibold text-primary hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-primary">Trang sau</a>
            @else
                <span class="inline-flex min-h-9 items-center px-2.5 text-gray-500" aria-disabled="true">Trang sau</span>
            @endif
        </div>
    </nav>
@endif
