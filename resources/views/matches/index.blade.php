@extends('layouts.app', ['title' => 'Lịch sử trận đấu - Bida Nội Bộ'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Lịch Sử Các Trận Đấu Bida</h1>
            <p class="text-sm sm:text-base text-gray-500 mt-1">Xem danh sách các trận đã đấu hoặc chuyển sang dạng lịch theo ngày.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
            <!-- View Mode Switcher -->
            <div class="inline-flex rounded-lg shadow-sm border border-gray-300 bg-white p-1 text-sm font-medium">
                <a href="{{ route('matches.index') }}" class="px-3.5 py-1.5 rounded-md bg-blue-600 text-white font-semibold shadow-sm transition">
                    Danh sách
                </a>
                <a href="{{ route('calendar') }}" class="px-3.5 py-1.5 rounded-md text-gray-600 hover:text-gray-900 transition">
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

    @if($viewMode === 'calendar')
        <livewire:match-calendar />
    @else
        <livewire:match-list />
    @endif
</div>
@endsection
