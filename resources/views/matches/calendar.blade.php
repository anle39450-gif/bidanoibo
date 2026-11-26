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

    <!-- Sub-tabs: Lịch đấu theo ngày & Trận đấu sắp diễn ra -->
    <div class="border-b border-gray-200">
        <nav class="flex space-x-6" aria-label="Tabs">
            <button 
                type="button" 
                onclick="switchCalendarTab('calendar')" 
                id="tab_btn_calendar"
                class="pb-3.5 px-1 border-b-2 font-bold text-sm sm:text-base flex items-center gap-2 transition cursor-pointer {{ $activeTab !== 'upcoming' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Lịch đấu theo ngày</span>
            </button>

            <button 
                type="button" 
                onclick="switchCalendarTab('upcoming')" 
                id="tab_btn_upcoming"
                class="pb-3.5 px-1 border-b-2 font-bold text-sm sm:text-base flex items-center gap-2 transition cursor-pointer {{ $activeTab === 'upcoming' ? 'border-red-600 text-red-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                <span class="animate-pulse">⚔️</span>
                <span>Trận đấu sắp diễn ra</span>
                <span 
                    id="upcoming_tab_badge" 
                    class="text-xs px-2 py-0.5 rounded-full font-black {{ $upcomingChallengesCount > 0 ? 'bg-red-600 text-white animate-pulse' : 'bg-gray-100 text-gray-600' }}"
                >
                    {{ $upcomingChallengesCount }}
                </span>
            </button>
        </nav>
    </div>

    <!-- Section 1: Lịch thi đấu theo ngày (Calendar View) -->
    <div id="section_calendar" class="{{ $activeTab === 'upcoming' ? 'hidden' : '' }}">
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

    <!-- Section 2: Trận đấu sắp diễn ra (Upcoming Duel Matches) -->
    <div id="section_upcoming" class="{{ $activeTab === 'upcoming' ? '' : 'hidden' }} space-y-6">
        <div class="bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-sm sm:text-base font-extrabold text-red-900">
                    <span>⚔️ Kèo thách đấu đã tiếp chiêu - Chờ thi đấu</span>
                </div>
                <p class="text-xs sm:text-sm text-red-700 leading-relaxed">
                    Sau khi 2 cơ thủ hoàn tất trận đấu trên bàn bida, <b>1 trong 2 cơ thủ</b> hoặc <b>Quản trị viên (Admin)</b> bấm chọn người thắng bên dưới để hệ thống lập tức chốt kết quả và tính nợ kèo!
                </p>
            </div>
            @auth
                <button type="button" onclick="openSendChallengeModal()"
                    class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 text-white font-extrabold text-xs sm:text-sm px-4 py-2.5 rounded-lg shadow-xs transition shrink-0 cursor-pointer flex items-center justify-center gap-1.5">
                    <span>+ Khiêu chiến người khác</span>
                </button>
            @endauth
        </div>

        <!-- Upcoming Cards Container -->
        <div id="upcoming_cards_grid" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            @forelse($upcomingChallenges as $c)
                @php
                    $isDoubles = $c->isDoubles();
                    $canInteract = auth()->check() && ($c->isParticipant(auth()->user()) || auth()->user()->isAdmin());
                    $matchup = $c->getMatchupAnalysis();
                    $t1 = $matchup['challenger'];
                    $t2 = $matchup['opponent'];
                    $pred = $matchup['prediction'];
                    $h2h = $matchup['h2h'];
                @endphp
                <div id="upcoming_challenge_card_{{ $c->id }}"
                    class="bg-white border border-gray-200 rounded-xl shadow-xs p-5 hover:border-red-300 hover:shadow-md transition space-y-4">
                    
                    <!-- Card Top Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 font-extrabold text-rose-700 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-full">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                                <span>Chờ thi đấu</span>
                            </span>
                            @if($isDoubles)
                                <span class="bg-indigo-100 text-indigo-800 font-extrabold px-2 py-0.5 rounded-full text-[10px]">
                                    2VS2 ĐÁNH ĐÔI
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-700 font-extrabold px-2 py-0.5 rounded-full text-[10px]">
                                    SOLO 1VS1
                                </span>
                            @endif
                        </div>
                        <span class="text-gray-400">
                            Tiếp chiêu {{ $c->accepted_at?->diffForHumans() ?? $c->created_at->diffForHumans() }}
                        </span>
                    </div>

                    <!-- Duel Matchup Players -->
                    <div class="flex items-center justify-between gap-3 py-1">
                        <!-- Player 1: Challenger Team -->
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div class="flex -space-x-2 shrink-0">
                                <img src="{{ $c->challenger->avatar_url }}" alt="{{ $c->challenger->name }}"
                                    class="w-10 h-10 rounded-full border-2 border-white object-cover shadow-2xs">
                                @if($isDoubles && $c->challengerPartner)
                                    <img src="{{ $c->challengerPartner->avatar_url }}" alt="{{ $c->challengerPartner->name }}"
                                        class="w-10 h-10 rounded-full border-2 border-white object-cover shadow-2xs">
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate leading-tight">
                                    {{ $c->challenger_team_names }}
                                </div>
                                <div class="text-xs text-gray-400 truncate">
                                    @<span>{{ $c->challenger->username }}</span>{{ ($isDoubles && $c->challengerPartner) ? ', @'.$c->challengerPartner->username : '' }}
                                </div>
                                <div class="flex items-center gap-1 mt-0.5 flex-wrap">
                                    <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.2 rounded">Thách đấu</span>
                                    <span class="text-[10px] font-semibold text-gray-500">{{ $t1['win_rate'] }}% WR ({{ $t1['wins'] }}W-{{ $t1['losses'] }}L)</span>
                                    @if($t1['streak']['count'] > 1)
                                        <span class="text-[10px] font-bold {{ $t1['streak']['type'] === 'win' ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $t1['streak']['type'] === 'win' ? '🔥' : '❄️' }}{{ $t1['streak']['count'] }}{{ $t1['streak']['type'] === 'win' ? 'W' : 'L' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- VS Badge -->
                        <div class="flex flex-col items-center justify-center shrink-0 px-1 text-center">
                            <span class="text-base font-black text-rose-600">VS</span>
                            <span class="text-[9px] font-bold text-gray-400">{{ $isDoubles ? '2VS2' : 'SOLO' }}</span>
                        </div>

                        <!-- Player 2: Opponent Team -->
                        <div class="flex items-center justify-end gap-2 flex-1 min-w-0 text-right">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-gray-900 truncate leading-tight">
                                    {{ $c->opponent_team_names }}
                                </div>
                                <div class="text-xs text-gray-400 truncate">
                                    @<span>{{ $c->opponent->username }}</span>{{ ($isDoubles && $c->opponentPartner) ? ', @'.$c->opponentPartner->username : '' }}
                                </div>
                                <div class="flex items-center justify-end gap-1 mt-0.5 flex-wrap">
                                    @if($t2['streak']['count'] > 1)
                                        <span class="text-[10px] font-bold {{ $t2['streak']['type'] === 'win' ? 'text-green-600' : 'text-red-500' }}">
                                            {{ $t2['streak']['type'] === 'win' ? '🔥' : '❄️' }}{{ $t2['streak']['count'] }}{{ $t2['streak']['type'] === 'win' ? 'W' : 'L' }}
                                        </span>
                                    @endif
                                    <span class="text-[10px] font-semibold text-gray-500">{{ $t2['win_rate'] }}% WR ({{ $t2['wins'] }}W-{{ $t2['losses'] }}L)</span>
                                    <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-1.5 py-0.2 rounded">Tiếp chiêu</span>
                                </div>
                            </div>
                            <div class="flex -space-x-2 shrink-0">
                                @if($isDoubles && $c->opponentPartner)
                                    <img src="{{ $c->opponentPartner->avatar_url }}" alt="{{ $c->opponentPartner->name }}"
                                        class="w-10 h-10 rounded-full border-2 border-white object-cover shadow-2xs">
                                @endif
                                <img src="{{ $c->opponent->avatar_url }}" alt="{{ $c->opponent->name }}"
                                    class="w-10 h-10 rounded-full border-2 border-white object-cover shadow-2xs">
                            </div>
                        </div>
                    </div>

                    <!-- Matchup Win Prediction Gauge (Chỉ số & Đánh giá % Win Dự Đoán) -->
                    <div class="bg-gray-50/90 rounded-xl p-3 border border-gray-200/80 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-rose-700 flex items-center gap-1 font-black">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <span>{{ $pred['challenger_pct'] }}%</span>
                            </span>
                            <span class="text-[10px] uppercase tracking-wider text-gray-500 font-extrabold flex items-center gap-1">
                                <span>📊</span>
                                <span>Dự Đoán % Win</span>
                            </span>
                            <span class="text-emerald-700 flex items-center gap-1 font-black">
                                <span>{{ $pred['opponent_pct'] }}%</span>
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            </span>
                        </div>

                        <!-- Dual Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2.5 flex overflow-hidden shadow-2xs">
                            <div class="bg-rose-500 h-full transition-all duration-700" style="width: {{ $pred['challenger_pct'] }}%;" title="{{ $c->challenger_team_names }}: {{ $pred['challenger_pct'] }}%"></div>
                            <div class="bg-emerald-500 h-full transition-all duration-700" style="width: {{ $pred['opponent_pct'] }}%;" title="{{ $c->opponent_team_names }}: {{ $pred['opponent_pct'] }}%"></div>
                        </div>

                        <!-- Evaluation text & H2H note -->
                        <div class="flex items-center justify-between text-[11px] pt-0.5">
                            <span class="font-medium text-gray-700">{{ $pred['eval_text'] }}</span>
                            @if($h2h['total'] > 0)
                                <span class="text-[10px] font-bold text-gray-500 bg-white border border-gray-200 px-1.5 py-0.5 rounded shadow-2xs shrink-0 ml-2" title="Lịch sử đối đầu trực tiếp">
                                    Đối đầu: {{ $h2h['challenger_wins'] }} - {{ $h2h['opponent_wins'] }}
                                </span>
                            @else
                                <span class="text-[10px] text-gray-400 shrink-0 ml-2">Chưa có đối đầu</span>
                            @endif
                        </div>
                    </div>

                    <!-- Challenge Prize & Taunt -->
                    <div class="bg-gray-50 rounded-lg p-3 text-xs space-y-1.5 border border-gray-100">
                        <div class="flex items-center gap-1.5 text-gray-700">
                            <span class="font-bold uppercase tracking-wider text-[11px] text-gray-500">Món kèo:</span>
                            <span class="font-black text-amber-800 text-sm">{{ $c->prize }}</span>
                        </div>
                        @if($c->message)
                            <div class="text-gray-600 italic">
                                "{{ $c->message }}"
                            </div>
                        @endif
                    </div>

                    <!-- Side Bets (Kèo Ngoài Bên Lề) -->
                    <div class="border border-purple-100 bg-purple-50/40 rounded-xl p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="text-sm">🎯</span>
                                <span class="text-xs font-black uppercase tracking-wider text-purple-900">Kèo Ngoài Bên Lề</span>
                                @if($c->sideBets->count() > 0)
                                    <span class="bg-purple-100 text-purple-800 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full">
                                        {{ $c->sideBets->count() }}
                                    </span>
                                @endif
                            </div>
                            @auth
                                <button type="button"
                                    onclick="openSideBetModal({{ $c->id }}, '{{ addslashes($c->challenger_team_names) }}', '{{ addslashes($c->opponent_team_names) }}', {{ $isDoubles ? 'true' : 'false' }})"
                                    class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-lg shadow-2xs transition flex items-center gap-1 cursor-pointer">
                                    <span>+ Vào Kèo</span>
                                </button>
                            @endauth
                        </div>

                        <!-- Side Bets List -->
                        @if($c->sideBets->count() > 0)
                            <div class="space-y-2">
                                @foreach($c->sideBets as $sb)
                                    <div class="bg-white border border-gray-200/80 rounded-lg p-2.5 text-xs shadow-2xs space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="font-extrabold text-gray-900">{{ $sb->creator->name }}</span>
                                                    <span class="text-gray-400 text-[11px]">bắt</span>
                                                    <span class="bg-rose-50 text-rose-700 border border-rose-200 font-bold px-1.5 py-0.2 rounded text-[11px]">{{ $sb->creator_team_name }}</span>
                                                    <span class="text-gray-300 font-bold">⚔️</span>
                                                    <span class="font-extrabold text-gray-900">{{ $sb->target->name }}</span>
                                                    <span class="text-gray-400 text-[11px]">nhận</span>
                                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-1.5 py-0.2 rounded text-[11px]">{{ $sb->target_team_name }}</span>
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] text-gray-600">
                                                    <span>🎁 Món kèo: <b class="text-purple-700">{{ $sb->prize }}</b></span>
                                                    @if($sb->notes)
                                                        <span class="text-gray-400 italic">"{{ $sb->notes }}"</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- Status Badge -->
                                            <div class="shrink-0 text-right">
                                                @if($sb->status === 'pending')
                                                    <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2 py-0.5 rounded-full inline-block">
                                                        Chờ {{ $sb->target->name }} nhận
                                                    </span>
                                                @elseif($sb->status === 'accepted')
                                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-full inline-block">
                                                        Đã chốt kèo 🤝
                                                    </span>
                                                @elseif($sb->status === 'declined')
                                                    <span class="bg-gray-100 text-gray-500 text-[10px] font-bold px-2 py-0.5 rounded-full inline-block">
                                                        Từ chối ✕
                                                    </span>
                                                @elseif($sb->status === 'cancelled')
                                                    <span class="bg-gray-100 text-gray-400 text-[10px] font-bold px-2 py-0.5 rounded-full inline-block">
                                                        Đã hủy
                                                    </span>
                                                @elseif($sb->status === 'resolved')
                                                    <span class="bg-purple-100 text-purple-800 text-[10px] font-black px-2 py-0.5 rounded-full inline-block">
                                                        🏆 {{ $sb->winner?->name }} thắng
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Interactive actions for this specific side bet -->
                                        @auth
                                            @php
                                                $myId = auth()->id();
                                            @endphp
                                            @if($sb->status === 'pending')
                                                <div class="flex items-center justify-end gap-1.5 pt-1.5 border-t border-gray-100">
                                                    @if($sb->target_id === $myId)
                                                        <button type="button" onclick="handleAcceptSideBet({{ $sb->id }})"
                                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-md transition shadow-2xs cursor-pointer">
                                                            ✓ Nhận kèo
                                                        </button>
                                                        <button type="button" onclick="handleDeclineSideBet({{ $sb->id }})"
                                                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-[11px] px-2 py-1 rounded-md transition cursor-pointer">
                                                            ✕ Từ chối
                                                        </button>
                                                    @elseif($sb->creator_id === $myId)
                                                        <button type="button" onclick="handleCancelSideBet({{ $sb->id }})"
                                                            class="text-gray-400 hover:text-red-600 font-semibold text-[11px] transition cursor-pointer">
                                                            Hủy kèo
                                                        </button>
                                                    @endif
                                                </div>
                                            @elseif($sb->status === 'resolved')
                                                <!-- Settlement actions -->
                                                <div class="flex items-center justify-between pt-1.5 border-t border-gray-100 text-[11px]">
                                                    <div class="text-gray-500">
                                                        @if($sb->prize_status === 'unpaid')
                                                            <span class="text-red-600 font-bold">Chưa trả kèo</span>
                                                        @elseif($sb->prize_status === 'paid')
                                                            <span class="text-amber-700 font-bold">Đã trả (chờ xác nhận)</span>
                                                        @elseif($sb->prize_status === 'confirmed')
                                                            <span class="text-emerald-700 font-bold">Hoàn tất ✓</span>
                                                        @endif
                                                    </div>

                                                    <div class="flex items-center gap-1.5">
                                                        @if($sb->prize_status === 'unpaid' && $sb->loser_id === $myId)
                                                            <button type="button" onclick="handlePaySideBet({{ $sb->id }})"
                                                                class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-md transition shadow-2xs cursor-pointer">
                                                                Đã trả kèo
                                                            </button>
                                                        @elseif($sb->prize_status === 'paid' && $sb->winner_id === $myId)
                                                            <button type="button" onclick="handleConfirmSideBet({{ $sb->id }})"
                                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] px-2.5 py-1 rounded-md transition shadow-2xs cursor-pointer">
                                                                Xác nhận đã nhận
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endauth
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-2 text-xs text-purple-700/70 italic bg-white/60 rounded-lg border border-purple-100">
                                Chưa có kèo ngoài nào. Bấm <b>+ Vào Kèo</b> để bắt độ đồng nghiệp nhé!
                            </div>
                        @endif
                    </div>

                    <!-- Interactive Action Area (Winner selection by participants or Admin) -->
                    @if($canInteract)
                        <div class="pt-2 border-t border-gray-100 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-gray-700 uppercase tracking-wider">Cập nhật kết quả:</span>
                                @if(auth()->user()->isAdmin() && ! $c->isParticipant(auth()->user()))
                                    <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded">Quyền Admin</span>
                                @else
                                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">Cơ thủ trong trận</span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <button type="button"
                                    onclick="handleResolveChallenge({{ $c->id }}, {{ $c->challenger_id }}, '{{ addslashes($c->challenger_team_names) }}')"
                                    class="w-full bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-800 hover:text-emerald-900 font-bold py-2 px-2.5 rounded-lg text-xs transition cursor-pointer flex items-center justify-center gap-1 truncate shadow-2xs">
                                    <span>🏆</span>
                                    <span class="truncate">{{ $isDoubles ? 'Đội ' : '' }}{{ $c->challenger_team_names }} thắng</span>
                                </button>
                                <button type="button"
                                    onclick="handleResolveChallenge({{ $c->id }}, {{ $c->opponent_id }}, '{{ addslashes($c->opponent_team_names) }}')"
                                    class="w-full bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-800 hover:text-emerald-900 font-bold py-2 px-2.5 rounded-lg text-xs transition cursor-pointer flex items-center justify-center gap-1 truncate shadow-2xs">
                                    <span>🏆</span>
                                    <span class="truncate">{{ $isDoubles ? 'Đội ' : '' }}{{ $c->opponent_team_names }} thắng</span>
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="pt-2 border-t border-gray-100 text-center text-xs text-gray-400 italic">
                            Chờ các cơ thủ trong trận hoặc Quản trị viên cập nhật kết quả sau trận đấu.
                        </div>
                    @endif

                </div>
            @empty
                <div id="upcoming_empty_state" class="col-span-full bg-white border border-gray-200 rounded-xl p-10 text-center space-y-3 shadow-xs">
                    <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto text-2xl font-bold">
                        ⚔️
                    </div>
                    <div class="text-base font-bold text-gray-900">Hiện không có trận khiêu chiến nào đang chờ đấu</div>
                    <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto">
                        Khi ai đó gửi lời thách đấu và đối phương bấm "Tiếp Chiêu", trận đấu sẽ xuất hiện ở đây để 2 người thi đấu và chọn người thắng!
                    </p>
                    @auth
                        <div class="pt-2">
                            <button type="button" onclick="openSendChallengeModal()"
                                class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 text-white font-extrabold text-xs sm:text-sm px-5 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                                ⚔️ Phát Lệnh Khiêu Chiến Ngay
                            </button>
                        </div>
                    @endauth
                </div>
            @endforelse
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

    function switchCalendarTab(tab) {
        const btnCal = document.getElementById('tab_btn_calendar');
        const btnUp = document.getElementById('tab_btn_upcoming');
        const secCal = document.getElementById('section_calendar');
        const secUp = document.getElementById('section_upcoming');

        if (!btnCal || !btnUp || !secCal || !secUp) return;

        if (tab === 'upcoming') {
            btnUp.className = 'pb-3.5 px-1 border-b-2 font-bold text-sm sm:text-base flex items-center gap-2 transition cursor-pointer border-red-600 text-red-600';
            btnCal.className = 'pb-3.5 px-1 border-b-2 font-bold text-sm sm:text-base flex items-center gap-2 transition cursor-pointer border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
            secCal.classList.add('hidden');
            secUp.classList.remove('hidden');
            history.replaceState(null, '', '?tab=upcoming');
        } else {
            btnCal.className = 'pb-3.5 px-1 border-b-2 font-bold text-sm sm:text-base flex items-center gap-2 transition cursor-pointer border-blue-600 text-blue-600';
            btnUp.className = 'pb-3.5 px-1 border-b-2 font-bold text-sm sm:text-base flex items-center gap-2 transition cursor-pointer border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
            secUp.classList.add('hidden');
            secCal.classList.remove('hidden');
            history.replaceState(null, '', window.location.pathname);
        }
    }

    async function handleResolveChallenge(challengeId, winnerId, winnerName) {
        if (!confirm(`Xác nhận cơ thủ "${winnerName}" đã giành CHIẾN THẮNG trận khiêu chiến này?`)) {
            return;
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        try {
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
                alert(data.message || 'Có lỗi xảy ra khi cập nhật kết quả!');
                return;
            }

            alert(data.message || 'Đã ghi nhận kết quả thành công!');

            // Remove card with smooth transition
            const card = document.getElementById(`upcoming_challenge_card_${challengeId}`);
            if (card) {
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    // Update badge count
                    const badge = document.getElementById('upcoming_tab_badge');
                    if (badge) {
                        let count = parseInt(badge.innerText.trim()) || 0;
                        count = Math.max(0, count - 1);
                        badge.innerText = count;
                        if (count === 0) {
                            badge.className = 'text-xs px-2 py-0.5 rounded-full font-black bg-gray-100 text-gray-600';
                            const grid = document.getElementById('upcoming_cards_grid');
                            if (grid && !grid.querySelector('[id^="upcoming_challenge_card_"]')) {
                                grid.innerHTML = `
                                    <div id="upcoming_empty_state" class="col-span-full bg-white border border-gray-200 rounded-xl p-10 text-center space-y-3 shadow-xs">
                                        <div class="w-12 h-12 rounded-full bg-red-50 text-red-600 flex items-center justify-center mx-auto text-2xl font-bold">
                                            ⚔️
                                        </div>
                                        <div class="text-base font-bold text-gray-900">Hiện không có trận khiêu chiến nào đang chờ đấu</div>
                                        <p class="text-xs sm:text-sm text-gray-500 max-w-md mx-auto">
                                            Khi ai đó gửi lời thách đấu và đối phương bấm "Tiếp Chiêu", trận đấu sẽ xuất hiện ở đây để 2 người thi đấu và chọn người thắng!
                                        </p>
                                        @auth
                                            <div class="pt-2">
                                                <button type="button" onclick="openSendChallengeModal()"
                                                    class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 text-white font-extrabold text-xs sm:text-sm px-5 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                                                    ⚔️ Phát Lệnh Khiêu Chiến Ngay
                                                </button>
                                            </div>
                                        @endauth
                                    </div>
                                `;
                            }
                        }
                    }
                }, 300);
            }
        } catch (e) {
            alert('Lỗi kết nối: ' + e.message);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('tab') === 'upcoming') {
            switchCalendarTab('upcoming');
        }
    });
</script>
@endsection
