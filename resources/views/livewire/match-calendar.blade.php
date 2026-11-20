<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Left: Calendar (7 cols on lg) -->
        <div class="lg:col-span-7 bg-white border border-gray-200 rounded-lg shadow-sm p-4 sm:p-6 space-y-4">

            <!-- Calendar Header -->
            <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-200 gap-2">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $monthTitle }}</h2>
                    <span class="text-xs sm:text-sm bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-full font-medium">
                        {{ $totalMonthMatches }} trận
                    </span>
                </div>

                <div class="flex items-center space-x-1.5">
                    <button 
                        type="button" 
                        wire:click="goToToday" 
                        class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition cursor-pointer"
                    >
                        Hôm nay
                    </button>
                    <button type="button" wire:click="goToPrevMonth"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-300 rounded-lg transition cursor-pointer"
                        title="Tháng trước">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                            </path>
                        </svg>
                    </button>
                    <button type="button" wire:click="goToNextMonth"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-300 rounded-lg transition cursor-pointer"
                        title="Tháng sau">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Days of Week (Responsive labels) -->
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

            <!-- Days Grid (Responsive square cells) -->
            <div class="grid grid-cols-7 gap-1 sm:gap-1.5">
                @foreach($days as $day)
                            @php
                                $isSelected = $day['isSelected'];
                                $isToday = $day['isToday'];
                                $hasMatches = $day['matchCount'] > 0;
                                $isCurrentMonth = $day['isCurrentMonth'];
                            @endphp

                    <button
                        type="button"
                        wire:click="selectDate('{{ $day['dateString'] }}')"
                        class="min-h-[54px] sm:min-h-[70px] p-1.5 sm:p-2 rounded-lg border text-left flex flex-col justify-between transition cursor-pointer
                            {{ $isSelected 
                                ? 'bg-blue-50 border-blue-600 ring-2 ring-blue-600 text-blue-900 font-bold' 
                                : ($isToday 
                                    ? 'bg-amber-50/70 border-amber-300 text-gray-900 font-semibold' 
                                    : ($isCurrentMonth 
                                        ? 'bg-white border-gray-200 hover:bg-gray-50 text-gray-900' 
                                        : 'bg-gray-50/70 border-gray-100 text-gray-400')) }}"
                    >
                        <!-- Day number -->
                        <span class="text-xs sm:text-sm {{ $isSelected ? 'text-blue-700 font-bold' : ($isToday ? 'text-amber-900 font-bold' : 'font-medium') }}">
                            {{ $day['dayNumber'] }}
                        </span>

                        <!-- Match badge -->
                        @if($hasMatches)
                            <div class="mt-1">
                                <span class="inline-flex items-center px-1 sm:px-1.5 py-0.5 rounded text-[10px] sm:text-xs font-semibold {{ $isSelected ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800' }}">
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
                        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mt-0.5">
                            {{ $selectedDateObj->translatedFormat('l, d/m/Y') }}
                        </h3>
                    </div>
                    <span class="bg-gray-100 text-gray-800 text-xs sm:text-sm px-3 py-1 rounded-full font-bold">
                        {{ $selectedMatches->count() }} trận
                    </span>
                </div>

                <!-- Match List for the Day -->
                <div class="space-y-3">
                    @forelse($selectedMatches as $m)
                        @php
                            $isConfirmed = in_array($m->status, ['confirmed', 'resolved']);
                        @endphp
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition space-y-2.5 bg-gray-50/50">
                            
                            <div class="flex items-center justify-between text-xs sm:text-sm">
                                <span class="font-medium text-gray-500">{{ $m->played_at->format('H:i') }}</span>
                                <span class="px-2.5 py-0.5 rounded text-xs font-semibold
                                    {{ $m->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($m->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $m->status === 'confirmed' ? 'Đã xác nhận' : ($m->status === 'pending' ? 'Chờ duyệt' : 'Khiếu nại') }}
                                </span>
                            </div>

                            <!-- Players -->
                            <div class="flex items-center justify-between text-sm sm:text-base py-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900">{{ $m->team1_names }}</span>
                                    @if(($m->winner_id === $m->player1_id || $m->winner_partner_id === $m->player1_id) && $isConfirmed)
                                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">Thắng</span>
                                    @endif
                                </div>
                                <span class="text-xs font-black text-gray-400">VS</span>
                                <div class="flex items-center gap-2">
                                    @if(($m->winner_id === $m->player2_id || $m->winner_partner_id === $m->player2_id) && $isConfirmed)
                                        <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded font-bold">Thắng</span>
                                    @endif
                                    <span class="font-bold text-gray-900">{{ $m->team2_names }}</span>
                                </div>
                            </div>

                            <!-- Prize & Link -->
                            <div class="flex items-center justify-between text-xs sm:text-sm pt-2.5 border-t border-gray-200/60">
                                <span class="text-gray-700 font-medium">Kèo: <b class="text-gray-900">{{ $m->prize }}</b></span>
                                <a href="{{ route('matches.show', $m->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold">
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