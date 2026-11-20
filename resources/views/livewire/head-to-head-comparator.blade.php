<div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-200">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Lịch Sử Đối Đầu Trực Tiếp (Head-to-Head)</h3>
            <p class="text-sm text-gray-500">So sánh thành tích giữa hai cơ thủ khi chạm trán.</p>
        </div>

        <div class="w-full sm:w-64">
            <select wire:model.live="selectedOpponentId" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                @foreach($opponents as $op)
                    <option value="{{ $op->id }}">vs {{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($selectedOpponent && $h2hData)
        <div class="grid grid-cols-3 items-center gap-4 bg-gray-50 rounded-xl p-6 text-center border border-gray-200">
            <div>
                <div class="font-bold text-gray-900 text-base">{{ $member->name }}</div>
                <div class="text-3xl sm:text-4xl font-extrabold text-green-700 mt-1">{{ $h2hData['user1_wins'] }} <span class="text-xs sm:text-sm font-medium text-gray-500">thắng</span></div>
                <div class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">{{ $h2hData['user1_win_rate'] }}%</div>
            </div>

            <div class="text-xs sm:text-sm text-gray-400 font-bold uppercase">
                <span class="bg-white border border-gray-300 px-3 py-1.5 rounded-lg font-bold text-gray-800 shadow-sm">VS</span>
                <div class="mt-2 font-medium text-gray-500">Tổng {{ $h2hData['total_matches'] }} trận</div>
            </div>

            <div>
                <div class="font-bold text-gray-900 text-base">{{ $selectedOpponent->name }}</div>
                <div class="text-3xl sm:text-4xl font-extrabold text-blue-700 mt-1">{{ $h2hData['user2_wins'] }} <span class="text-xs sm:text-sm font-medium text-gray-500">thắng</span></div>
                <div class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">{{ $h2hData['user2_win_rate'] }}%</div>
            </div>
        </div>

        @if($h2hData['total_matches'] > 0)
            <div class="space-y-2.5 pt-2">
                <div class="text-xs sm:text-sm font-bold text-gray-700 uppercase tracking-wider">Các trận đối đầu gần đây</div>
                <div class="divide-y divide-gray-100 border border-gray-200 rounded-lg overflow-hidden bg-white text-sm">
                    @foreach($h2hData['matches']->take(5) as $dm)
                        @php $isWin = ($dm->winner_id === $member->id); @endphp
                        <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between hover:bg-gray-50 transition gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="px-2.5 py-0.5 rounded text-xs font-bold {{ $isWin ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $isWin ? 'THẮNG' : 'THUA' }}
                                </span>
                                <span class="text-gray-800">Kèo: <b>{{ $dm->prize }}</b></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-gray-400 font-mono text-xs">{{ $dm->played_at->format('d/m/Y') }}</span>
                                <a href="{{ route('matches.show', $dm->id) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-sm">Chi tiết →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="p-8 text-center text-sm text-gray-500">
                Hai cơ thủ chưa từng chạm trán trong trận đấu chính thức nào.
            </div>
        @endif
    @endif
</div>
