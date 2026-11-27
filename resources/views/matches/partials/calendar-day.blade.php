<button type="button"
    data-calendar-date="{{ $day['dateString'] ?? '' }}"
    data-outside-month="{{ isset($day) && !$day['isCurrentMonth'] ? 'true' : 'false' }}"
    aria-pressed="{{ !empty($day['isSelected']) ? 'true' : 'false' }}"
    @if(!empty($day['isToday'])) aria-current="date" @endif
    aria-label="{{ isset($day) ? implode('/', array_reverse(explode('-', $day['dateString']))).' · '.$day['matchCount'].' trận' : '' }}"
    class="group flex min-h-14 min-w-0 cursor-pointer flex-col items-start justify-between rounded-lg border border-gray-200 bg-white p-1.5 text-left text-gray-900 transition-colors hover:border-primary/40 hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary motion-reduce:transition-none data-[outside-month=true]:text-gray-500 aria-pressed:border-primary aria-pressed:bg-primary-soft aria-pressed:text-primary sm:min-h-16 sm:p-2.5">
    <span class="flex w-full items-center justify-between gap-1">
        <span data-day-number class="text-sm font-semibold tabular-nums">{{ $day['dayNumber'] ?? '' }}</span>
        <span aria-hidden="true" class="hidden size-1.5 shrink-0 rounded-full bg-primary group-aria-[current=date]:block"></span>
    </span>
    <span data-day-badge @if(empty($day['matchCount'])) hidden @endif
        class="max-w-full whitespace-nowrap rounded bg-primary-soft px-1 py-0.5 text-[10px] font-semibold leading-tight text-primary tabular-nums group-aria-pressed:bg-primary group-aria-pressed:text-white sm:px-1.5 sm:text-xs">
        <span data-day-count>{{ $day['matchCount'] ?? '' }}</span><span class="hidden sm:inline"> trận</span>
    </span>
</button>
