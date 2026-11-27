@extends('layouts.app', ['title' => 'Lịch thi đấu - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Lịch thi đấu</h1>

    @php
        $calendarControlClass = 'inline-flex min-h-9 cursor-pointer items-center justify-center rounded-md border border-gray-200 px-2.5 text-sm font-medium text-gray-700 transition-colors hover:border-primary/30 hover:bg-primary-soft hover:text-primary focus-visible:outline-2 focus-visible:outline-primary aria-disabled:cursor-wait aria-disabled:opacity-50 motion-reduce:transition-none';
    @endphp

    @include('partials.match-navigation', ['currentMatchView' => 'calendar'])

    <div id="section_calendar" class="{{ $activeTab === 'upcoming' ? 'hidden' : '' }}">
        <div class="grid items-start gap-4 lg:grid-cols-12">
            <section aria-labelledby="cal_month_title" class="min-w-0 rounded-xl border border-gray-200 bg-white p-3 shadow-xs sm:p-5 lg:col-span-7">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div role="status" class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                        <h2 id="cal_month_title" class="text-lg font-bold tracking-tight text-gray-900 sm:text-xl">{{ $monthTitle }}</h2>
                        <span id="cal_total_matches" class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 tabular-nums">{{ $totalMonthMatches }} trận</span>
                    </div>

                    <div id="cal_month_controls" class="flex items-center gap-1.5">
                        <button type="button" onclick="goToCalendarToday()" class="{{ $calendarControlClass }}">Hôm nay</button>
                        <button type="button" onclick="goToCalendarPrevMonth()" aria-label="Tháng trước" title="Tháng trước" class="{{ $calendarControlClass }}">
                            <svg aria-hidden="true" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 19-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button type="button" onclick="goToCalendarNextMonth()" aria-label="Tháng sau" title="Tháng sau" class="{{ $calendarControlClass }}">
                            <svg aria-hidden="true" class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p id="cal_month_error" role="alert" hidden class="mt-3 rounded-lg bg-primary-soft px-3 py-2 text-sm text-primary">Chưa tải được lịch. Vui lòng thử lại.</p>

                <div class="mt-4 grid grid-cols-7 border-y border-gray-100 py-2.5 text-center text-xs font-semibold text-gray-600">
                    @foreach(['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'CN'] as $weekday)
                        <span>{{ $weekday }}</span>
                    @endforeach
                </div>

                <div id="cal_days_grid" class="mt-2 grid grid-cols-7 gap-1 sm:gap-1.5">
                    @foreach($days as $day)
                        @include('matches.partials.calendar-day', ['day' => $day])
                    @endforeach
                </div>
                <template id="cal_day_template">
                    @include('matches.partials.calendar-day', ['day' => null])
                </template>

                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-gray-100 pt-3 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1.5"><span aria-hidden="true" class="size-1.5 rounded-full bg-primary"></span> Hôm nay</span>
                    <span class="inline-flex items-center gap-1.5"><span aria-hidden="true" class="size-3 rounded border border-primary bg-primary-soft"></span> Ngày đã chọn</span>
                    <a href="#cal_day_details" class="ml-auto rounded text-sm font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary lg:hidden">Xem trận <span aria-hidden="true">↓</span></a>
                </div>
            </section>

            @php
                $selectedDay = \Carbon\Carbon::parse($selectedDate);
                $weekdayNames = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
                $selectedMatches = $matchesByDate[$selectedDate] ?? [];
            @endphp
            <section id="cal_day_details" tabindex="-1" aria-labelledby="cal_selected_date_title"
                class="min-w-0 scroll-mt-24 rounded-xl border border-gray-200 bg-white p-3 shadow-xs focus-visible:outline-2 focus-visible:outline-primary sm:p-5 lg:col-span-5">
                <div role="status" class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-3">
                    <h2 id="cal_selected_date_title" class="text-base font-bold text-gray-900 sm:text-lg">{{ $weekdayNames[$selectedDay->dayOfWeek] }}, {{ $selectedDay->format('d/m/Y') }}</h2>
                    <span id="cal_selected_date_count" class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-semibold text-primary tabular-nums">{{ count($selectedMatches) }} trận</span>
                </div>

                <ul id="cal_matches_container" tabindex="0" aria-labelledby="cal_selected_date_title"
                    class="mt-3 max-h-[min(55dvh,28rem)] space-y-2.5 overflow-y-auto rounded-lg pr-1 [scrollbar-gutter:stable] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary lg:max-h-[min(62dvh,32rem)] lg:overscroll-contain"
                    @if(!$selectedMatches) hidden @endif>
                    @foreach($selectedMatches as $match)
                        @include('matches.partials.calendar-match', ['match' => $match])
                    @endforeach
                </ul>
                <p id="cal_day_empty" class="mt-3 rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-7 text-center text-sm text-gray-600"
                    @if($selectedMatches) hidden @endif>Ngày này chưa có trận nào được ghi nhận.</p>
                <template id="cal_match_template">
                    @include('matches.partials.calendar-match', ['match' => null])
                </template>
            </section>
        </div>
    </div>

    <section id="section_upcoming" aria-labelledby="cal_upcoming_title" class="{{ $activeTab === 'upcoming' ? '' : 'hidden' }} space-y-3">
        <h2 id="cal_upcoming_title" tabindex="-1" class="text-base font-semibold text-gray-900">Trận đấu sắp diễn ra</h2>
        <p id="upcoming_feedback" role="status" hidden class="rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800"></p>

        <div id="upcoming_cards_grid" class="grid items-start gap-4 xl:grid-cols-2">
            @foreach($upcomingChallenges as $challenge)
                @php
                    $isDoubles = $challenge->isDoubles();
                    $canResolve = $challenge->isParticipant(auth()->user()) || auth()->user()->isAdmin();
                    $userId = auth()->id();
                    $teams = [
                        ['name' => $challenge->challenger_team_names, 'players' => [$challenge->challenger, $challenge->challengerPartner]],
                        ['name' => $challenge->opponent_team_names, 'players' => [$challenge->opponent, $challenge->opponentPartner]],
                    ];
                    $analysis = $matchupAnalyses[$challenge->id];
                    $prediction = $analysis['prediction'];
                    $headToHead = $analysis['h2h'];
                    $sideBets = $challenge->sideBets;
                    $hasSideBetAction = $sideBets->contains(fn ($sideBet) =>
                        ($sideBet->status === 'pending' && in_array($userId, [$sideBet->creator_id, $sideBet->target_id], true))
                        || ($sideBet->status === 'resolved' && (
                            ($sideBet->prize_status === 'unpaid' && $sideBet->loser_id === $userId)
                            || ($sideBet->prize_status === 'paid' && $sideBet->winner_id === $userId)
                        ))
                    );
                    $actionClass = 'inline-flex min-h-10 items-center justify-center rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 transition-colors hover:border-primary/30 hover:bg-primary-soft hover:text-primary focus-visible:outline-2 focus-visible:outline-primary motion-reduce:transition-none cursor-pointer';
                @endphp
                <article id="upcoming_challenge_card_{{ $challenge->id }}" aria-labelledby="upcoming_matchup_{{ $challenge->id }}"
                    class="min-w-0 space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-5">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-medium">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-amber-900">
                            <span aria-hidden="true" class="size-1.5 rounded-full bg-amber-600"></span>
                            Chờ thi đấu
                        </span>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-gray-700">{{ $isDoubles ? 'Đánh đôi' : 'Đánh đơn' }}</span>
                    </div>

                    <h3 id="upcoming_matchup_{{ $challenge->id }}" class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-start gap-2 rounded-lg bg-gray-50 px-2 py-4 text-center text-sm font-semibold text-gray-900 sm:gap-4 sm:text-base">
                        @foreach($teams as $team)
                            @unless($loop->first)
                                <span class="mt-2 rounded-full bg-white px-2 py-1 text-xs font-medium text-gray-600">gặp</span>
                            @endunless
                            <span class="flex min-w-0 flex-col items-center gap-2">
                                <span class="flex -space-x-1.5" aria-hidden="true">
                                    @foreach(array_filter($team['players']) as $player)
                                        <span class="rounded-full ring-2 ring-gray-50">
                                            @include('partials.member-avatar', ['user' => $player])
                                        </span>
                                    @endforeach
                                </span>
                                <span class="wrap-anywhere">{{ $team['name'] }}</span>
                            </span>
                        @endforeach
                    </h3>

                    <div class="space-y-2.5 rounded-lg border border-gray-200 px-3 py-2.5 text-center text-xs">
                        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 font-semibold tabular-nums">
                            <span class="text-primary"><span class="sr-only">{{ $teams[0]['name'] }} </span>{{ $prediction['challenger_pct'] }}%</span>
                            <span class="text-gray-600">Dự đoán thắng</span>
                            <span class="text-slate-700"><span class="sr-only">{{ $teams[1]['name'] }} </span>{{ $prediction['opponent_pct'] }}%</span>
                        </div>
                        <div aria-hidden="true" class="flex h-1.5 overflow-hidden rounded-full bg-slate-400">
                            <span class="bg-primary" style="width: {{ $prediction['challenger_pct'] }}%"></span>
                        </div>
                        @if($headToHead['total'] > 0)
                            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 border-t border-gray-100 pt-2.5 tabular-nums">
                                @foreach(['challenger_wins', 'opponent_wins'] as $winsKey)
                                    @if($loop->last)
                                        <span class="text-gray-600">Đối đầu · {{ $headToHead['total'] }} trận</span>
                                    @endif
                                    <span class="text-gray-700">
                                        <span class="sr-only">{{ $teams[$loop->index]['name'] }} </span>{{ $headToHead[$winsKey] }} thắng
                                        <strong class="block text-gray-900 sm:inline">({{ round($headToHead[$winsKey] / $headToHead['total'] * 100, 1) }}%)</strong>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="border-t border-gray-100 pt-2.5 text-gray-600">Chưa có trận đối đầu đã chốt</p>
                        @endif
                    </div>

                    <div class="text-sm">
                        <p class="flex flex-wrap gap-x-2 gap-y-0.5 text-gray-600">
                            <span>Món kèo</span>
                            <strong class="text-gray-900 wrap-anywhere">{{ $challenge->prize }}</strong>
                        </p>
                        @if($challenge->message)
                            <p class="mt-1 text-gray-600 wrap-anywhere">{{ $challenge->message }}</p>
                        @endif
                    </div>

                    @if($canResolve)
                        <div class="space-y-2 border-t border-gray-100 pt-3">
                            <p class="text-sm font-semibold text-gray-900">Chọn người thắng sau khi đấu</p>
                            <div data-winner-actions class="grid gap-2 sm:grid-cols-2">
                                @foreach($teams as $team)
                                    <button type="button"
                                        onclick="handleResolveChallenge(this, {{ $challenge->id }}, {{ $team['players'][0]->id }}, @js($team['name']))"
                                        class="min-h-10 cursor-pointer rounded-lg border border-primary/25 bg-primary-soft px-3 py-2 text-sm font-semibold text-primary transition-colors wrap-anywhere hover:border-primary hover:bg-primary hover:text-white focus-visible:outline-2 focus-visible:outline-primary aria-disabled:cursor-wait aria-disabled:opacity-50 motion-reduce:transition-none">
                                        {{ $isDoubles ? 'Đội ' : '' }}{{ $team['name'] }} thắng
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="relative border-t border-gray-100 pt-2">
                        @if($sideBets->isNotEmpty())
                            <details class="group" @if($hasSideBetAction) open @endif>
                                <summary class="flex min-h-10 w-fit cursor-pointer list-none items-center gap-1.5 rounded-md text-sm font-semibold text-gray-700 hover:text-primary focus-visible:outline-2 focus-visible:outline-primary [&::-webkit-details-marker]:hidden">
                                    <svg aria-hidden="true" class="size-4 shrink-0 transition-transform group-open:rotate-90 motion-reduce:transition-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"></path>
                                    </svg>
                                    Kèo ngoài
                                    <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs tabular-nums">{{ $sideBets->count() }}</span>
                                </summary>
                                <ul class="mt-2 space-y-2">
                                    @foreach($sideBets as $sideBet)
                                        @php
                                            $statusLabel = match ($sideBet->status) {
                                                'pending' => 'Chờ nhận',
                                                'accepted' => 'Đã nhận',
                                                'declined' => 'Từ chối',
                                                'cancelled' => 'Đã hủy',
                                                'resolved' => match ($sideBet->prize_status) {
                                                    'unpaid' => 'Chưa trả',
                                                    'paid' => 'Chờ xác nhận',
                                                    'confirmed' => 'Đã hoàn tất',
                                                    default => 'Đã chốt',
                                                },
                                                default => 'Đang xử lý',
                                            };
                                        @endphp
                                        <li class="space-y-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm">
                                            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                                                <p class="min-w-0 font-semibold text-gray-900 wrap-anywhere">{{ $sideBet->prize }}</p>
                                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $sideBet->status === 'pending' ? 'bg-amber-50 text-amber-900' : 'bg-white text-gray-700' }}">{{ $statusLabel }}</span>
                                            </div>
                                            <p class="text-xs leading-relaxed text-gray-700 wrap-anywhere">
                                                {{ $sideBet->creator->name }} chọn <strong>{{ $sideBet->creator_team_name }}</strong>
                                                · {{ $sideBet->target->name }} theo cửa <strong>{{ $sideBet->target_team_name }}</strong>
                                            </p>
                                            @if($sideBet->status === 'resolved' && $sideBet->winner)
                                                <p class="text-xs font-semibold text-green-800">{{ $sideBet->winner->name }} thắng kèo</p>
                                            @endif
                                            @if($sideBet->notes)
                                                <p class="text-xs text-gray-600 wrap-anywhere">{{ $sideBet->notes }}</p>
                                            @endif

                                            @if($sideBet->status === 'pending' && $sideBet->target_id === $userId)
                                                <div class="flex flex-wrap gap-2 pt-1">
                                                    <button type="button" onclick="handleAcceptSideBet({{ $sideBet->id }})" class="{{ $actionClass }}">Nhận kèo</button>
                                                    <button type="button" onclick="handleDeclineSideBet({{ $sideBet->id }})" class="{{ $actionClass }}">Từ chối</button>
                                                </div>
                                            @elseif($sideBet->status === 'pending' && $sideBet->creator_id === $userId)
                                                <button type="button" onclick="handleCancelSideBet({{ $sideBet->id }})" class="{{ $actionClass }}">Hủy kèo</button>
                                            @elseif($sideBet->status === 'resolved' && $sideBet->prize_status === 'unpaid' && $sideBet->loser_id === $userId)
                                                <button type="button" onclick="handlePaySideBet({{ $sideBet->id }})" class="{{ $actionClass }}">Đã trả kèo</button>
                                            @elseif($sideBet->status === 'resolved' && $sideBet->prize_status === 'paid' && $sideBet->winner_id === $userId)
                                                <button type="button" onclick="handleConfirmSideBet({{ $sideBet->id }})" class="{{ $actionClass }}">Xác nhận đã nhận</button>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            <span class="flex min-h-10 items-center text-sm text-gray-600">Kèo ngoài</span>
                        @endif

                        <button type="button"
                            onclick="openSideBetModal({{ $challenge->id }}, @js($challenge->challenger_team_names), @js($challenge->opponent_team_names))"
                            class="absolute top-2 right-0 inline-flex min-h-10 cursor-pointer items-center rounded-md px-2 text-sm font-semibold text-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary">
                            <span aria-hidden="true" class="mr-1">+</span> Vào kèo
                        </button>
                    </div>
                </article>
            @endforeach

            <div id="upcoming_empty_state" @if($upcomingChallengesCount) hidden @endif
                class="col-span-full space-y-1 rounded-xl border border-dashed border-gray-200 bg-white px-4 py-8 text-center">
                <p class="font-semibold text-gray-900">Chưa có trận nào sắp diễn ra.</p>
                <p class="text-sm text-gray-600">Trận sẽ xuất hiện tại đây khi lời khiêu chiến được chấp nhận.</p>
            </div>
        </div>
    </section>
</div>

<script>

    const calendarState = {
        year: {{ $year }},
        month: {{ $month }},
        matchesByDate: @js($matchesByDate),
        loadingMonth: false,
    };

    const calendarGrid = document.getElementById('cal_days_grid');
    const calendarWeekdays = @js($weekdayNames);
    const calendarDataUrl = @js(route('calendar.data'));

    function calendarDateLabel(date) {
        return date.split('-').reverse().join('/');
    }

    calendarGrid.addEventListener('click', (event) => {
        const day = event.target.closest('[data-calendar-date]');
        if (day) selectCalendarDate(day.dataset.calendarDate);
    });

    function selectCalendarDate(date) {
        calendarGrid.querySelectorAll('[data-calendar-date]').forEach((day) => {
            day.setAttribute('aria-pressed', String(day.dataset.calendarDate === date));
        });

        const weekday = new Date(date + 'T00:00:00Z').getUTCDay();
        document.getElementById('cal_selected_date_title').textContent = calendarWeekdays[weekday] + ', ' + calendarDateLabel(date);

        const matches = calendarState.matchesByDate[date] ?? [];
        const container = document.getElementById('cal_matches_container');
        const template = document.getElementById('cal_match_template');
        document.getElementById('cal_selected_date_count').textContent = matches.length + ' trận';
        document.getElementById('cal_day_empty').hidden = matches.length > 0;
        container.hidden = matches.length === 0;

        container.replaceChildren(...matches.map((match) => {
            const card = template.content.firstElementChild.cloneNode(true);
            card.querySelector('[data-match-time]').textContent = match.time;
            const status = card.querySelector('[data-match-status]');
            status.textContent = match.status_label;
            status.classList.add(...match.status_class.split(' '));
            card.querySelector('[data-match-team1]').textContent = match.team1_names;
            card.querySelector('[data-match-team2]').textContent = match.team2_names;
            card.querySelector('[data-match-result]').hidden = !match.is_confirmed || !match.winner_names;
            card.querySelector('[data-match-winner]').textContent = match.winner_names;
            card.querySelector('[data-match-prize]').textContent = match.prize;
            card.querySelector('[data-match-link]').href = match.url;

            return card;
        }));
        container.scrollTop = 0;
    }

    async function loadCalendarMonth(year, month) {
        if (calendarState.loadingMonth) return;

        const controls = document.querySelectorAll('#cal_month_controls button');
        const error = document.getElementById('cal_month_error');
        const monthTitle = document.getElementById('cal_month_title');
        calendarState.loadingMonth = true;
        calendarGrid.setAttribute('aria-busy', 'true');
        controls.forEach((button) => button.setAttribute('aria-disabled', 'true'));
        error.hidden = true;

        try {
            const url = new URL(calendarDataUrl, window.location.href);
            if (year !== undefined && month !== undefined) {
                url.search = new URLSearchParams({ year, month });
            }
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Calendar request failed');

            const data = await response.json();
            if (!data.success) throw new Error('Calendar data unavailable');

            const dayTemplate = document.getElementById('cal_day_template');
            const days = data.days.map((day) => {
                const button = dayTemplate.content.firstElementChild.cloneNode(true);
                button.dataset.calendarDate = day.dateString;
                button.dataset.outsideMonth = String(!day.isCurrentMonth);
                if (day.isToday) button.setAttribute('aria-current', 'date');
                button.setAttribute('aria-label', calendarDateLabel(day.dateString) + ' · ' + day.matchCount + ' trận');
                button.querySelector('[data-day-number]').textContent = day.dayNumber;
                button.querySelector('[data-day-count]').textContent = day.matchCount;
                button.querySelector('[data-day-badge]').hidden = day.matchCount === 0;

                return button;
            });

            calendarState.year = data.year;
            calendarState.month = data.month;
            calendarState.matchesByDate = data.matchesByDate;
            monthTitle.textContent = data.monthTitle;
            document.getElementById('cal_total_matches').textContent = data.totalMonthMatches + ' trận';

            calendarGrid.replaceChildren(...days);
            selectCalendarDate(data.selectedDate);
        } catch {
            error.hidden = false;
        } finally {
            calendarState.loadingMonth = false;
            calendarGrid.removeAttribute('aria-busy');
            controls.forEach((button) => button.removeAttribute('aria-disabled'));
        }
    }

    function goToCalendarPrevMonth() {
        const { year, month } = calendarState;
        loadCalendarMonth(month === 1 ? year - 1 : year, month === 1 ? 12 : month - 1);
    }

    function goToCalendarNextMonth() {
        const { year, month } = calendarState;
        loadCalendarMonth(month === 12 ? year + 1 : year, month === 12 ? 1 : month + 1);
    }

    function goToCalendarToday() {
        loadCalendarMonth();
    }

    function switchCalendarTab(tab) {
        const showUpcoming = tab === 'upcoming';
        document.getElementById('tab_btn_calendar').setAttribute('aria-pressed', String(!showUpcoming));
        document.getElementById('tab_btn_upcoming').setAttribute('aria-pressed', String(showUpcoming));
        document.getElementById('section_calendar').classList.toggle('hidden', showUpcoming);
        document.getElementById('section_upcoming').classList.toggle('hidden', !showUpcoming);

        const url = new URL(window.location.href);
        if (showUpcoming) {
            url.searchParams.set('tab', 'upcoming');
            url.hash = '';
        } else {
            url.searchParams.delete('tab');
        }
        history.replaceState(null, '', url);
    }

    async function handleResolveChallenge(button, challengeId, winnerId, winnerName) {
        if (button.getAttribute('aria-disabled') === 'true') {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const actions = button.closest('[data-winner-actions]').querySelectorAll('button');
        actions.forEach((action) => action.setAttribute('aria-disabled', 'true'));
        let saved = false;
        try {
            const confirmed = await window.confirmSiteAction({
                title: 'Chốt kết quả?',
                text: `${winnerName} thắng trận này.`,
                buttons: ['Hủy', 'Chốt kết quả'],
            });
            if (!confirmed) return;

            const res = await fetch(`/challenges/${challengeId}/resolve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ winner_id: winnerId })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                alert(data.message || 'Chưa chốt được kết quả. Vui lòng thử lại.');
                return;
            }

            saved = true;
            const feedback = document.getElementById('upcoming_feedback');
            feedback.hidden = false;
            feedback.textContent = `Đã chốt kết quả: ${winnerName} thắng.`;

            const card = document.getElementById(`upcoming_challenge_card_${challengeId}`);
            if (card) {
                const removeCard = () => {
                    if (card.contains(document.activeElement)) {
                        document.getElementById('cal_upcoming_title').focus();
                    }
                    card.remove();
                    const badge = document.getElementById('upcoming_tab_badge');
                    const count = Math.max(0, Number(badge.textContent) - 1);
                    badge.textContent = count;
                    badge.hidden = count === 0;
                    document.getElementById('upcoming_empty_state').hidden = count !== 0;
                };

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    removeCard();
                } else {
                    card.style.transition = 'opacity 0.2s ease';
                    card.style.opacity = '0';
                    setTimeout(removeCard, 200);
                }
            }
        } catch {
            alert('Chưa chốt được kết quả. Vui lòng thử lại.');
        } finally {
            if (!saved) actions.forEach((action) => action.removeAttribute('aria-disabled'));
        }
    }

</script>
@endsection
