@extends('layouts.app', ['title' => 'Hồ sơ ' . $member->name . ' - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
    <div class="space-y-4">
        <section aria-labelledby="member-heading" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3.5 sm:px-5">
                <div class="flex min-w-0 items-center gap-3">
                    @include('partials.member-avatar', ['user' => $member])
                    <div class="min-w-0">
                        <h1 id="member-heading" class="text-xl font-bold text-gray-900 wrap-anywhere">{{ $member->name }}</h1>
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-600">
                            <span class="wrap-anywhere">{{ '@'.$member->username }}</span>
                            @if($member->isAdmin())
                                <span class="rounded-md bg-amber-50 px-1.5 py-0.5 font-medium text-amber-800">Quản trị viên</span>
                            @endif
                            @foreach(array_unique(array_column($stats['badges'], 'name')) as $badge)
                                <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-gray-700">{{ $badge }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                @auth
                    @if(auth()->id() !== $member->id)
                        <button type="button" onclick="triggerCreateMatchModal({{ $member->id }})"
                            class="min-h-9 cursor-pointer rounded-lg bg-primary px-3 text-xs font-semibold text-white transition hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Ghi trận với {{ $member->name }}
                        </button>
                    @else
                        <button type="button" onclick="openChangePasswordModal()"
                            class="min-h-9 cursor-pointer rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Đổi mật khẩu
                        </button>
                    @endif
                @endauth
            </div>

            <dl class="grid grid-cols-4 divide-x divide-gray-100 border-t border-gray-100 bg-gray-50/70 py-3 text-center tabular-nums">
                <div class="px-1">
                    <dt class="text-xs text-gray-600">Số trận</dt>
                    <dd class="mt-0.5 text-lg font-bold text-gray-900">{{ $stats['total_matches'] }}</dd>
                </div>
                <div class="px-1">
                    <dt class="text-xs text-gray-600">Thắng</dt>
                    <dd class="mt-0.5 text-lg font-bold text-green-700">{{ $stats['wins'] }}</dd>
                </div>
                <div class="px-1">
                    <dt class="text-xs text-gray-600">Thua</dt>
                    <dd class="mt-0.5 text-lg font-bold text-red-700">{{ $stats['losses'] }}</dd>
                </div>
                <div class="px-1">
                    <dt class="text-xs text-gray-600">Tỷ lệ thắng</dt>
                    <dd class="mt-0.5 text-lg font-bold text-gray-900">{{ $stats['win_rate'] }}%</dd>
                </div>
            </dl>
        </section>

        <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]">
            <section aria-labelledby="recent-matches-heading" class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                <h2 id="recent-matches-heading" class="border-b border-gray-100 px-4 py-3.5 text-base font-bold text-gray-900 sm:px-5">Trận gần đây</h2>

                <ul class="divide-y divide-gray-100">
                    @forelse($recentMatches as $recentMatch)
                        @php
                            $isWin = in_array($member->id, [$recentMatch->winner_id, $recentMatch->winner_partner_id], true);
                            $result = match ($recentMatch->status) {
                                'confirmed', 'resolved' => $isWin ? 'Thắng' : 'Thua',
                                'pending' => 'Chờ xác nhận',
                                'disputed' => 'Khiếu nại',
                                'cancelled' => 'Đã hủy',
                                default => $recentMatch->status,
                            };
                            $resultStyle = match ($result) {
                                'Thắng' => 'bg-green-50 text-green-800',
                                'Thua' => 'bg-red-50 text-red-800',
                                'Chờ xác nhận', 'Khiếu nại' => 'bg-amber-50 text-amber-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <li>
                            <a href="{{ route('matches.show', $recentMatch->id) }}"
                                class="flex min-w-0 items-start justify-between gap-2 px-4 py-3 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary sm:items-center sm:px-5">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="text-sm font-semibold text-gray-900 wrap-anywhere">{{ $recentMatch->team1_names }} vs {{ $recentMatch->team2_names }}</span>
                                        <span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $resultStyle }}">{{ $result }}</span>
                                    </div>
                                    @if($recentMatch->prize)
                                        <p class="mt-1 text-xs text-gray-600 wrap-anywhere">{{ $recentMatch->prize }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 text-xs text-gray-600 tabular-nums">
                                    <time datetime="{{ $recentMatch->played_at->toDateString() }}">{{ $recentMatch->played_at->format('d/m/Y') }}</time>
                                    <svg viewBox="0 0 24 24" class="hidden size-3.5 text-primary sm:block" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                    </svg>
                                </span>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-gray-600 sm:px-5">Chưa có trận nào.</li>
                    @endforelse
                </ul>
            </section>

            <livewire:head-to-head-comparator :member="$member" />
        </div>

        @auth
            @if(auth()->id() === $member->id || auth()->user()->isAdmin())
                <details class="rounded-xl border border-gray-200 bg-white shadow-xs">
                    <summary class="cursor-pointer rounded-xl px-4 py-3.5 text-sm font-semibold text-gray-900 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary sm:px-5">
                        Liên kết Telegram
                    </summary>
                    <div class="space-y-3 border-t border-gray-100 px-4 py-4 text-sm sm:px-5">
                        @if($member->telegram_id)
                            <p class="font-medium text-green-800">Đã liên kết (ID: {{ $member->telegram_id }})</p>
                        @else
                            <p class="text-gray-700">Gửi <code class="rounded bg-gray-100 px-1.5 py-0.5">/start {{ $member->telegram_link_code ?? 'LINK' }}</code> cho {{ '@'.($botConfig->bot_username ?? 'BidaNoiBoBot') }} để liên kết.</p>
                            <div class="flex flex-wrap items-center gap-2">
                                @if($botConfig->bot_username)
                                    <a href="https://t.me/{{ $botConfig->bot_username }}?start={{ $member->telegram_link_code }}"
                                        target="_blank" rel="noopener"
                                        class="inline-flex min-h-9 items-center rounded-lg bg-primary px-3 text-xs font-semibold text-white hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                        Mở Telegram
                                    </a>
                                @endif
                                <form method="POST" action="{{ route('members.telegram-code', $member->id) }}">
                                    @csrf
                                    <button type="submit" class="min-h-9 cursor-pointer rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                        Đổi mã liên kết
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </details>
            @endif
        @endauth
    </div>
@endsection
