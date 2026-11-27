@extends('layouts.app', ['title' => 'Lịch sử trận đấu - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">{{ $viewMode === 'calendar' ? 'Lịch thi đấu' : 'Lịch sử trận đấu' }}</h1>

    @include('partials.match-navigation', ['currentMatchView' => $viewMode === 'calendar' ? 'legacy-calendar' : 'matches.index'])

    @if($viewMode === 'calendar')
        <livewire:match-calendar />
    @else
        <livewire:match-list />
    @endif
</div>
@endsection
