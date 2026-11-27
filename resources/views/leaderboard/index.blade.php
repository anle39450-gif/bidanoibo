@extends('layouts.app', ['title' => 'Bảng xếp hạng - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 id="leaderboard-heading" class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Bảng xếp hạng</h1>

            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                <nav aria-label="Lọc bảng xếp hạng theo thời gian" class="grid grid-cols-3 gap-1 rounded-lg border border-gray-200 bg-white p-1">
                    @foreach(['all' => 'Tất cả', 'month' => 'Tháng này', 'year' => 'Năm nay'] as $periodValue => $label)
                        <a href="{{ route('leaderboard', ['period' => $periodValue, 'streak' => $streak]) }}"
                            @if($period === $periodValue) aria-current="page" @endif
                            class="flex min-h-9 items-center justify-center rounded-md px-2.5 text-center text-xs font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:px-3 sm:text-sm {{ $period === $periodValue ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                @if($viewerRank !== null)
                    <a href="{{ $viewerIsVisible ? '#my-leaderboard-rank' : route('leaderboard', ['period' => $period]).'#my-leaderboard-rank' }}"
                        class="inline-flex min-h-9 w-fit items-center gap-2 rounded-lg border border-primary/15 bg-primary-soft px-3 text-xs font-semibold text-primary transition hover:bg-primary/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:text-sm">
                        Hạng của bạn <span class="tabular-nums">#{{ $viewerRank }}</span>
                    </a>
                @endif
            </div>
        </div>

        <nav aria-label="Lọc theo phong độ hiện tại" class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-semibold text-gray-700">Phong độ hiện tại</span>
            @foreach(['all' => 'Tất cả', 'win' => 'Đang thắng', 'loss' => 'Đang thua'] as $value => $label)
                <a href="{{ route('leaderboard', ['period' => $period, 'streak' => $value === 'all' ? null : $value]) }}"
                    @if(($streak ?? 'all') === $value) aria-current="page" @endif
                    class="inline-flex min-h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-primary aria-[current=page]:border-primary/25 aria-[current=page]:bg-primary-soft aria-[current=page]:text-primary">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
            <table aria-labelledby="leaderboard-heading" class="w-full text-center text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
                    <tr>
                        <th scope="col" class="w-12 px-2 py-3 text-center sm:px-4">Hạng</th>
                        <th scope="col" class="px-2 py-3 sm:px-4">Cơ thủ</th>
                        <th scope="col" class="hidden px-3 py-3 lg:table-cell">Trận</th>
                        <th scope="col" class="px-1.5 py-3 sm:px-3">Thắng</th>
                        <th scope="col" class="hidden px-3 py-3 lg:table-cell">Thua</th>
                        <th scope="col" class="px-2 py-3 whitespace-nowrap sm:px-4">Tỷ lệ<span class="hidden sm:inline"> thắng</span></th>
                        <th scope="col" class="hidden px-3 py-3 lg:table-cell">Phong độ</th>
                        <th scope="col" class="hidden whitespace-nowrap px-3 py-2 lg:table-cell">Chuỗi thắng <span class="block font-normal">Dài nhất</span></th>
                        <th scope="col" class="hidden whitespace-nowrap px-3 py-2 lg:table-cell">Chuỗi thua <span class="block font-normal">Dài nhất</span></th>
                        <th scope="col" class="hidden whitespace-nowrap px-3 py-2 lg:table-cell">5 trận gần nhất <span class="block font-normal">Cũ → mới</span></th>
                        <th scope="col" class="hidden px-4 py-3 xl:table-cell">Danh hiệu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 tabular-nums">
                    @forelse($leaderboard as $entry)
                        @php
                            $rank = $entry['rank'];
                            $player = $entry['user'];
                            $isLeader = $rank === 1 && $entry['wins'] > 0;
                            $medal = match ($rank) {
                                1 => '🥇',
                                2 => '🥈',
                                3 => '🥉',
                                default => null,
                            };
                        @endphp
                        <tr @if($player->id === $viewerId) id="my-leaderboard-rank" tabindex="-1" @endif
                            class="scroll-mt-20 transition-colors hover:bg-gray-50 target:bg-primary-soft target:hover:bg-primary-soft {{ $isLeader ? 'bg-primary-soft/40' : '' }}">
                            <td class="px-2 py-3 text-center sm:px-4">
                                @if($medal)
                                    <span role="img" aria-label="Hạng {{ $rank }}" class="inline-grid size-7 place-items-center text-xl leading-none">{{ $medal }}</span>
                                @else
                                    <span class="inline-grid size-7 place-items-center text-xs font-semibold text-gray-600">{{ $rank }}</span>
                                @endif
                            </td>
                            <th scope="row" class="px-2 py-3 font-normal sm:px-4">
                                <div class="flex items-center justify-center gap-2 sm:gap-3">
                                    @include('partials.member-avatar', ['user' => $player])
                                    <div class="min-w-0">
                                        <a href="{{ route('members.show', $player->username) }}"
                                            class="rounded-sm font-semibold text-gray-900 wrap-anywhere hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                            {{ $player->name }}
                                        </a>
                                        <span class="block text-xs text-gray-500 wrap-anywhere">{{ '@'.$player->username }}</span>
                                        <div class="mt-1 flex flex-wrap justify-center gap-x-2 text-xs text-gray-600 lg:hidden">
                                            <span>{{ $entry['total_matches'] }} trận</span>
                                            <span>{{ $entry['losses'] }} thua</span>
                                        </div>
                                        @if($entry['current_streak_type'])
                                            <p class="mt-1 text-xs font-medium lg:hidden {{ $entry['current_streak_type'] === 'win' ? 'text-green-800' : 'text-red-800' }}">
                                                Đang {{ $entry['current_streak_type'] === 'win' ? 'thắng' : 'thua' }} {{ $entry['current_streak_count'] }} trận
                                            </p>
                                            <p class="mt-1 text-xs text-gray-600 lg:hidden">
                                                Chuỗi dài nhất:
                                                <span class="whitespace-nowrap text-green-800">{{ $entry['longest_win_streak'] }} thắng</span>
                                                · <span class="whitespace-nowrap text-red-800">{{ $entry['longest_loss_streak'] }} thua</span>
                                            </p>
                                            <div class="mt-1 text-xs text-gray-600 lg:hidden">
                                                @include('leaderboard.recent-results', ['results' => $entry['recent_results']])
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </th>
                            <td class="hidden px-3 py-3 font-medium text-gray-700 lg:table-cell">{{ $entry['total_matches'] }}</td>
                            <td class="px-1.5 py-3 font-semibold text-green-700 sm:px-3">{{ $entry['wins'] }}</td>
                            <td class="hidden px-3 py-3 font-semibold text-red-700 lg:table-cell">{{ $entry['losses'] }}</td>
                            <td class="px-2 py-3 text-xs font-semibold text-gray-900 whitespace-nowrap sm:px-4 sm:text-sm">{{ $entry['win_rate'] }}%</td>
                            <td class="hidden px-3 py-3 lg:table-cell">
                                @if($entry['current_streak_type'] === 'win')
                                    <span class="rounded-md bg-green-50 px-1.5 py-1 text-xs font-medium whitespace-nowrap text-green-800">{{ $entry['current_streak_count'] }} thắng</span>
                                @elseif($entry['current_streak_type'] === 'loss')
                                    <span class="rounded-md bg-red-50 px-1.5 py-1 text-xs font-medium whitespace-nowrap text-red-800">{{ $entry['current_streak_count'] }} thua</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="hidden px-3 py-3 font-semibold text-green-800 lg:table-cell">{{ $entry['longest_win_streak'] ?: '—' }}</td>
                            <td class="hidden px-3 py-3 font-semibold text-red-800 lg:table-cell">{{ $entry['longest_loss_streak'] ?: '—' }}</td>
                            <td class="hidden px-3 py-3 lg:table-cell">
                                @include('leaderboard.recent-results', ['results' => $entry['recent_results']])
                            </td>
                            <td class="hidden px-4 py-3 xl:table-cell">
                                <div class="flex flex-wrap justify-center gap-1.5">
                                    @forelse(array_unique(array_column($entry['badges'], 'name')) as $badge)
                                        <span class="rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $badge }}</span>
                                    @empty
                                        <span class="text-gray-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10 text-center text-sm text-gray-600">{{ $streak ? 'Không có cơ thủ phù hợp với bộ lọc trong kỳ này.' : 'Chưa có dữ liệu bảng xếp hạng.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
