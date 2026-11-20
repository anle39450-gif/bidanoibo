@extends('layouts.app', ['title' => 'Danh sách cơ thủ - Bida Nội Bộ'])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Danh Sách Cơ Thủ Nội Bộ</h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">Tất cả các thành viên tham gia thi đấu và giao lưu.</p>
            </div>

            @auth
                @if(auth()->user()->isAdmin())
                    <button 
                        type="button" 
                        onclick="openCreateMemberModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer self-start sm:self-auto"
                    >
                        + Tạo mới cơ thủ
                    </button>
                @endif
            @endauth
        </div>

        <!-- Members Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
            @foreach($membersWithStats as $item)
                @php
                    $member = $item['user'];
                    $st = $item['stats'];
                @endphp
                <div
                    class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm space-y-4 hover:border-gray-300 transition">
                    <div class="flex items-center gap-3.5">
                        <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}"
                            class="w-14 h-14 rounded-full border border-gray-200 object-cover shadow-sm">
                        <div class="overflow-hidden">
                            <a href="{{ route('members.show', $member->username) }}"
                                class="text-base sm:text-lg font-bold text-gray-900 hover:text-blue-600 block truncate">
                                {{ $member->name }}
                            </a>
                            <div class="text-xs sm:text-sm text-gray-500">@<span>{{ $member->username }}</span></div>
                            <div class="flex items-center gap-1.5 mt-1">
                                @if($member->isAdmin())
                                    <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2 py-0.5 rounded">Admin</span>
                                @endif
                                @if($member->telegram_id)
                                    <span
                                        class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded">Telegram</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Stats summary -->
                    <div
                        class="grid grid-cols-3 gap-2 bg-gray-50 border border-gray-100 rounded-lg p-3 text-center text-xs sm:text-sm">
                        <div>
                            <div class="text-gray-500">Trận</div>
                            <div class="font-extrabold text-base text-gray-900 mt-0.5">{{ $st['total_matches'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Thắng</div>
                            <div class="font-extrabold text-base text-green-700 mt-0.5">{{ $st['wins'] }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Tỷ lệ</div>
                            <div class="font-extrabold text-base text-gray-900 mt-0.5">{{ $st['win_rate'] }}%</div>
                        </div>
                    </div>

                    <!-- Badges -->
                    <div class="flex flex-wrap gap-1.5 min-h-[24px]">
                        @foreach(array_slice($st['badges'], 0, 3) as $bg)
                            <span class="bg-gray-100 text-gray-700 text-xs font-medium px-2 py-0.5 rounded border border-gray-200">
                                {{ $bg['name'] }}
                            </span>
                        @endforeach
                    </div>

                    <a href="{{ route('members.show', $member->username) }}"
                        class="block w-full py-2 text-center text-sm font-semibold text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition">
                        Xem Hồ Sơ & Đối Đầu →
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection