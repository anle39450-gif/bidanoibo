@php
    $teams = [
        ['players' => [$match->player1, $match->player1Partner], 'won' => in_array($match->winner_id, [$match->player1_id, $match->player1_partner_id], true)],
        ['players' => [$match->player2, $match->player2Partner], 'won' => in_array($match->winner_id, [$match->player2_id, $match->player2_partner_id], true)],
    ];
@endphp

<div class="grid grid-cols-2 gap-2 sm:gap-3">
    @foreach($teams as $team)
        @php
            $isWinner = $hasResult && $match->winner_id && $team['won'];
        @endphp
        <div class="min-w-0 rounded-xl border p-2.5 sm:p-3.5 {{ $isWinner ? 'border-green-200 bg-green-50/70' : 'border-gray-200 bg-gray-50' }}">
            <p class="mb-2 text-xs font-semibold {{ $isWinner ? 'text-green-800' : 'text-gray-600' }}">
                {{ $isWinner ? 'Bên thắng' : ($hasResult && $match->winner_id ? 'Bên thua' : 'Bên '.$loop->iteration) }}
            </p>
            <ul class="space-y-2">
                @forelse(array_filter($team['players']) as $player)
                    <li class="flex min-w-0 flex-wrap items-center gap-2 sm:flex-nowrap">
                        @include('partials.member-avatar', ['user' => $player])
                        <span class="min-w-0 text-sm font-semibold text-gray-900 wrap-anywhere">{{ $player->name }}</span>
                    </li>
                @empty
                    <li class="text-sm text-gray-600">Chưa rõ cơ thủ</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</div>
