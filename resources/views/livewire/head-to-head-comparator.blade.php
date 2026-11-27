<section aria-labelledby="head-to-head-heading" class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
    <div class="space-y-2.5 border-b border-gray-100 px-4 py-3.5 sm:px-5">
        <h2 id="head-to-head-heading" class="text-base font-bold text-gray-900">Đối đầu</h2>

        <select wire:model.live="selectedOpponentId" aria-label="Chọn đối thủ để so sánh" @disabled($opponents->isEmpty())
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary disabled:bg-gray-50 disabled:text-gray-500">
            <option value="0" @selected($selectedOpponentId === 0) disabled>Chọn đối thủ</option>
            @foreach($opponents as $opponent)
                <option value="{{ $opponent->id }}">{{ $opponent->name }} ({{ '@'.$opponent->username }})</option>
            @endforeach
        </select>
    </div>

    @if($opponents->isEmpty())
        <p class="px-4 py-4 text-sm text-gray-600 sm:px-5">Chưa có cơ thủ để so sánh.</p>
    @elseif($selectedOpponent && $h2hData && $h2hData['total_matches'] > 0)
        <div class="space-y-3 px-4 py-4 sm:px-5">
            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 rounded-lg bg-gray-50 px-2 py-3 text-center tabular-nums">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-900 wrap-anywhere">{{ $member->name }}</div>
                    <div class="mt-1 text-sm font-bold text-primary">{{ $h2hData['user1_wins'] }} thắng</div>
                </div>
                <span class="text-xs text-gray-600">{{ $h2hData['total_matches'] }} trận</span>
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-gray-900 wrap-anywhere">{{ $selectedOpponent->name }}</div>
                    <div class="mt-1 text-sm font-bold text-gray-800">{{ $h2hData['user2_wins'] }} thắng</div>
                </div>
            </div>

            <ul class="divide-y divide-gray-100">
                @foreach($h2hData['matches']->take(5) as $match)
                    @php($isWin = in_array($member->id, [$match->winner_id, $match->winner_partner_id], true))
                    <li wire:key="head-to-head-match-{{ $match->id }}">
                        <a href="{{ route('matches.show', $match->id) }}"
                            class="flex min-w-0 items-center justify-between gap-2 rounded-sm py-2.5 text-sm transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-primary">
                            <span class="flex min-w-0 flex-wrap items-center gap-2">
                                <span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $isWin ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800' }}">
                                    {{ $isWin ? 'Thắng' : 'Thua' }}
                                </span>
                                <span class="text-xs text-gray-700 wrap-anywhere">{{ $match->prize }}</span>
                            </span>
                            <time datetime="{{ $match->played_at->toDateString() }}" class="shrink-0 text-xs text-gray-600 tabular-nums">{{ $match->played_at->format('d/m/Y') }}</time>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif($selectedOpponent)
        <p class="px-4 py-4 text-sm text-gray-600 sm:px-5">Hai cơ thủ chưa có trận đối đầu.</p>
    @endif
</section>
