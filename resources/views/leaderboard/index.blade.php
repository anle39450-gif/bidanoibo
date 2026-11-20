@extends('layouts.app', ['title' => 'Bảng xếp hạng - Bida Nội Bộ'])

@section('content')
<div class="space-y-6">
    <!-- Header with Period Selector -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Bảng Xếp Hạng Cơ Thủ Bida</h1>
            <p class="text-sm sm:text-base text-gray-500 mt-1">Xếp hạng theo số trận thắng, tỷ lệ thắng và phong độ chuỗi trận.</p>
        </div>

        <!-- Period Tabs -->
        <div class="inline-flex rounded-lg shadow-sm border border-gray-300 bg-white p-1 text-sm font-medium">
            <a href="{{ route('leaderboard', ['period' => 'all']) }}" class="px-3.5 py-1.5 rounded-md transition {{ $period === 'all' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                Toàn thời gian
            </a>
            <a href="{{ route('leaderboard', ['period' => 'month']) }}" class="px-3.5 py-1.5 rounded-md transition {{ $period === 'month' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                Tháng này
            </a>
            <a href="{{ route('leaderboard', ['period' => 'year']) }}" class="px-3.5 py-1.5 rounded-md transition {{ $period === 'year' ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'text-gray-600 hover:text-gray-900' }}">
                Năm nay
            </a>
        </div>
    </div>

    <!-- Spotlight: Top Player & Loser -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if($topPlayer)
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex items-center gap-4">
                <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded">TOP 1</span>
                <div>
                    <span class="text-xs sm:text-sm font-semibold text-amber-700 uppercase">Quán quân {{ $period === 'month' ? 'tháng này' : ($period === 'year' ? 'năm nay' : 'toàn thời gian') }}</span>
                    <a href="{{ route('members.show', $topPlayer['user']->username) }}" class="text-lg sm:text-xl font-bold text-gray-900 hover:underline block mt-0.5">
                        {{ $topPlayer['user']->name }}
                    </a>
                    <div class="text-sm text-gray-600 mt-0.5">
                        Thắng: <b class="text-green-700">{{ $topPlayer['wins'] }}</b> trận ({{ $topPlayer['win_rate'] }}%) 
                        @if($topPlayer['current_streak_type'] === 'win')
                            • Chuỗi {{ $topPlayer['current_streak_count'] }}W
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if($loser)
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex items-center gap-4">
                <span class="px-2.5 py-1 bg-red-100 text-red-800 text-xs font-bold rounded">CHIẾN THẦN</span>
                <div>
                    <span class="text-xs sm:text-sm font-semibold text-red-700 uppercase">Chiến thần bao kèo</span>
                    <a href="{{ route('members.show', $loser['user']->username) }}" class="text-lg sm:text-xl font-bold text-gray-900 hover:underline block mt-0.5">
                        {{ $loser['user']->name }}
                    </a>
                    <div class="text-sm text-gray-600 mt-0.5">
                        Thua: <b class="text-red-700">{{ $loser['losses'] }}</b> trận • <i>{{ $loser['humorous_title'] }}</i>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Leaderboard Table -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider text-xs">
                    <tr>
                        <th class="py-3.5 px-4">Hạng</th>
                        <th class="py-3.5 px-4">Cơ thủ</th>
                        <th class="py-3.5 px-4 text-center">Tổng trận</th>
                        <th class="py-3.5 px-4 text-center">Thắng</th>
                        <th class="py-3.5 px-4 text-center">Thua</th>
                        <th class="py-3.5 px-4 text-center">Tỷ lệ thắng</th>
                        <th class="py-3.5 px-4 text-center">Chuỗi hiện tại</th>
                        <th class="py-3.5 px-4 text-center">Chuỗi MAX</th>
                        <th class="py-3.5 px-4">Danh hiệu vui</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($leaderboard as $item)
                        @php
                            $rank = $item['rank'];
                            $user = $item['user'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="py-3.5 px-4 font-extrabold text-gray-900">
                                @if($rank === 1)
                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 font-bold text-xs">Hạng 1</span>
                                @elseif($rank === 2)
                                    <span class="px-2 py-0.5 rounded bg-gray-200 text-gray-800 font-bold text-xs">Hạng 2</span>
                                @elseif($rank === 3)
                                    <span class="px-2 py-0.5 rounded bg-orange-100 text-orange-900 font-bold text-xs">Hạng 3</span>
                                @else
                                    <span class="text-gray-600 font-semibold text-xs">{{ $rank }}</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full border border-gray-200 object-cover">
                                    <div>
                                        <a href="{{ route('members.show', $user->username) }}" class="font-bold text-gray-900 hover:underline">
                                            {{ $user->name }}
                                        </a>
                                        <span class="text-xs text-gray-400 block">@<span>{{ $user->username }}</span></span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center text-gray-700 font-medium">
                                {{ $item['total_matches'] }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-green-700">
                                {{ $item['wins'] }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-red-600">
                                {{ $item['losses'] }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-gray-900">
                                {{ $item['win_rate'] }}%
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @if($item['current_streak_type'] === 'win')
                                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-green-100 text-green-800">
                                        W{{ $item['current_streak_count'] }}
                                    </span>
                                @elseif($item['current_streak_type'] === 'loss')
                                    <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">
                                        L{{ $item['current_streak_count'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center font-medium text-gray-600">
                                {{ $item['longest_win_streak'] }}
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse($item['badges'] as $b)
                                        <span class="px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                            {{ $b['name'] }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400">-</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500">Chưa có dữ liệu bảng xếp hạng.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
