@php
    $hasResult = in_array($match->status, ['confirmed', 'resolved'], true);
    [$statusLabel, $statusClass] = match ($match->status) {
        'confirmed' => ['Đã xác nhận', 'bg-green-100 text-green-800'],
        'resolved' => ['Đã xử lý', 'bg-green-100 text-green-800'],
        'pending' => ['Chờ xác nhận', 'bg-amber-100 text-amber-800'],
        'disputed' => ['Đang khiếu nại', 'bg-red-100 text-red-800'],
        'cancelled' => ['Đã hủy', 'bg-gray-100 text-gray-700'],
        default => [$match->status, 'bg-gray-100 text-gray-700'],
    };
    $prizeStatuses = ['unpaid' => 'Chưa trả', 'paid' => 'Chờ xác nhận nhận kèo', 'confirmed' => 'Đã hoàn tất', 'rejected' => 'Cần xử lý lại'];
    $prizePairs = $match->isDoubles()
        ? ['primary' => [$match->loser, $match->winner], 'partner' => [$match->loserPartner, $match->winnerPartner]]
        : [];
@endphp

<article data-match-detail-fragment class="space-y-4">
    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 text-xs font-medium text-gray-600">
        <span class="rounded-full px-2.5 py-1 font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
        <span>#{{ $match->id }}</span>
        <time datetime="{{ $match->played_at->toIso8601String() }}" class="tabular-nums">{{ $match->played_at->format('d/m/Y · H:i') }}</time>
        <span>{{ $match->isDoubles() ? 'Đánh đôi' : 'Đánh đơn' }}</span>
        @if($match->trashed() || $match->isHidden())
            <span class="rounded-full bg-gray-100 px-2 py-1">{{ $match->trashed() ? 'Trong thùng rác' : 'Đang ẩn' }}</span>
        @endif
    </div>

    @include('matches.partials.match-teams', ['match' => $match, 'hasResult' => $hasResult])

    <section aria-labelledby="match-detail-prize-title" class="rounded-xl border border-gray-200 p-3 sm:p-4">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div>
                <h3 id="match-detail-prize-title" class="text-xs font-semibold text-gray-600">Món kèo</h3>
                <p class="mt-0.5 text-base font-bold text-gray-900 wrap-anywhere">{{ $match->prize ?: 'Không có kèo' }}</p>
            </div>
            @if($hasResult && !$match->isDoubles() && $match->prize)
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $match->isPrizeConfirmed() ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $prizeStatuses[$match->getPrizeStatusBySlot()] ?? 'Đang xử lý' }}
                </span>
            @endif
        </div>
        @if($hasResult && $match->prize && $prizePairs)
            <ul class="mt-2 divide-y divide-gray-100 border-t border-gray-100 text-xs text-gray-700">
                @foreach($prizePairs as $slot => [$payer, $recipient])
                    @if($payer && $recipient)
                        <li class="flex flex-wrap items-center justify-between gap-1.5 py-2">
                            <span class="wrap-anywhere">{{ $payer->name }} trả cho {{ $recipient->name }}</span>
                            <span class="font-semibold {{ $match->isPrizeConfirmed($slot) ? 'text-green-800' : 'text-amber-800' }}">{{ $prizeStatuses[$match->getPrizeStatusBySlot($slot)] ?? 'Đang xử lý' }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        @endif
    </section>

    @if(!$hasResult && $match->status === 'pending' && $match->winner_id)
        <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900 wrap-anywhere">Kết quả được ghi nhận: <strong>{{ $match->winner_names }} thắng</strong></p>
    @endif

    @if($match->notes || $match->dispute_reason || $match->evidence_path)
        <div class="space-y-2 text-sm text-gray-700">
            @if($match->notes)
                <p class="wrap-anywhere"><strong>Ghi chú</strong> · {{ $match->notes }}</p>
            @endif
            @if($match->dispute_reason)
                <p class="wrap-anywhere"><strong>Lý do khiếu nại</strong> · {{ $match->dispute_reason }}</p>
            @endif
            @if($match->evidence_path)
                <a href="{{ asset('storage/'.$match->evidence_path) }}" target="_blank" rel="noopener" class="font-semibold text-primary hover:underline">Xem ảnh minh chứng</a>
            @endif
        </div>
    @endif

    <details class="group rounded-xl border border-gray-200">
        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 rounded-xl p-3 text-sm font-semibold text-gray-900 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-primary [&::-webkit-details-marker]:hidden sm:px-4">
            Lịch sử thao tác
            <svg aria-hidden="true" class="size-4 text-gray-600 transition-transform group-open:rotate-180 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
            </svg>
        </summary>
        <ul class="divide-y divide-gray-100 border-t border-gray-100 px-3 text-sm sm:px-4">
            @forelse($match->histories as $entry)
                <li class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1 py-2.5">
                    <span class="min-w-0 flex-1 text-gray-700 wrap-anywhere">{{ $entry->note }}</span>
                    <time datetime="{{ $entry->created_at->toIso8601String() }}" class="shrink-0 text-xs text-gray-600 tabular-nums">{{ $entry->created_at->format('d/m/Y · H:i') }}</time>
                </li>
            @empty
                <li class="py-2.5 text-gray-600">Chưa có thao tác nào.</li>
            @endforelse
        </ul>
    </details>

    @if($match->creator)
        <p class="text-xs text-gray-600">Người tạo: {{ $match->creator->name }}</p>
    @endif
</article>
