@extends('layouts.app', ['title' => 'Bảng điều khiển - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
    @php
        $dashboardUser = auth()->user();
        $hasDashboardTasks = $myPendingMatches->isNotEmpty()
            || $myPendingSideBetInvites->isNotEmpty()
            || $matchesToPay->isNotEmpty()
            || $matchesAwaitingReceipt->isNotEmpty()
            || $myUnpaidSideBets->isNotEmpty()
            || $myUnpaidReceivables->isNotEmpty()
            || $myUnpaidSideBetReceivables->isNotEmpty();
    @endphp

    <div class="space-y-4">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Bảng điều khiển</h1>

        @auth
            <div class="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <section aria-labelledby="dashboard-stats-heading" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                    <div class="flex min-h-12 flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-2.5 sm:px-5">
                        <h2 id="dashboard-stats-heading" class="text-base font-bold text-gray-900">Thành tích của bạn</h2>
                        <a href="{{ route('members.show', $dashboardUser->username) }}"
                            class="group inline-flex items-center gap-1.5 rounded-sm text-sm font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                            Xem hồ sơ
                            <svg class="size-4 motion-safe:transition-transform motion-safe:group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                            </svg>
                        </a>
                    </div>
                    @include('dashboard.summary-metrics', [
                        'columns' => 'lg:grid-cols-4',
                        'metrics' => [
                            ['Số trận', $myStats['total_matches'], 'bg-gray-100 text-gray-700', 'text-gray-900', 'M8 7h8m-8 5h8m-8 5h5M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z'],
                            ['Trận thắng', $myStats['wins'], 'bg-green-50 text-green-700', 'text-green-700', 'M8 21h8m-4-4v4M7 4h10v6a5 5 0 0 1-10 0V4ZM7 6H4v2a3 3 0 0 0 3 3m10-5h3v2a3 3 0 0 1-3 3'],
                            ['Trận thua', $myStats['losses'], 'bg-red-50 text-red-700', 'text-red-700', 'm4 7 6 6 4-4 6 6m-5 0h5v-5'],
                            ['Tỷ lệ thắng', $myStats['win_rate'].'%', 'bg-primary-soft text-primary', 'text-primary', 'M6 18 18 6M9 7a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm10 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z'],
                        ],
                    ])
                </section>

                <section aria-labelledby="dashboard-payments-heading" aria-describedby="dashboard-payments-note" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                    <div class="flex min-h-12 flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-2.5 sm:px-5">
                        <h2 id="dashboard-payments-heading" class="text-base font-bold text-gray-900">Trả kèo</h2>
                        @if($matchesToPay->isNotEmpty() || $matchesAwaitingReceipt->isNotEmpty() || $myUnpaidSideBets->isNotEmpty())
                            <a href="#tong-quan-keo" class="rounded-sm text-sm font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                Xem kèo
                            </a>
                        @endif
                    </div>
                    <p id="dashboard-payments-note" class="sr-only">Kèo đã trả bao gồm kèo đang chờ đối thủ xác nhận.</p>
                    @include('dashboard.summary-metrics', [
                        'metrics' => [
                            ['Kèo đã trả', $dashboardUser->paid_debts_count, 'bg-green-50 text-green-700', 'text-green-700', 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0ZM8 12l3 3 5-6'],
                            ['Kèo chưa trả', $dashboardUser->unpaid_debts_count, 'bg-amber-50 text-amber-800', 'text-amber-900', 'M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Zm-10-5v5l3 2'],
                        ],
                    ])
                </section>
            </div>
        @endauth

        <div class="grid items-start gap-4 {{ $hasDashboardTasks ? 'xl:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]' : '' }}">
            @if($hasDashboardTasks)
                <section id="tong-quan-keo" aria-labelledby="dashboard-tasks-heading" class="min-w-0 scroll-mt-20 rounded-xl border border-gray-200 bg-white shadow-xs">
                    <h2 id="dashboard-tasks-heading" class="border-b border-gray-100 px-4 py-3.5 text-base font-bold text-gray-900 sm:px-5">Việc cần xử lý</h2>
                    <div class="space-y-3 px-4 py-3.5 sm:px-5">
                        @if($myPendingMatches->isNotEmpty())
                            <div id="tran-can-xac-nhan" class="scroll-mt-20 rounded-lg border border-amber-200 bg-amber-50/40">
                                <h3 class="flex items-center justify-between gap-2 border-b border-amber-100 px-3 py-2.5 text-sm font-semibold text-amber-900">
                                    Trận cần bạn xác nhận
                                    <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-xs tabular-nums">{{ $myPendingMatches->total() }}</span>
                                </h3>
                                <ul class="divide-y divide-amber-100" x-data="{
                                    now: Date.now(),
                                    timer: null,
                                    init() {
                                        this.timer = setInterval(() => { this.now = Date.now() }, 1000)
                                    },
                                    destroy() {
                                        clearInterval(this.timer)
                                    },
                                    remaining(deadline) {
                                        const milliseconds = deadline * 1000 - this.now
                                        if (milliseconds <= 0) return 'Đã hết hạn'
                                        const minutes = Math.floor(milliseconds / 60000)
                                        if (minutes === 0) return 'Còn dưới 1 phút'
                                        return 'Còn ' + Math.floor(minutes / 60) + ' giờ ' + (minutes % 60) + ' phút'
                                    }
                                }">
                                    @foreach($myPendingMatches as $pendingMatch)
                                        <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-3">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 wrap-anywhere">{{ $pendingMatch->team1_names }} vs {{ $pendingMatch->team2_names }}</p>
                                                @if($pendingMatch->auto_confirm_at)
                                                    @php
                                                        $remainingSeconds = $pendingMatch->getRemainingSecondsForAutoConfirm();
                                                        $remainingMinutes = intdiv($remainingSeconds, 60);
                                                    @endphp
                                                    <p class="mt-1 text-xs font-semibold text-amber-900 tabular-nums" x-text="remaining({{ $pendingMatch->auto_confirm_at->getTimestamp() }})">
                                                        @if($remainingSeconds === 0)
                                                            Đã hết hạn
                                                        @elseif($remainingMinutes === 0)
                                                            Còn dưới 1 phút
                                                        @else
                                                            Còn {{ intdiv($remainingMinutes, 60) }} giờ {{ $remainingMinutes % 60 }} phút
                                                        @endif
                                                    </p>
                                                @endif
                                            </div>
                                            <a href="{{ route('matches.show', $pendingMatch->id) }}"
                                                class="inline-flex min-h-9 items-center rounded-lg bg-primary px-3 text-xs font-semibold text-white transition hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Xem trận</a>
                                        </li>
                                    @endforeach
                                </ul>
                                @include('dashboard.list-pagination', ['paginator' => $myPendingMatches, 'paginationLabel' => 'trận cần xác nhận'])
                            </div>
                        @endif

                        <!-- Lời rủ bắt kèo ngoài đang chờ bạn phản hồi -->
                        @if(isset($myPendingSideBetInvites) && $myPendingSideBetInvites->isNotEmpty())
                            <div id="loi-moi-keo-ngoai" class="scroll-mt-20 space-y-2 rounded-lg border border-gray-200 px-3 py-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base">🎯</span>
                                        <span class="text-sm font-semibold text-gray-900">
                                            Lời rủ vào kèo ngoài đang chờ bạn chốt ({{ $myPendingSideBetInvites->total() }} kèo)
                                        </span>
                                    </div>
                                </div>

                                <div class="divide-y divide-gray-100">
                                    @foreach($myPendingSideBetInvites as $invite)
                                        <div class="flex flex-wrap items-start justify-between gap-2.5 py-3 text-sm">
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-extrabold text-gray-900">{{ $invite->creator->name }}</span>
                                                    <span class="text-gray-500 text-xs">rủ bạn bắt kèo trận:</span>
                                                    <span
                                                        class="font-bold text-gray-800 bg-white px-2 py-0.5 rounded border border-purple-200 text-xs">
                                                        {{ $invite->challenge->challenger_team_names }} vs
                                                        {{ $invite->challenge->opponent_team_names }}
                                                    </span>
                                                </div>
                                                <div class="text-xs text-purple-900 flex items-center gap-2 flex-wrap">
                                                    <span>👉 {{ $invite->creator->name }} bắt <b
                                                            class="text-rose-700">{{ $invite->creator_team_name }}</b></span>
                                                    <span>• Bạn nhận phe: <b class="text-emerald-700">{{ $invite->target_team_name }}</b></span>
                                                    <span>• Món kèo: <b
                                                            class="bg-amber-100 text-amber-900 px-1.5 py-0.2 rounded border border-amber-200">{{ $invite->prize }}</b></span>
                                                    @if($invite->notes)
                                                        <span class="text-gray-500 italic">"{{ $invite->notes }}"</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button type="button" onclick="handleAcceptSideBet({{ $invite->id }})"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-lg shadow-2xs transition cursor-pointer">
                                                    ✓ Nhận kèo
                                                </button>
                                                <button type="button" onclick="handleDeclineSideBet({{ $invite->id }})"
                                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs px-3 py-1.5 rounded-lg transition cursor-pointer">
                                                    ✕ Từ chối
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @include('dashboard.list-pagination', ['paginator' => $myPendingSideBetInvites, 'paginationLabel' => 'lời mời kèo ngoài'])
                            </div>
                        @endif

                        @if($matchesToPay->isNotEmpty())
                            <div id="keo-can-tra" class="scroll-mt-20 rounded-lg border border-gray-200">
                                <h3 class="px-3 py-2.5 text-sm font-semibold text-gray-900">Kèo cần trả</h3>
                                @include('dashboard.prize-payment-list', ['paymentMatches' => $matchesToPay])
                                @include('dashboard.list-pagination', ['paginator' => $matchesToPay, 'paginationLabel' => 'kèo cần trả'])
                            </div>
                        @endif

                        <!-- Kèo ngoài bạn thua chưa trả -->
                        @if(isset($myUnpaidSideBets) && $myUnpaidSideBets->isNotEmpty())
                            <div id="keo-ngoai-can-tra" class="scroll-mt-20 rounded-lg border border-gray-200 px-3 py-2.5">
                                <div class="mb-2.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <span class="flex items-center gap-1.5">
                                        <span>🎯</span>
                                        <span>Kèo ngoài bạn thua chưa trả ({{ $myUnpaidSideBets->total() }} kèo)</span>
                                    </span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @foreach($myUnpaidSideBets as $sb)
                                        <div class="flex flex-wrap items-start justify-between gap-2.5 py-3 text-sm">
                                            <div>
                                                <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                                    <span>Thua kèo ngoài trước: <b
                                                            class="text-blue-700 font-black">{{ $sb->winner?->name }}</b></span>
                                                    @if($sb->prize_status === 'paid')
                                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded font-bold">Đã trả (Chờ
                                                            đối thủ xác nhận)</span>
                                                    @else
                                                        <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa
                                                            trả</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-600 mt-0.5">
                                                    Trận: <span class="text-gray-700 font-medium">{{ $sb->challenge->challenger_team_names }} vs
                                                        {{ $sb->challenge->opponent_team_names }}</span> •
                                                    Món kèo: <b
                                                        class="text-amber-900 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded">{{ $sb->prize }}</b>
                                                    @if($sb->notes)
                                                        <span class="text-gray-500 italic ml-1">"{{ $sb->notes }}"</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($sb->prize_status === 'unpaid')
                                                    <button type="button" onclick="handlePaySideBet({{ $sb->id }})"
                                                        class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                        Đã trả kèo
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @include('dashboard.list-pagination', ['paginator' => $myUnpaidSideBets, 'paginationLabel' => 'kèo ngoài cần trả'])
                            </div>
                        @endif

                        <!-- If there are unpaid receivables (kèo người khác chưa trả cho bạn), show list! -->
                        @if($myUnpaidReceivables->isNotEmpty())
                            <div id="keo-cho-nhan" class="scroll-mt-20 rounded-lg border border-gray-200 px-3 py-2.5">
                                <div class="mb-2.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <span>Kèo chờ nhận</span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @foreach($myUnpaidReceivables as $recMatch)
                                        @php
                                            $userSlot = $recMatch->getPrizeSlotForUser(auth()->user());
                                            $userStatus = $recMatch->getPrizeStatusForUser(auth()->user());
                                            $counterpart = $recMatch->getCounterpartForUser(auth()->user());
                                            $userNotes = $recMatch->getPrizeNotesForUser(auth()->user());
                                            $userFeedback = $recMatch->getPrizeFeedbackForUser(auth()->user());
                                        @endphp
                                        <div class="flex flex-wrap items-start justify-between gap-2.5 py-3 text-sm">
                                            <div>
                                                <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                                    @if($recMatch->isDoubles())
                                                        <span
                                                            class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 text-[11px] font-extrabold px-2 py-0.5 rounded-full border border-purple-200">
                                                            <span>👥</span> Kèo đôi tách chéo
                                                        </span>
                                                        <span><b class="text-red-600 font-black text-sm">{{ $counterpart?->name }}</b> nợ riêng bạn</span>
                                                    @else
                                                        <span>Thắng trước: <span class="text-red-600">{{ $recMatch->loser_names }}</span></span>
                                                    @endif
                                                    <span
                                                        class="text-xs text-gray-400 font-normal font-mono">({{ $recMatch->played_at->format('d/m/Y') }})</span>
                                                    @if($userStatus === 'paid')
                                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded font-bold">Đối thủ
                                                            đã trả (Cần bạn xác nhận)</span>
                                                    @elseif($userStatus === 'rejected')
                                                        <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded font-bold">Bạn đã phản hồi
                                                            Không đồng ý</span>
                                                    @else
                                                        <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa
                                                            trả</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-600 mt-0.5">
                                                    @if($recMatch->isDoubles())
                                                        Trận đôi: <span class="text-gray-700 font-medium">{{ $recMatch->team1_names }} vs
                                                            {{ $recMatch->team2_names }}</span> •
                                                    @endif
                                                    Kèo chờ nhận: <b
                                                        class="text-blue-900 bg-blue-100 border border-blue-200 px-2 py-0.5 rounded">{{ $recMatch->prize }}</b>
                                                    @if($userNotes)
                                                        <span class="text-gray-500 italic ml-1">Ghi chú đối thủ: "{{ $userNotes }}"</span>
                                                    @endif
                                                </div>
                                                @if($userStatus === 'rejected' && $userFeedback)
                                                    <div class="text-xs text-red-600 mt-1 italic font-medium">
                                                        Lý do bạn từ chối: "{{ $userFeedback }}"
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($userStatus === 'paid')
                                                    <form method="POST" action="{{ route('matches.confirm-prize', $recMatch->id) }}"
                                                        onsubmit="return confirm('Xác nhận bạn ĐÃ OK / đã nhận đủ kèo \'{{ $recMatch->prize }}\' {{ $recMatch->isDoubles() && $counterpart ? 'từ ' . $counterpart->name : '' }}?')">
                                                        @csrf
                                                        <input type="hidden" name="pair_slot" value="{{ $userSlot }}">
                                                        <button type="submit"
                                                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                            Xác nhận đã nhận
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                        onclick="openRejectPrizeModal({{ $recMatch->id }}, '{{ addslashes($recMatch->prize) }}', '{{ addslashes($counterpart?->name ?? $recMatch->loser_names) }}', '{{ $userSlot }}')"
                                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-xs px-2.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                        Không đồng ý
                                                    </button>
                                                @endif
                                                <a href="{{ route('matches.show', $recMatch->id) }}"
                                                    class="text-xs text-primary hover:underline font-medium">
                                                    Xem chi tiết →
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @include('dashboard.list-pagination', ['paginator' => $myUnpaidReceivables, 'paginationLabel' => 'kèo chờ nhận'])
                            </div>
                        @endif

                        <!-- Kèo ngoài đối thủ nợ bạn -->
                        @if(isset($myUnpaidSideBetReceivables) && $myUnpaidSideBetReceivables->isNotEmpty())
                            <div id="keo-ngoai-cho-nhan" class="scroll-mt-20 rounded-lg border border-gray-200 px-3 py-2.5">
                                <div class="mb-2.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <span class="flex items-center gap-1.5">
                                        <span>🎯</span>
                                        <span>Kèo ngoài đối thủ chưa trả cho bạn ({{ $myUnpaidSideBetReceivables->total() }} kèo)</span>
                                    </span>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @foreach($myUnpaidSideBetReceivables as $sb)
                                        <div class="flex flex-wrap items-start justify-between gap-2.5 py-3 text-sm">
                                            <div>
                                                <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                                    <span><b class="text-red-600 font-black">{{ $sb->loser?->name }}</b> nợ bạn</span>
                                                    @if($sb->prize_status === 'paid')
                                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded font-bold">Đối thủ
                                                            báo đã trả (Cần bạn xác nhận)</span>
                                                    @else
                                                        <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa
                                                            trả</span>
                                                    @endif
                                                </div>
                                                <div class="text-xs text-gray-600 mt-0.5">
                                                    Trận: <span class="text-gray-700 font-medium">{{ $sb->challenge->challenger_team_names }} vs
                                                        {{ $sb->challenge->opponent_team_names }}</span> •
                                                    Món kèo: <b
                                                        class="text-blue-900 bg-blue-100 border border-blue-200 px-2 py-0.5 rounded">{{ $sb->prize }}</b>
                                                    @if($sb->notes)
                                                        <span class="text-gray-500 italic ml-1">"{{ $sb->notes }}"</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                @if($sb->prize_status === 'paid')
                                                    <button type="button" onclick="handleConfirmSideBet({{ $sb->id }})"
                                                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                        Xác nhận đã nhận
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @include('dashboard.list-pagination', ['paginator' => $myUnpaidSideBetReceivables, 'paginationLabel' => 'kèo ngoài chờ nhận'])
                            </div>
                        @endif
                        @if($matchesAwaitingReceipt->isNotEmpty())
                            <details id="keo-da-tra" open class="group scroll-mt-20 rounded-lg border border-gray-200 bg-gray-50/70">
                                <summary class="flex min-h-11 cursor-pointer items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-gray-700 list-none hover:bg-gray-100 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary [&::-webkit-details-marker]:hidden">
                                    <span class="min-w-0 flex-1">Đã trả · chờ xác nhận</span>
                                    <span class="rounded-md bg-white px-1.5 py-0.5 text-xs text-gray-700 tabular-nums">{{ $matchesAwaitingReceipt->total() }}</span>
                                    <svg class="size-4 shrink-0 transition-transform group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </summary>
                                @include('dashboard.prize-payment-list', ['paymentMatches' => $matchesAwaitingReceipt])
                                @include('dashboard.list-pagination', ['paginator' => $matchesAwaitingReceipt, 'paginationLabel' => 'kèo đã trả'])
                            </details>
                        @endif
                    </div>
                </section>
            @else
                <p id="tong-quan-keo" class="scroll-mt-20 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600 sm:px-5">Hiện không có việc cần xử lý.</p>
            @endif

            <section id="tran-gan-day" aria-labelledby="dashboard-recent-heading" class="min-w-0 scroll-mt-20 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3.5 sm:px-5">
                    <h2 id="dashboard-recent-heading" class="text-base font-bold text-gray-900">Trận gần đây</h2>
                    <a href="{{ route('matches.index') }}"
                        class="rounded-sm text-sm font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Xem lịch sử</a>
                </div>

                <ul class="divide-y divide-gray-100">
                    @forelse($recentMatches as $match)
                        @php
                            $matchStatus = match ($match->status) {
                                'confirmed' => 'Đã xác nhận',
                                'resolved' => 'Đã xử lý',
                                'pending' => 'Chờ xác nhận',
                                'disputed' => 'Khiếu nại',
                                'cancelled' => 'Đã hủy',
                                default => $match->status,
                            };
                            $statusStyle = match ($match->status) {
                                'pending' => 'bg-amber-50 text-amber-800',
                                'disputed' => 'bg-red-50 text-red-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <li>
                            <a href="{{ route('matches.show', $match->id) }}" aria-haspopup="dialog"
                                class="grid min-w-0 grid-cols-[minmax(0,1fr)_auto] items-start gap-2 px-4 py-3 transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-primary sm:gap-3 sm:px-5">
                                <div class="min-w-0">
                                    @include('dashboard.match-result', ['match' => $match])
                                    @if($match->prize)
                                        <p class="mt-1 text-xs text-gray-600 wrap-anywhere">{{ $match->prize }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $statusStyle }}">{{ $matchStatus }}</span>
                                    <time datetime="{{ $match->played_at->toDateString() }}" class="text-xs text-gray-600 tabular-nums">{{ $match->played_at->format('d/m/Y') }}</time>
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-4 text-sm text-gray-600 sm:px-5">Chưa có trận nào.</li>
                    @endforelse
                </ul>
                @include('dashboard.list-pagination', ['paginator' => $recentMatches, 'paginationLabel' => 'trận gần đây'])
            </section>
        </div>
    </div>

    <!-- Reject Prize Feedback Modal -->
    <div id="rejectPrizeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeRejectPrizeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Phản Hồi Không Đồng Ý Trả Kèo</h3>
                    <button type="button" onclick="closeRejectPrizeModal()"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                </div>

                <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-sm text-rose-900">
                    <div><b>Kèo:</b> <span id="rejectModalPrize"></span></div>
                    <div class="mt-1"><b>Người trả:</b> <span id="rejectModalDebtor"></span></div>
                </div>

                <form id="rejectPrizeForm" method="POST" action="" class="space-y-4 text-sm">
                    @csrf
                    <input type="hidden" name="pair_slot" id="rejectModalPairSlot" value="primary">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Lý do không đồng ý (tùy chọn)</label>
                        <input type="text" name="reason" placeholder="VD: Chưa nhận được nước, chưa thấy chuyển khoản..."
                            class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="closeRejectPrizeModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">Hủy</button>
                        <button type="submit"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">
                            Xác nhận Không đồng ý
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRejectPrizeModal(matchId, prize, debtor, pairSlot = 'primary') {
            document.getElementById('rejectModalPrize').textContent = prize;
            document.getElementById('rejectModalDebtor').textContent = debtor;
            document.getElementById('rejectModalPairSlot').value = pairSlot || 'primary';
            document.getElementById('rejectPrizeForm').action = '/matches/' + matchId + '/reject-prize';
            document.getElementById('rejectPrizeModal').classList.remove('hidden');
        }
        function closeRejectPrizeModal() {
            document.getElementById('rejectPrizeModal').classList.add('hidden');
        }
    </script>
@endsection
