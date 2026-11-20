@extends('layouts.app', ['title' => 'Lịch thi đấu - Bida Nội Bộ'])

@section('content')
<div class="space-y-6">
    <!-- Header with View Mode Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Lịch Thi Đấu Bida Theo Ngày</h1>
            <p class="text-sm sm:text-base text-gray-500 mt-1">Bấm vào bất kỳ ngày nào trên lịch để xem các trận đấu đã diễn ra.</p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <!-- View Mode Switcher -->
            <div class="inline-flex rounded-lg shadow-sm border border-gray-300 bg-white p-1 text-sm font-medium">
                <a href="{{ route('matches.index') }}" class="px-3.5 py-1.5 rounded-md text-gray-600 hover:text-gray-900 transition">
                    Danh sách
                </a>
                <a href="{{ route('calendar') }}" class="px-3.5 py-1.5 rounded-md bg-blue-600 text-white font-semibold shadow-sm transition">
                    Lịch Calendar
                </a>
            </div>

            @auth
                <button 
                    type="button" 
                    onclick="triggerCreateMatchModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition cursor-pointer"
                >
                    + Ghi nhận trận
                </button>
            @else
                <a 
                    href="{{ route('login') }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition cursor-pointer"
                >
                    + Ghi nhận trận
                </a>
            @endauth
        </div>
    </div>

    <!-- AJAX Responsive Calendar -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left: Calendar Grid (7 cols on lg) -->
        <div class="lg:col-span-7 bg-white border border-gray-200 rounded-lg shadow-sm p-4 sm:p-6 space-y-4">

            <!-- Calendar Header -->
            <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-200 gap-2">
                <div class="flex items-center gap-3">
                    <h2 id="cal_month_title" class="text-xl sm:text-2xl font-bold text-gray-900">{{ $monthTitle }}</h2>
                    <span id="cal_total_matches" class="text-xs sm:text-sm bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-full font-medium">
                        {{ $totalMonthMatches }} trận
                    </span>
                </div>

                <div class="flex items-center space-x-1.5">
                    <button 
                        type="button" 
                        onclick="goToCalendarToday()" 
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition cursor-pointer"
                    >
                        Hôm nay
                    </button>
                    <button 
                        type="button" 
                        onclick="goToCalendarPrevMonth()"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-300 rounded-lg transition cursor-pointer"
                        title="Tháng trước"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button 
                        type="button" 
                        onclick="goToCalendarNextMonth()"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-300 rounded-lg transition cursor-pointer"
                        title="Tháng sau"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 text-center border-b border-gray-200 pb-2">
                @foreach([
                    ['long' => 'Thứ 2', 'short' => 'T2'],
                    ['long' => 'Thứ 3', 'short' => 'T3'],
                    ['long' => 'Thứ 4', 'short' => 'T4'],
                    ['long' => 'Thứ 5', 'short' => 'T5'],
                    ['long' => 'Thứ 6', 'short' => 'T6'],
                    ['long' => 'Thứ 7', 'short' => 'T7'],
                    ['long' => 'CN', 'short' => 'CN'],
                ] as $wd)
                    <div class="text-xs sm:text-sm font-bold text-gray-700">
                        <span class="sm:hidden">{{ $wd['short'] }}</span>
                        <span class="hidden sm:inline">{{ $wd['long'] }}</span>
                    </div>
                @endforeach
            </div>

            <!-- Days Grid Container -->
            <div id="cal_days_grid" class="grid grid-cols-7 gap-1 sm:gap-1.5">
                @foreach($days as $day)
                    @php
                        $isSelected = $day['isSelected'];
                        $isToday = $day['isToday'];
                        $hasMatches = $day['matchCount'] > 0;
                        $isCurrentMonth = $day['isCurrentMonth'];
                    @endphp
                    <button
                        type="button"
                        id="cal_day_btn_{{ $day['dateString'] }}"
                        onclick="selectCalendarDate('{{ $day['dateString'] }}')"
                        class="cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer
                            {{ $isSelected 
                                ? 'bg-blue-50 border-blue-600 ring-2 ring-blue-600 text-blue-900 font-bold' 
                                : ($isToday 
                                    ? 'bg-amber-50/70 border-amber-300 text-gray-900 font-semibold' 
                                    : ($isCurrentMonth 
                                        ? 'bg-white border-gray-200 hover:bg-gray-50 text-gray-900' 
                                        : 'bg-gray-50/70 border-gray-100 text-gray-400')) }}"
                    >
                        <span class="text-xs sm:text-sm {{ $isSelected ? 'text-blue-700 font-bold' : ($isToday ? 'text-amber-900 font-bold' : 'font-medium') }}">
                            {{ $day['dayNumber'] }}
                        </span>

                        @if($hasMatches)
                            <div class="mt-1">
                                <span class="cal-match-badge inline-flex items-center px-1 sm:px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-semibold {{ $isSelected ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $day['matchCount'] }}<span class="hidden sm:inline ml-0.5"> trận</span>
                                </span>
                            </div>
                        @else
                            <span></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <!-- Footer note -->
            <div class="text-xs sm:text-sm text-gray-500 pt-2 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <span>Nhấn vào từng ngày trên lịch để xem danh sách trận</span>
                <span class="text-blue-600 font-semibold">Hôm nay: {{ now()->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Right: Matches on Selected Date (5 cols on lg) -->
        <div class="lg:col-span-5 space-y-4">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 sm:p-6 space-y-4">

                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <div>
                        <div class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Trận đấu theo ngày</div>
                        <h3 id="cal_selected_date_title" class="text-lg sm:text-xl font-bold text-gray-900 mt-0.5">
                            {{ \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, d/m/Y') }}
                        </h3>
                    </div>
                    <span id="cal_selected_date_count" class="bg-gray-100 text-gray-800 text-xs sm:text-sm px-3 py-1 rounded-full font-bold">
                        {{ count($matchesByDate[$selectedDate] ?? []) }} trận
                    </span>
                </div>

                <!-- Match List Container -->
                <div id="cal_matches_container" class="space-y-3">
                    <!-- Populated dynamically via JS / initially populated by SSR -->
                    @forelse($matchesByDate[$selectedDate] ?? [] as $m)
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition space-y-2.5 bg-gray-50/50">
                            <div class="flex items-center justify-between text-xs sm:text-sm">
                                <span class="font-medium text-gray-500">{{ $m['time'] }}</span>
                                <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $m['status_class'] }}">
                                    {{ $m['status_label'] }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-sm sm:text-base py-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900">{{ $m['team1_names'] }}</span>
                                    @if($m['is_team1_winner'] && $m['is_confirmed'])
                                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">Thắng</span>
                                    @endif
                                </div>
                                <span class="text-xs font-black text-gray-400">VS</span>
                                <div class="flex items-center gap-2">
                                    @if($m['is_team2_winner'] && $m['is_confirmed'])
                                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">Thắng</span>
                                    @endif
                                    <span class="font-bold text-gray-900">{{ $m['team2_names'] }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs sm:text-sm pt-2.5 border-t border-gray-200/60">
                                <span class="text-gray-700 font-medium">Kèo: <b class="text-gray-900">{{ $m['prize'] }}</b></span>
                                <a href="{{ $m['url'] }}" class="text-blue-600 hover:text-blue-800 font-semibold">
                                    Xem chi tiết →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-sm text-gray-500 space-y-2">
                            <p>Không có trận đấu nào diễn ra trong ngày này.</p>
                            @auth
                                <button 
                                    type="button" 
                                    onclick="triggerCreateMatchModal()" 
                                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition cursor-pointer"
                                >
                                    + Ghi nhận trận ngay
                                </button>
                            @endauth
                        </div>
                    @endforelse
                </div>

            </div>
        </div>

    </div>
</div>

<script>
    // Initialize in-memory calendar state
    window.calendarState = {
        year: {{ $year }},
        month: {{ $month }},
        selectedDate: '{{ $selectedDate }}',
        days: @json($days),
        matchesByDate: @json($matchesByDate)
    };

    /**
     * Instantly select a date (0ms lag client-side)
     */
    function selectCalendarDate(dateStr) {
        window.calendarState.selectedDate = dateStr;

        // 1. Update UI highlight for day cells
        document.querySelectorAll('.cal-day-cell').forEach(cell => {
            const isTarget = (cell.id === 'cal_day_btn_' + dateStr);
            const isToday = cell.getAttribute('data-is-today') === '1';
            const isCurrentMonth = cell.getAttribute('data-is-current-month') === '1';

            if (isTarget) {
                cell.className = 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-blue-50 border-blue-600 ring-2 ring-blue-600 text-blue-900 font-bold';
            } else if (isToday) {
                cell.className = 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-amber-50/70 border-amber-300 text-gray-900 font-semibold';
            } else if (isCurrentMonth) {
                cell.className = 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-white border-gray-200 hover:bg-gray-50 text-gray-900';
            } else {
                cell.className = 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-gray-50/70 border-gray-100 text-gray-400';
            }

            // Update badge inside cell
            const badge = cell.querySelector('.cal-match-badge');
            if (badge) {
                badge.className = isTarget 
                    ? 'cal-match-badge inline-flex items-center px-1 sm:px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-semibold bg-blue-600 text-white'
                    : 'cal-match-badge inline-flex items-center px-1 sm:px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-semibold bg-blue-100 text-blue-800';
            }
        });

        // 2. Format Vietnamese Day Header
        const parts = dateStr.split('-');
        const dateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        const dayNames = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy'];
        const formattedDate = dayNames[dateObj.getDay()] + ', ' + String(dateObj.getDate()).padStart(2, '0') + '/' + String(dateObj.getMonth() + 1).padStart(2, '0') + '/' + dateObj.getFullYear();
        
        document.getElementById('cal_selected_date_title').innerText = formattedDate;

        // 3. Render matches list instantly
        const matches = window.calendarState.matchesByDate[dateStr] || [];
        document.getElementById('cal_selected_date_count').innerText = matches.length + ' trận';

        const container = document.getElementById('cal_matches_container');
        if (matches.length === 0) {
            container.innerHTML = `
                <div class="py-8 text-center text-sm text-gray-500 space-y-2">
                    <p>Không có trận đấu nào diễn ra trong ngày này.</p>
                    @auth
                        <button 
                            type="button" 
                            onclick="triggerCreateMatchModal()" 
                            class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold px-4 py-2 rounded-lg shadow-sm transition cursor-pointer"
                        >
                            + Ghi nhận trận ngay
                        </button>
                    @endauth
                </div>
            `;
            return;
        }

        let html = '';
        matches.forEach(m => {
            html += `
                <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition space-y-2.5 bg-gray-50/50">
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <span class="font-medium text-gray-500">${m.time}</span>
                        <span class="px-2.5 py-0.5 rounded text-xs font-semibold ${m.status_class}">
                            ${m.status_label}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-sm sm:text-base py-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900">${m.team1_names}</span>
                            ${m.is_team1_winner && m.is_confirmed ? '<span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">Thắng</span>' : ''}
                        </div>
                        <span class="text-xs font-black text-gray-400">VS</span>
                        <div class="flex items-center gap-2">
                            ${m.is_team2_winner && m.is_confirmed ? '<span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">Thắng</span>' : ''}
                            <span class="font-bold text-gray-900">${m.team2_names}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs sm:text-sm pt-2.5 border-t border-gray-200/60">
                        <span class="text-gray-700 font-medium">Kèo: <b class="text-gray-900">${m.prize}</b></span>
                        <a href="${m.url}" class="text-blue-600 hover:text-blue-800 font-semibold">
                            Xem chi tiết →
                        </a>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    /**
     * Fetch new month via AJAX
     */
    async function loadCalendarMonth(year, month) {
        try {
            document.getElementById('cal_month_title').classList.add('opacity-50');

            const response = await fetch(`{{ route('calendar.data') }}?year=${year}&month=${month}`, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();
            if (!data.success) return;

            window.calendarState.year = data.year;
            window.calendarState.month = data.month;
            window.calendarState.days = data.days;
            window.calendarState.matchesByDate = data.matchesByDate;
            window.calendarState.selectedDate = data.selectedDate;

            // Update Month Title & Count
            document.getElementById('cal_month_title').innerText = data.monthTitle;
            document.getElementById('cal_month_title').classList.remove('opacity-50');
            document.getElementById('cal_total_matches').innerText = data.totalMonthMatches + ' trận';

            // Re-render Days Grid
            const grid = document.getElementById('cal_days_grid');
            let gridHtml = '';
            data.days.forEach(d => {
                const isSelected = d.isSelected;
                const isToday = d.isToday;
                const hasMatches = d.matchCount > 0;
                const isCurrentMonth = d.isCurrentMonth;

                const cellClass = isSelected 
                    ? 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-blue-50 border-blue-600 ring-2 ring-blue-600 text-blue-900 font-bold'
                    : (isToday 
                        ? 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-amber-50/70 border-amber-300 text-gray-900 font-semibold'
                        : (isCurrentMonth 
                            ? 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-white border-gray-200 hover:bg-gray-50 text-gray-900'
                            : 'cal-day-cell min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer bg-gray-50/70 border-gray-100 text-gray-400'));

                const badgeClass = isSelected ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800';

                gridHtml += `
                    <button
                        type="button"
                        id="cal_day_btn_${d.dateString}"
                        data-is-today="${isToday ? 1 : 0}"
                        data-is-current-month="${isCurrentMonth ? 1 : 0}"
                        onclick="selectCalendarDate('${d.dateString}')"
                        class="${cellClass}"
                    >
                        <span class="text-xs sm:text-sm ${isSelected ? 'text-blue-700 font-bold' : (isToday ? 'text-amber-900 font-bold' : 'font-medium')}">
                            ${d.dayNumber}
                        </span>

                        ${hasMatches ? `
                            <div class="mt-1">
                                <span class="cal-match-badge inline-flex items-center px-1 sm:px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-semibold ${badgeClass}">
                                    ${d.matchCount}<span class="hidden sm:inline ml-0.5"> trận</span>
                                </span>
                            </div>
                        ` : '<span></span>'}
                    </button>
                `;
            });
            grid.innerHTML = gridHtml;

            // Render selected date matches
            selectCalendarDate(data.selectedDate);
        } catch (err) {
            console.error('Error loading calendar month', err);
            document.getElementById('cal_month_title').classList.remove('opacity-50');
        }
    }

    function goToCalendarPrevMonth() {
        let y = window.calendarState.year;
        let m = window.calendarState.month - 1;
        if (m < 1) {
            m = 12;
            y -= 1;
        }
        loadCalendarMonth(y, m);
    }

    function goToCalendarNextMonth() {
        let y = window.calendarState.year;
        let m = window.calendarState.month + 1;
        if (m > 12) {
            m = 1;
            y += 1;
        }
        loadCalendarMonth(y, m);
    }

    function goToCalendarToday() {
        const now = new Date();
        loadCalendarMonth(now.getFullYear(), now.getMonth() + 1);
    }
</script>
@endsection
