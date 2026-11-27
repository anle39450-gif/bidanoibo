@php
    $team1Won = $match->winner_id !== null && in_array($match->winner_id, [$match->player1_id, $match->player1_partner_id], true);
    $team2Won = $match->winner_id !== null && in_array($match->winner_id, [$match->player2_id, $match->player2_partner_id], true);
    $hasResult = in_array($match->status, ['confirmed', 'resolved'], true) && $team1Won !== $team2Won;
    $teams = [[$match->team1_names, $team1Won], [$match->team2_names, $team2Won]];
@endphp

<div class="flex flex-wrap items-baseline gap-x-2 gap-y-1 text-sm">
    @foreach($teams as [$name, $won])
        <span class="inline-flex min-w-0 flex-wrap items-baseline gap-1.5">
            @unless($loop->first)
                <span class="text-xs text-gray-500">gặp</span>
            @endunless
            <span class="font-semibold text-gray-900 wrap-anywhere">{{ $name }}</span>
            @if($hasResult)
                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $won ? 'bg-green-100 text-green-800' : 'bg-primary-soft text-primary' }}">{{ $won ? 'Thắng' : 'Thua' }}</span>
            @endif
        </span>
    @endforeach
</div>
