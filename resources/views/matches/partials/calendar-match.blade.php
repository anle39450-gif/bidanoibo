<li @if(isset($match['history_id'])) wire:key="history-match-{{ $match['history_id'] }}" @endif
    class="space-y-3 rounded-lg border border-gray-200 p-3.5 transition-colors hover:border-primary/30 motion-reduce:transition-none sm:p-4 {{ isset($match['history_id']) ? 'bg-white shadow-xs' : 'bg-gray-50/60' }}">
    <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
        <span data-match-time class="font-medium text-gray-600 tabular-nums">{{ $match['time'] ?? '' }}</span>
        <span data-match-status class="rounded-full px-2 py-0.5 font-semibold {{ $match['status_class'] ?? '' }}">{{ $match['status_label'] ?? '' }}</span>
    </div>

    @php
        $team1Won = !empty($match['is_team1_winner']);
        $team2Won = !empty($match['is_team2_winner']);
        $hasResult = !empty($match['is_confirmed']) && $team1Won !== $team2Won;
    @endphp
    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-1.5 sm:gap-2">
        @foreach([1 => $team1Won, 2 => $team2Won] as $side => $won)
            @unless($loop->first)
                <span class="text-xs font-medium text-gray-500">gặp</span>
            @endunless
            <div data-match-side="{{ $side }}" data-result="{{ $hasResult ? ($won ? 'win' : 'loss') : '' }}"
                class="group flex h-full min-w-0 flex-wrap items-center justify-center gap-x-2 gap-y-1 rounded-lg border border-gray-200 bg-white px-2 py-2 text-center data-[result=win]:border-green-200 data-[result=win]:bg-green-50 data-[result=loss]:border-red-200 data-[result=loss]:bg-primary-soft">
                <span data-match-team-name class="text-sm font-semibold text-gray-900 wrap-anywhere">{{ $match['team'.$side.'_names'] ?? '' }}</span>
                <span class="hidden rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-800 group-data-[result=win]:inline-block">Thắng</span>
                <span class="hidden rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-primary group-data-[result=loss]:inline-block">Thua</span>
            </div>
        @endforeach
    </div>

    @if(isset($match['remaining_minutes']))
        <p class="text-xs text-amber-800">Tự xác nhận sau <strong>{{ intdiv($match['remaining_minutes'], 60) }} giờ {{ $match['remaining_minutes'] % 60 }} phút</strong></p>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-2.5 text-sm">
        <div class="flex min-w-0 flex-wrap items-center gap-2 text-xs">
            <span data-match-prize class="min-w-0 text-gray-600 wrap-anywhere">{{ $match['prize'] ?? '' }}</span>
            @isset($match['prize_paid'])
                <span class="rounded-full px-2 py-0.5 font-semibold {{ $match['prize_paid'] ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-800' }}">
                    {{ $match['prize_paid'] ? 'Đã trả kèo' : 'Chưa trả kèo' }}
                </span>
            @endisset
        </div>
        <div class="ml-auto flex items-center gap-1.5">
            @if(!empty($match['can_confirm']))
                <button type="button" wire:click="quickConfirm({{ $match['history_id'] }})"
                    wire:loading.attr="disabled" wire:target="quickConfirm({{ $match['history_id'] }})"
                    class="min-h-8 cursor-pointer rounded-md bg-primary px-2.5 font-semibold text-white hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-wait disabled:opacity-60">
                    Xác nhận
                </button>
            @endif
            <a data-match-link aria-haspopup="dialog" @isset($match) href="{{ $match['url'] }}" @endisset
                class="inline-flex min-h-8 items-center gap-1.5 whitespace-nowrap rounded-md px-2 font-semibold text-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary">
                Chi tiết <span aria-hidden="true">→</span>
            </a>
        </div>
    </div>
</li>
