@php
    $navigationItemClass = 'inline-flex min-h-10 items-center justify-center gap-1.5 rounded-md px-2.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary motion-reduce:transition-none sm:gap-2 sm:px-3.5 sm:text-sm';
@endphp

<nav aria-label="Xem trận đấu" class="flex w-fit max-w-full flex-wrap gap-1 rounded-lg border border-gray-200 bg-white p-1 shadow-xs">
    @if($currentMatchView === 'calendar')
        @foreach(['calendar' => ['Theo ngày', 'section_calendar'], 'upcoming' => ['Sắp diễn ra', 'section_upcoming']] as $tab => [$label, $section])
            <button type="button" id="tab_btn_{{ $tab }}" onclick="switchCalendarTab('{{ $tab }}')"
                aria-controls="{{ $section }}" aria-pressed="{{ ($activeTab === 'upcoming' ? 'upcoming' : 'calendar') === $tab ? 'true' : 'false' }}"
                class="{{ $navigationItemClass }} group cursor-pointer aria-pressed:bg-primary aria-pressed:text-white aria-pressed:hover:bg-primary-hover">
                {{ $label }}
                @if($tab === 'upcoming')
                    <span id="upcoming_tab_badge" class="rounded-full bg-primary-soft px-1.5 text-xs tabular-nums text-primary group-aria-pressed:bg-white/20 group-aria-pressed:text-white"
                        @if(!$upcomingChallengesCount) hidden @endif>{{ $upcomingChallengesCount }}</span>
                @endif
            </button>
        @endforeach
    @else
        <a href="{{ route('calendar') }}" @if($currentMatchView === 'legacy-calendar') aria-current="page" @endif
            class="{{ $navigationItemClass }} aria-[current=page]:bg-primary aria-[current=page]:text-white aria-[current=page]:hover:bg-primary-hover">Theo ngày</a>
        <a href="{{ route('calendar', ['tab' => 'upcoming']) }}" class="{{ $navigationItemClass }}">
            Sắp diễn ra
            @if($upcomingChallengesCount > 0)
                <span class="rounded-full bg-primary-soft px-1.5 text-xs tabular-nums text-primary">{{ $upcomingChallengesCount }}</span>
            @endif
        </a>
    @endif

    <a href="{{ route('matches.index') }}"
        @if($currentMatchView === 'matches.index') aria-current="page" @endif
        class="{{ $navigationItemClass }} aria-[current=page]:bg-primary aria-[current=page]:text-white aria-[current=page]:hover:bg-primary-hover">Lịch sử</a>
</nav>
