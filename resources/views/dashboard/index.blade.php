@extends('layouts.app', ['title' => 'Dashboard - Bida Nội Bộ'])

@section('content')
    <div class="space-y-6">

        <!-- Welcome Header -->
        <div
            class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                    Bảng Điều Khiển
                </h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">
                    Lưu lại kết quả các trận bida giao lưu, tự động xác nhận sau 24h và tính chuỗi thắng thua.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                @auth
                    <button type="button" onclick="triggerCreateMatchModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                        + Ghi nhận trận đấu
                    </button>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                        + Ghi nhận trận đấu
                    </a>
                @endauth

                <a href="{{ route('calendar') }}"
                    class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm flex items-center gap-2">
                    <span>Xem Lịch Đấu</span>
                </a>
            </div>
        </div>

        <!-- 4 Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <div class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Tổng số trận</div>
                <div class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-1.5">{{ $totalMatches }}</div>
                <div class="text-xs sm:text-sm text-gray-400 mt-0.5">Trận chính thức</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <div class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Cơ thủ tham gia</div>
                <div class="text-3xl sm:text-4xl font-extrabold text-gray-900 mt-1.5">{{ $totalMembers }}</div>
                <div class="text-xs sm:text-sm text-gray-400 mt-0.5">Thành viên hoạt động</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <div class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Chờ xác nhận</div>
                <div class="text-3xl sm:text-4xl font-extrabold text-amber-600 mt-1.5">{{ $pendingCount }}</div>
                <div class="text-xs sm:text-sm text-gray-400 mt-0.5">Hạn chót 24 giờ</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <div class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Đang tranh chấp</div>
                <div class="text-3xl sm:text-4xl font-extrabold text-red-600 mt-1.5">{{ $disputedCount }}</div>
                <div class="text-xs sm:text-sm text-gray-400 mt-0.5">Cần Admin xử lý</div>
            </div>
        </div>

        <!-- Spotlight Cards: Quán quân & Chiến thần bao kèo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Top Player -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded">TOP 1</span>
                    <div>
                        <span class="text-xs sm:text-sm font-semibold text-amber-600 uppercase tracking-wider">Quán quân
                            tháng này</span>
                        @if($topPlayer)
                            <div class="text-lg sm:text-xl font-bold text-gray-900 mt-0.5">{{ $topPlayer['user']->name }}</div>
                            <div class="text-sm text-gray-600 font-medium mt-0.5">
                                Thắng: <b class="text-green-600">{{ $topPlayer['wins'] }}</b> trận
                                ({{ $topPlayer['win_rate'] }}%)
                            </div>
                        @else
                            <div class="text-sm text-gray-400 mt-1">Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
                @if($topPlayer)
                    <a href="{{ route('members.show', $topPlayer['user']->username) }}"
                        class="text-sm font-semibold text-blue-600 hover:underline shrink-0">
                        Xem hồ sơ →
                    </a>
                @endif
            </div>

            <!-- Loser of the Month -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="px-2.5 py-1 bg-red-100 text-red-800 text-xs font-bold rounded">KÈO NHIỀU</span>
                    <div>
                        <span class="text-xs sm:text-sm font-semibold text-red-600 uppercase tracking-wider">Chiến thần bao
                            kèo</span>
                        @if($loserOfMonth)
                            <div class="text-lg sm:text-xl font-bold text-gray-900 mt-0.5">{{ $loserOfMonth['user']->name }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium mt-0.5">
                                Thua: <b class="text-red-600">{{ $loserOfMonth['losses'] }}</b> trận •
                                <i>{{ $loserOfMonth['humorous_title'] }}</i>
                            </div>
                        @else
                            <div class="text-sm text-gray-400 mt-1">Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
                @if($loserOfMonth)
                    <a href="{{ route('members.show', $loserOfMonth['user']->username) }}"
                        class="text-sm font-semibold text-blue-600 hover:underline shrink-0">
                        Xem hồ sơ →
                    </a>
                @endif
            </div>
        </div>

        <!-- Main 2-column layout: Recent Matches & Mini Leaderboard -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left 2 Cols: Recent Matches -->
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Trận đấu gần đây</h2>
                    <a href="{{ route('matches.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">
                        Xem tất cả ({{ $totalMatches }}) →
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($recentMatches as $m)
                        @php
                            $isConfirmed = in_array($m->status, ['confirmed', 'resolved']);
                        @endphp
                        <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-xs sm:text-sm">
                                    <span
                                        class="px-2.5 py-0.5 rounded text-xs font-semibold
                                                                                {{ $m->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($m->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $m->status === 'confirmed' ? 'Đã xác nhận' : ($m->status === 'pending' ? 'Chờ xác nhận' : 'Tranh chấp') }}
                                    </span>
                                    <span class="text-gray-400">{{ $m->played_at->format('d/m/Y H:i') }}</span>
                                </div>

                                <div class="text-base font-bold text-gray-900">
                                    <span>{{ $m->team1_names }}</span>
                                    <span class="text-gray-400 font-normal px-1">vs</span>
                                    <span>{{ $m->team2_names }}</span>
                                    @if($isConfirmed)
                                        <span class="ml-1 text-xs sm:text-sm text-green-700 font-medium">({{ $m->winner_names }}
                                            thắng)</span>
                                    @endif
                                </div>

                                <div class="text-sm text-gray-600">
                                    Kèo: <b class="text-gray-900">{{ $m->prize }}</b>
                                </div>
                            </div>

                            <a href="{{ route('matches.show', $m->id) }}"
                                class="text-sm font-semibold text-blue-600 hover:underline shrink-0">
                                Chi tiết →
                            </a>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">
                            Chưa có trận đấu nào được ghi nhận.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right 1 Col: Mini Leaderboard -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden self-start">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Bảng xếp hạng tháng</h2>
                    <a href="{{ route('leaderboard') }}" class="text-sm font-semibold text-blue-600 hover:underline">
                        Đầy đủ →
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($miniLeaderboard as $idx => $item)
                        <div class="p-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="w-5 text-center text-sm font-extrabold {{ $idx === 0 ? 'text-amber-600' : 'text-gray-400' }}">
                                    {{ $idx + 1 }}
                                </span>
                                <img src="{{ $item['user']->avatar_url }}" alt="{{ $item['user']->name }}"
                                    class="w-9 h-9 rounded-full border border-gray-200 object-cover shrink-0">
                                <div class="truncate">
                                    <a href="{{ route('members.show', $item['user']->username) }}"
                                        class="text-sm font-bold text-gray-900 hover:underline block leading-tight truncate">
                                        {{ $item['user']->name }}
                                    </a>
                                    <span class="text-xs text-gray-400">@<span>{{ $item['user']->username }}</span></span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-gray-900">{{ $item['wins'] }} Thắng</div>
                                <div class="text-xs text-gray-500 font-medium">{{ $item['win_rate'] }}%</div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">
                            Chưa có dữ liệu bảng xếp hạng.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
@endsection