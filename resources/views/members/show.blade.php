@extends('layouts.app', ['title' => 'Hồ sơ ' . $member->name . ' - Bida Nội Bộ'])

@section('content')
    <div class="space-y-6">

        <!-- Profile Card -->
        <div
            class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6">
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 text-center sm:text-left">
                <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}"
                    class="w-16 h-16 rounded-full border border-gray-200 object-cover">

                <div class="space-y-1">
                    <div class="flex items-center justify-center sm:justify-start gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ $member->name }}</h1>
                        <span class="text-xs text-gray-500 font-mono">@<span>{{ $member->username }}</span></span>
                        @if($member->isAdmin())
                            <span class="bg-amber-100 text-amber-800 text-[11px] font-semibold px-2 py-0.5 rounded">Quản trị
                                viên</span>
                        @endif
                    </div>

                    <div class="text-xs text-gray-500">
                        Gia nhập: {{ $member->created_at->format('d/m/Y') }}
                    </div>

                    <!-- Badges -->
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1 pt-1">
                        @forelse($stats['badges'] as $b)
                            <span class="bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded border border-gray-200">
                                {{ $b['name'] }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">Chưa có danh hiệu.</span>
                        @endforelse
                    </div>
                </div>
            </div>

            @auth
                @if(auth()->id() !== $member->id)
                    <button type="button" onclick="triggerCreateMatchModal({{ $member->id }})"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-4 py-2 rounded-md shadow-sm transition cursor-pointer">
                        + Ghi nhận trận với {{ $member->name }}
                    </button>
                @else
                    <button type="button" onclick="openChangePasswordModal()"
                        class="bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 text-xs font-semibold px-4 py-2 rounded-md shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        <span>Đổi mật khẩu</span>
                    </button>
                @endif
            @endauth
        </div>

        <!-- Telegram Card (for owner or admin) -->
        @auth
            @if(auth()->id() === $member->id || auth()->user()->isAdmin())
                <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <h3 class="text-sm font-bold text-gray-900">Liên Kết Telegram Bot</h3>
                        @if($member->telegram_id)
                            <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                Đã liên kết (ID: {{ $member->telegram_id }})
                            </span>
                        @else
                            <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                Chưa liên kết
                            </span>
                        @endif
                    </div>

                    @if(!$member->telegram_id)
                        <div class="text-xs text-gray-600 space-y-2">
                            <p>Để nhận thông báo và ghi nhận trận nhanh qua Telegram:</p>
                            <ol class="list-decimal list-inside space-y-1 text-gray-500">
                                <li>Mở Telegram và chat với bot: <b>@<span>{{ $botConfig->bot_username ?? 'BidaNoiBoBot' }}</span></b>
                                </li>
                                <li>Gửi lệnh liên kết: <code
                                        class="bg-gray-100 px-1 py-0.5 rounded font-mono text-gray-800">/start {{ $member->telegram_link_code ?? 'LINK' }}</code>
                                </li>
                            </ol>

                            <div class="pt-2 flex items-center gap-2">
                                @if($botConfig->bot_username)
                                    <a href="https://t.me/{{ $botConfig->bot_username }}?start={{ $member->telegram_link_code }}"
                                        target="_blank"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs px-3 py-1.5 rounded transition">
                                        Mở Telegram kết nối ngay
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('members.telegram-code', $member->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-medium px-3 py-1.5 rounded transition">
                                        Đổi mã liên kết
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endauth

        <!-- Performance Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="bg-white border border-gray-200 rounded-lg p-4 text-center shadow-sm">
                <div class="text-xs font-medium text-gray-500 uppercase">Tổng số trận</div>
                <div class="text-xl font-bold text-gray-900 mt-1">{{ $stats['total_matches'] }}</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4 text-center shadow-sm">
                <div class="text-xs font-medium text-gray-500 uppercase">Thắng</div>
                <div class="text-xl font-bold text-green-700 mt-1">{{ $stats['wins'] }}</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4 text-center shadow-sm">
                <div class="text-xs font-medium text-gray-500 uppercase">Thua</div>
                <div class="text-xl font-bold text-red-600 mt-1">{{ $stats['losses'] }}</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4 text-center shadow-sm">
                <div class="text-xs font-medium text-gray-500 uppercase">Tỷ lệ thắng</div>
                <div class="text-xl font-bold text-gray-900 mt-1">{{ $stats['win_rate'] }}%</div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4 text-center shadow-sm">
                <div class="text-xs font-medium text-gray-500 uppercase">Chuỗi hiện tại</div>
                <div class="text-sm font-bold mt-1">
                    @if($stats['current_streak_type'] === 'win')
                        <span class="text-green-700">{{ $stats['current_streak_count'] }} Thắng</span>
                    @elseif($stats['current_streak_type'] === 'loss')
                        <span class="text-red-600">{{ $stats['current_streak_count'] }} Thua</span>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-4 text-center shadow-sm">
                <div class="text-xs font-medium text-gray-500 uppercase">Chuỗi MAX</div>
                <div class="text-xl font-bold text-gray-900 mt-1">{{ $stats['longest_win_streak'] }}</div>
            </div>
        </div>

        <!-- Livewire Head-to-Head Component -->
        <livewire:head-to-head-comparator :member="$member" />

        <!-- Recent Match History -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-sm font-bold text-gray-900">Trận đấu gần đây của {{ $member->name }}</h3>
            </div>

            <div class="divide-y divide-gray-100 text-xs">
                @forelse($recentMatches as $m)
                    @php
                        $isConfirmed = in_array($m->status, ['confirmed', 'resolved']);
                        $isWin = ($m->winner_id === $member->id);
                    @endphp
                    <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <span
                                class="px-2 py-0.5 rounded font-bold text-[11px] {{ $isConfirmed ? ($isWin ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') : 'bg-amber-100 text-amber-800' }}">
                                {{ $isConfirmed ? ($isWin ? 'THẮNG' : 'THUA') : strtoupper($m->status) }}
                            </span>
                            <div>
                                <div class="font-semibold text-gray-900 text-sm">
                                    {{ $m->player1->name }} vs {{ $m->player2->name }}
                                </div>
                                <div class="text-gray-500 mt-0.5">
                                    Kèo: <b>{{ $m->prize }}</b> @if($m->notes) • <i>"{{ $m->notes }}"</i> @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <span class="text-gray-400 font-mono">{{ $m->played_at->format('d/m/Y') }}</span>
                            <a href="{{ route('matches.show', $m->id) }}" class="text-blue-600 hover:underline font-medium">
                                Chi tiết
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500">Chưa có trận đấu nào.</div>
                @endforelse
            </div>
        </div>

    </div>
@endsection