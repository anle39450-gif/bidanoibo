@extends('layouts.app', ['title' => 'Chi tiết trận đấu #' . $match->id . ' - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
    @php
        $isPending = ($match->status === 'pending');
        $isDisputed = ($match->status === 'disputed');
        $isConfirmed = in_array($match->status, ['confirmed', 'resolved']);
        $minutesLeft = $isPending ? intdiv($match->getRemainingSecondsForAutoConfirm(), 60) : 0;

        [$statusLabel, $statusClass] = match ($match->status) {
            'confirmed' => ['Đã xác nhận', 'bg-green-100 text-green-800'],
            'resolved' => ['Đã xử lý', 'bg-green-100 text-green-800'],
            'pending' => ['Chờ xác nhận', 'bg-amber-100 text-amber-900'],
            'disputed' => ['Đang khiếu nại', 'bg-primary-soft text-primary'],
            'cancelled' => ['Đã hủy', 'bg-gray-100 text-gray-700'],
            default => [$match->status, 'bg-gray-100 text-gray-700'],
        };
        $primaryAction = 'inline-flex min-h-10 cursor-pointer items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';
        $secondaryAction = 'inline-flex min-h-10 cursor-pointer items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-primary';
    @endphp

    <div class="space-y-4">
        <header class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('matches.index') }}"
                    class="inline-flex min-h-8 items-center gap-1.5 rounded-md text-sm font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-primary">
                    <span aria-hidden="true">←</span> Lịch sử trận đấu
                </a>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Chi tiết trận đấu <span class="text-gray-600">#{{ $match->id }}</span></h1>
            </div>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.index') }}" class="mt-1 rounded-md px-2 py-1.5 text-sm font-semibold text-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary">Quản trị</a>
            @endif
        </header>

        @if($match->trashed())
            <div
                class="bg-red-50 border-2 border-red-300 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="text-3xl">🗑️</span>
                    <div>
                        <h4 class="text-base font-bold text-red-900">Trận đấu này đang nằm trong Thùng rác</h4>
                        <p class="text-xs sm:text-sm text-red-700 mt-0.5">
                            Đã xóa tạm thời lúc {{ $match->deleted_at->format('H:i d/m/Y') }}. Trận này bị ẩn hoàn toàn khỏi
                            danh sách thi đấu và bảng xếp hạng.
                        </p>
                    </div>
                </div>
                @auth
                    @if(auth()->user()->isAdmin())
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('admin.trash.restore', $match->id) }}" onsubmit="confirmMatchAction(event)"
                                data-confirm-title="Khôi phục trận đấu?" data-confirm-text="Trận sẽ được đưa ra khỏi thùng rác." data-confirm-label="Khôi phục">
                                @csrf
                                <button type="submit"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs sm:text-sm px-4 py-2 rounded-lg shadow-sm transition cursor-pointer">
                                    ♻️ Khôi phục trận
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.trash.force-delete', $match->id) }}"
                                onsubmit="confirmMatchAction(event)" data-confirm-title="Xóa vĩnh viễn?" data-confirm-text="Thao tác này không thể hoàn tác." data-confirm-label="Xóa vĩnh viễn">
                                @csrf
                                <button type="submit"
                                    class="bg-red-600 hover:bg-red-700 text-white font-semibold text-xs sm:text-sm px-4 py-2 rounded-lg shadow-sm transition cursor-pointer">
                                    💥 Xóa vĩnh viễn
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>
        @endif

        <div class="grid items-start gap-4 xl:grid-cols-3">
            <div class="min-w-0 space-y-4 xl:col-span-2">
                <section aria-labelledby="match-result-title" class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h2 id="match-result-title" class="font-bold text-gray-900">Kết quả trận đấu</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                <time datetime="{{ $match->played_at->toIso8601String() }}" class="tabular-nums">{{ $match->played_at->format('d/m/Y · H:i') }}</time>
                                <span class="mx-1" aria-hidden="true">·</span> {{ $match->isDoubles() ? 'Đánh đôi' : 'Đánh đơn' }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($match->isHidden())
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">Đang ẩn</span>
                            @endif
                        </div>
                    </div>

                    @include('matches.partials.match-teams', ['match' => $match, 'hasResult' => $isConfirmed])

                    @if($isPending && $match->winner_id)
                        <p class="text-sm text-gray-700 wrap-anywhere">Kết quả ghi nhận: <strong>{{ $match->winner_names }} thắng</strong></p>
                    @endif
                </section>

                @if($isPending)
                    <section aria-labelledby="match-confirmation-title" class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/70 p-4 sm:p-5">
                        <h2 id="match-confirmation-title" class="font-bold text-amber-900">{{ $isWaitingForUser ? 'Đến lượt bạn xác nhận' : 'Chờ xác nhận kết quả' }}</h2>
                        @if($match->auto_confirm_at)
                            <p class="text-sm text-amber-900">Tự xác nhận sau <strong>{{ intdiv($minutesLeft, 60) }} giờ {{ $minutesLeft % 60 }} phút</strong> · {{ $match->auto_confirm_at->format('H:i d/m') }}</p>
                        @endif
                        @if($isWaitingForUser)
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('matches.confirm', $match->id) }}" onsubmit="confirmMatchAction(event)"
                                    data-confirm-title="Xác nhận kết quả?" data-confirm-text="{{ $match->winner_names }} thắng trận này." data-confirm-label="Xác nhận">
                                    @csrf
                                    <button type="submit" class="{{ $primaryAction }}">Xác nhận kết quả</button>
                                </form>
                                <button type="button" onclick="document.getElementById('disputeModal').classList.remove('hidden')" class="{{ $secondaryAction }}">Khiếu nại</button>
                            </div>
                        @endif
                    </section>
                @endif

                <!-- Prize & Settlement -->
                @if($match->isDoubles())
                    <!-- 2vs2 Doubles Cross Settlement (Tách Chéo Độc Lập) -->
                    <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-5">
                        <div
                            class="flex flex-wrap items-start justify-between gap-2 border-b border-gray-100 pb-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-sm font-semibold text-gray-700">Kèo đôi tách chéo độc lập</h2>
                                    @if($match->isPrizeConfirmed())
                                        <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                            Cả hai cặp đã hoàn tất
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-lg font-bold text-gray-900 wrap-anywhere">{{ $match->prize ?: 'Không có kèo' }}</p>
                            </div>
                        </div>

                        <!-- 2 Side-by-side Cross Pairs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Pair 1: Loser -> Winner -->
                            <div
                                class="bg-white border rounded-xl p-4 shadow-2xs flex flex-col justify-between space-y-3 {{ $match->prize_status === 'confirmed' ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-gray-200' }}">
                                <div class="space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-1.5">
                                        <span
                                            class="text-xs font-extrabold uppercase tracking-wider text-purple-900 bg-purple-100 px-2 py-0.5 rounded">
                                            Cặp 1: {{ $match->loser?->name }} ➡️ {{ $match->winner?->name }}
                                        </span>
                                        @if($match->prize_status === 'confirmed')
                                            <span class="bg-emerald-100 text-emerald-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Đã OK</span>
                                        @elseif($match->prize_status === 'paid')
                                            <span class="bg-blue-100 text-blue-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Đã trả (Chờ OK)</span>
                                        @elseif($match->prize_status === 'rejected')
                                            <span class="bg-rose-100 text-rose-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Không đồng ý</span>
                                        @else
                                            <span class="bg-amber-100 text-amber-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Chưa trả</span>
                                        @endif
                                    </div>

                                    <div class="text-sm text-gray-800">
                                        <span class="text-red-700 font-bold">{{ $match->loser?->name }}</span> trả kèo cho <span
                                            class="text-emerald-700 font-bold">{{ $match->winner?->name }}</span>
                                    </div>

                                    @if($match->prize_paid_at)
                                        <div class="text-xs text-gray-600 bg-gray-50 p-2 rounded border border-gray-100">
                                            <div>Đã trả lúc: {{ $match->prize_paid_at->format('H:i d/m/Y') }}</div>
                                            @if($match->prize_notes)
                                                <div class="italic text-gray-700 mt-0.5">"{{ $match->prize_notes }}"</div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($match->prize_status === 'confirmed')
                                        <div class="text-xs text-emerald-700 font-medium">
                                            ✓ {{ $match->winner?->name }} đã xác nhận OK lúc
                                            {{ $match->prize_confirmed_at?->format('H:i d/m/Y') }}
                                            @if($match->prize_feedback)
                                                <div class="italic">"{{ $match->prize_feedback }}"</div>
                                            @endif
                                        </div>
                                    @elseif($match->prize_status === 'rejected')
                                        <div class="text-xs text-rose-700 font-medium">
                                            ✕ {{ $match->winner?->name }} không đồng ý lúc
                                            {{ $match->prize_confirmed_at?->format('H:i d/m/Y') }}
                                            @if($match->prize_feedback)
                                                <div class="italic">"{{ $match->prize_feedback }}"</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @auth
                                    <div class="pt-2 border-t border-gray-100 flex flex-wrap items-center gap-2">
                                        @if($match->canUserConfirmPrize(auth()->user(), 'primary') && $match->prize_status === 'paid')
                                            <form method="POST" action="{{ route('matches.confirm-prize', $match->id) }}"
                                                onsubmit="confirmMatchAction(event)" data-confirm-title="Xác nhận đã nhận kèo?" data-confirm-text="Chỉ xác nhận khi bạn đã nhận đủ món kèo." data-confirm-label="Đã nhận đủ">
                                                @csrf
                                                <input type="hidden" name="pair_slot" value="primary">
                                                <button type="submit"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                    Đã OK
                                                </button>
                                            </form>
                                            <button type="button"
                                                onclick="openRejectPrizeModalExplicit('primary', @js($match->loser?->name ?? 'Người thua'))"
                                                class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                Không đồng ý
                                            </button>
                                        @endif

                                        @if($match->canUserPayPrize(auth()->user(), 'primary'))
                                            <button type="button"
                                                onclick="openPayPrizeModalExplicit('primary', @js('Trả kèo cho '.($match->winner?->name ?? 'người nhận')), @js($match->prize_notes ?? ''))"
                                                class="bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                {{ $match->prize_status === 'rejected' ? 'Trả kèo lại cho ' . $match->winner?->name : ($match->prize_status === 'paid' ? 'Cập nhật trả kèo' : 'Trả kèo cho ' . $match->winner?->name) }}
                                            </button>
                                        @endif
                                    </div>
                                @endauth
                            </div>

                            <!-- Pair 2: Loser Partner -> Winner Partner -->
                            <div
                                class="bg-white border rounded-xl p-4 shadow-2xs flex flex-col justify-between space-y-3 {{ $match->prize_partner_status === 'confirmed' ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-gray-200' }}">
                                <div class="space-y-2">
                                        <div class="flex flex-wrap items-center justify-between gap-1.5">
                                        <span
                                            class="text-xs font-extrabold uppercase tracking-wider text-purple-900 bg-purple-100 px-2 py-0.5 rounded">
                                            Cặp 2: {{ $match->loserPartner?->name }} ➡️ {{ $match->winnerPartner?->name }}
                                        </span>
                                        @if($match->prize_partner_status === 'confirmed')
                                            <span class="bg-emerald-100 text-emerald-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Đã OK</span>
                                        @elseif($match->prize_partner_status === 'paid')
                                            <span class="bg-blue-100 text-blue-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Đã trả (Chờ OK)</span>
                                        @elseif($match->prize_partner_status === 'rejected')
                                            <span class="bg-rose-100 text-rose-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Không đồng ý</span>
                                        @else
                                            <span class="bg-amber-100 text-amber-800 text-[11px] font-bold px-2 py-0.5 rounded-full">Chưa trả</span>
                                        @endif
                                    </div>

                                    <div class="text-sm text-gray-800">
                                        <span class="text-red-700 font-bold">{{ $match->loserPartner?->name }}</span> trả kèo cho
                                        <span class="text-emerald-700 font-bold">{{ $match->winnerPartner?->name }}</span>
                                    </div>

                                    @if($match->prize_partner_paid_at)
                                        <div class="text-xs text-gray-600 bg-gray-50 p-2 rounded border border-gray-100">
                                            <div>Đã trả lúc: {{ $match->prize_partner_paid_at->format('H:i d/m/Y') }}</div>
                                            @if($match->prize_partner_notes)
                                                <div class="italic text-gray-700 mt-0.5">"{{ $match->prize_partner_notes }}"</div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($match->prize_partner_status === 'confirmed')
                                        <div class="text-xs text-emerald-700 font-medium">
                                            ✓ {{ $match->winnerPartner?->name }} đã xác nhận OK lúc
                                            {{ $match->prize_partner_confirmed_at?->format('H:i d/m/Y') }}
                                            @if($match->prize_partner_feedback)
                                                <div class="italic">"{{ $match->prize_partner_feedback }}"</div>
                                            @endif
                                        </div>
                                    @elseif($match->prize_partner_status === 'rejected')
                                        <div class="text-xs text-rose-700 font-medium">
                                            ✕ {{ $match->winnerPartner?->name }} không đồng ý lúc
                                            {{ $match->prize_partner_confirmed_at?->format('H:i d/m/Y') }}
                                            @if($match->prize_partner_feedback)
                                                <div class="italic">"{{ $match->prize_partner_feedback }}"</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @auth
                                    <div class="pt-2 border-t border-gray-100 flex flex-wrap items-center gap-2">
                                        @if($match->canUserConfirmPrize(auth()->user(), 'partner') && $match->prize_partner_status === 'paid')
                                            <form method="POST" action="{{ route('matches.confirm-prize', $match->id) }}"
                                                onsubmit="confirmMatchAction(event)" data-confirm-title="Xác nhận đã nhận kèo?" data-confirm-text="Chỉ xác nhận khi bạn đã nhận đủ món kèo." data-confirm-label="Đã nhận đủ">
                                                @csrf
                                                <input type="hidden" name="pair_slot" value="partner">
                                                <button type="submit"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                    Đã OK
                                                </button>
                                            </form>
                                            <button type="button"
                                                onclick="openRejectPrizeModalExplicit('partner', @js($match->loserPartner?->name ?? 'Người thua'))"
                                                class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                Không đồng ý
                                            </button>
                                        @endif

                                        @if($match->canUserPayPrize(auth()->user(), 'partner'))
                                            <button type="button"
                                                onclick="openPayPrizeModalExplicit('partner', @js('Trả kèo cho '.($match->winnerPartner?->name ?? 'người nhận')), @js($match->prize_partner_notes ?? ''))"
                                                class="bg-primary hover:bg-primary-hover text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                {{ $match->prize_partner_status === 'rejected' ? 'Trả kèo lại cho ' . $match->winnerPartner?->name : ($match->prize_partner_status === 'paid' ? 'Cập nhật trả kèo' : 'Trả kèo cho ' . $match->winnerPartner?->name) }}
                                            </button>
                                        @endif
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>
                @else
                    @php
                        [$paymentLabel, $paymentClass] = match ($match->prize_status) {
                            'confirmed' => ['Đã hoàn tất', 'bg-green-100 text-green-800'],
                            'paid' => ['Đã trả · chờ xác nhận', 'bg-amber-100 text-amber-900'],
                            'rejected' => ['Cần trả lại', 'bg-primary-soft text-primary'],
                            default => ['Chưa trả kèo', 'bg-gray-100 text-gray-700'],
                        };
                        $canReceive = $match->canUserConfirmPrize(auth()->user(), 'primary') && $match->prize_status === 'paid';
                        $canPay = $match->canUserPayPrize(auth()->user(), 'primary');
                        $payLabel = match ($match->prize_status) {
                            'rejected' => 'Trả kèo lại',
                            'paid' => 'Cập nhật trả kèo',
                            default => 'Trả kèo',
                        };
                    @endphp
                    <section aria-labelledby="match-prize-title" class="space-y-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 id="match-prize-title" class="text-sm font-semibold text-gray-600">Món kèo</h2>
                                <p class="mt-1 text-lg font-bold text-gray-900 wrap-anywhere">{{ $match->prize ?: 'Không có kèo' }}</p>
                            </div>
                            @if($isConfirmed && $match->prize)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $paymentClass }}">{{ $paymentLabel }}</span>
                            @endif
                        </div>

                        @if($match->prize_paid_at)
                            <div class="space-y-1 rounded-lg bg-gray-50 px-3 py-2.5 text-sm text-gray-700">
                                <p><strong>{{ $match->prizePaidBy?->name ?? 'Người thua' }}</strong> đã trả lúc <span class="tabular-nums">{{ $match->prize_paid_at->format('H:i d/m/Y') }}</span></p>
                                @if($match->prize_notes)
                                    <p class="text-gray-600 wrap-anywhere">{{ $match->prize_notes }}</p>
                                @endif
                            </div>
                        @endif
                        @if(in_array($match->prize_status, ['confirmed', 'rejected'], true))
                            <div class="text-sm {{ $match->prize_status === 'confirmed' ? 'text-green-800' : 'text-primary' }}">
                                <p><strong>{{ $match->prizeConfirmedBy?->name ?? 'Người nhận' }}</strong> {{ $match->prize_status === 'confirmed' ? 'đã xác nhận nhận kèo' : 'chưa đồng ý' }}
                                    @if($match->prize_confirmed_at) · {{ $match->prize_confirmed_at->format('H:i d/m/Y') }} @endif
                                </p>
                                @if($match->prize_feedback)
                                    <p class="mt-1 text-gray-700 wrap-anywhere">{{ $match->prize_feedback }}</p>
                                @endif
                            </div>
                        @endif

                        @if($canReceive || $canPay)
                            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-3">
                                @if($canReceive)
                                    <form method="POST" action="{{ route('matches.confirm-prize', $match->id) }}" onsubmit="confirmMatchAction(event)"
                                        data-confirm-title="Xác nhận đã nhận kèo?" data-confirm-text="Chỉ xác nhận khi bạn đã nhận đủ món kèo." data-confirm-label="Đã nhận đủ">
                                        @csrf
                                        <input type="hidden" name="pair_slot" value="primary">
                                        <button type="submit" class="{{ $primaryAction }}">Xác nhận đã nhận</button>
                                    </form>
                                    <button type="button" onclick="openRejectPrizeModalExplicit('primary', @js($match->loser?->name ?? 'Người thua'))" class="{{ $secondaryAction }}">Phản hồi</button>
                                @endif
                                @if($canPay)
                                    <button type="button" onclick="openPayPrizeModalExplicit('primary', @js($payLabel), @js($match->prize_notes ?? ''))" class="{{ $primaryAction }}">{{ $payLabel }}</button>
                                @endif
                            </div>
                        @endif
                    </section>
                @endif

                <!-- Notes & Evidence -->
                @if($match->notes || $match->evidence_path)
                    <div class="space-y-3 pt-2">
                        @if($match->notes)
                            <div>
                                <div class="text-xs sm:text-sm font-semibold text-gray-700 uppercase mb-1.5">Ghi chú</div>
                                <p class="text-sm sm:text-base text-gray-800 bg-gray-50 p-3.5 rounded-lg border border-gray-200 italic">
                                    "{{ $match->notes }}"</p>
                            </div>
                        @endif

                        @if($match->evidence_path)
                            <div>
                                <div class="text-xs sm:text-sm font-semibold text-gray-700 uppercase mb-2">Ảnh bằng chứng</div>
                                <img src="{{ asset('storage/' . $match->evidence_path) }}" alt="Bằng chứng"
                                    class="rounded-lg border border-gray-300 max-h-96 shadow-sm">
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Dispute Details if disputed -->
                @if($isDisputed)
                    <div class="bg-red-50 border border-red-200 rounded-xl p-5 space-y-2.5 shadow-sm">
                        <div class="text-sm font-bold text-red-800 uppercase tracking-wide">Trận đấu đang có khiếu nại</div>
                        <div class="text-sm text-red-800">Lý do khiếu nại: <b>"{{ $match->dispute_reason }}"</b></div>
                        @if($match->dispute_evidence_path)
                            <img src="{{ asset('storage/' . $match->dispute_evidence_path) }}" alt="Evidence"
                                class="rounded-lg max-h-56 border border-red-200 mt-2 shadow-sm">
                        @endif
                    </div>
                @endif

                <!-- Admin Resolution Form -->
                @auth
                    @if(auth()->user()->isAdmin() && $isDisputed)
                        <div class="bg-amber-50 border border-amber-300 rounded-xl p-5 space-y-3.5 shadow-sm">
                            <div class="text-sm font-bold text-amber-900 uppercase tracking-wide">Xử lý tranh chấp (Dành cho Quản trị
                                viên)</div>
                            <form method="POST" action="{{ route('admin.matches.resolve', $match->id) }}" class="space-y-3.5" onsubmit="confirmMatchAction(event)"
                                data-confirm-title="Lưu quyết định xử lý?" data-confirm-text="Quyết định sẽ cập nhật kết quả chính thức của trận đấu." data-confirm-label="Lưu quyết định">
                                @csrf
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-sm">
                                    <label
                                        class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                        <input type="radio" name="resolution" value="confirm_initial" checked>
                                        <span class="font-medium">Giữ nguyên kết quả</span>
                                    </label>
                                    <label
                                        class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                        <input type="radio" name="resolution" value="reverse_winner">
                                        <span class="font-medium">Đảo ngược người thắng</span>
                                    </label>
                                    <label
                                        class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                        <input type="radio" name="resolution" value="cancel">
                                        <span class="font-medium">Hủy trận đấu</span>
                                    </label>
                                </div>

                                <textarea name="admin_notes" required rows="2" placeholder="Nhập lý do xử lý của quản trị viên..."
                                    class="w-full text-sm border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>

                                <button type="submit"
                                    class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm px-4 py-2 rounded-lg cursor-pointer transition shadow-sm">
                                    Lưu quyết định xử lý
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth

            </div>

            <aside class="min-w-0 space-y-4">
                <section aria-labelledby="match-history-title" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
                    <div class="border-b border-gray-100 px-4 py-3 sm:px-5">
                        <h2 id="match-history-title" class="font-bold text-gray-900">Lịch sử thao tác</h2>
                        @if($match->creator)
                            <p class="mt-1 text-xs text-gray-600">Tạo bởi <strong>{{ $match->creator->name }}</strong> · {{ $match->created_from === 'telegram' ? 'Telegram' : 'Website' }}</p>
                        @endif
                    </div>
                    <ol class="max-h-96 divide-y divide-gray-100 overflow-y-auto">
                        @forelse($match->histories as $entry)
                            <li class="space-y-1 px-4 py-2.5 text-sm sm:px-5">
                                <p class="text-gray-700 wrap-anywhere">{{ $entry->note ?: ($entry->user?->name ?? 'Hệ thống') }}</p>
                                <time datetime="{{ $entry->created_at->toIso8601String() }}" class="block text-xs text-gray-600 tabular-nums">{{ $entry->created_at->format('d/m/Y · H:i') }}</time>
                            </li>
                        @empty
                            <li class="px-4 py-4 text-sm text-gray-600 sm:px-5">Chưa có thao tác nào.</li>
                        @endforelse
                    </ol>
                </section>
                @if(auth()->user()->isAdmin() && !$match->trashed())
                    <details class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-xs sm:px-5">
                        <summary class="cursor-pointer rounded text-sm font-semibold text-gray-900 focus-visible:outline-2 focus-visible:outline-primary">Quản lý trận đấu</summary>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if($match->status !== 'cancelled')
                                <button type="button" onclick="document.getElementById('adminCancelModal').classList.remove('hidden')" class="{{ $secondaryAction }}">Hủy trận</button>
                            @endif
                            <form method="POST" action="{{ route('admin.matches.toggle-hidden', $match->id) }}" onsubmit="confirmMatchAction(event)"
                                data-confirm-title="{{ $match->isHidden() ? 'Hiện lại trận đấu?' : 'Ẩn trận đấu?' }}"
                                data-confirm-text="{{ $match->isHidden() ? 'Trận đấu sẽ xuất hiện lại trong danh sách.' : 'Trận đấu sẽ được ẩn khỏi danh sách và bảng xếp hạng.' }}"
                                data-confirm-label="{{ $match->isHidden() ? 'Hiện lại' : 'Ẩn trận' }}">
                                @csrf
                                <button type="submit" class="{{ $secondaryAction }}">{{ $match->isHidden() ? 'Hiện lại' : 'Ẩn trận' }}</button>
                            </form>
                            <form method="POST" action="{{ route('admin.matches.trash', $match->id) }}" onsubmit="confirmMatchAction(event)"
                                data-confirm-title="Chuyển vào thùng rác?" data-confirm-text="Bạn có thể khôi phục trận từ thùng rác." data-confirm-label="Chuyển vào thùng rác">
                                @csrf
                                <button type="submit" class="{{ $secondaryAction }}">Đưa vào thùng rác</button>
                            </form>
                        </div>
                    </details>
                @endif
            </aside>
        </div>
    </div>

    <!-- Simple Dispute Modal -->
    <div id="disputeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60" onclick="document.getElementById('disputeModal').classList.add('hidden')">
        </div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Khiếu nại kết quả trận đấu</h3>
                    <button type="button" onclick="document.getElementById('disputeModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                </div>

                <form method="POST" action="{{ route('matches.dispute', $match->id) }}" enctype="multipart/form-data" onsubmit="confirmMatchAction(event)"
                    data-confirm-title="Gửi khiếu nại?" data-confirm-text="Trận sẽ chuyển sang chờ xử lý khiếu nại." data-confirm-label="Gửi khiếu nại"
                    class="space-y-4 text-sm">
                    @csrf
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Lý do khiếu nại <span
                                class="text-red-500">*</span></label>
                        <textarea name="reason" required rows="3" placeholder="Nêu rõ lý do..."
                            class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Ảnh bằng chứng (tùy chọn)</label>
                        <input type="file" name="evidence" accept="image/*"
                            class="w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('disputeModal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">Hủy</button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">Gửi
                            khiếu nại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pay Prize Settlement Modal -->
    <div id="payPrizeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60"
            onclick="document.getElementById('payPrizeModal').classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900" id="payModalTitle">
                        @auth
                            {{ $match->prize_status === 'rejected' ? 'Trả kèo lại' : ($match->prize_status === 'paid' ? 'Cập nhật trả kèo' : ($match->isUserDebtor(auth()->user()) ? 'Trả kèo trận đấu' : 'Xác nhận hoàn thành trả kèo')) }}
                        @else
                            Xác nhận trả kèo
                        @endauth
                    </h3>
                    <button type="button" onclick="document.getElementById('payPrizeModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm">
                    <div class="text-xs text-gray-500 font-semibold uppercase">Phần thưởng / Kèo:</div>
                    <div class="font-bold text-gray-900 mt-0.5 text-base">{{ $match->prize }}</div>
                    @if($match->prize_status === 'rejected' && $match->prize_feedback)
                        <div class="text-xs text-red-600 mt-2 font-medium">
                            Lý do người nhận không đồng ý trước đó: "{{ $match->prize_feedback }}"
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ route('matches.pay-prize', $match->id) }}" class="space-y-4 text-sm" onsubmit="confirmMatchAction(event)"
                    data-confirm-title="Cập nhật trả kèo?" data-confirm-text="Người nhận sẽ được thông báo để xác nhận." data-confirm-label="Lưu cập nhật">
                    @csrf
                    <input type="hidden" name="pair_slot" id="payModalPairSlot" value="primary">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Ghi chú xác nhận (tùy chọn)</label>
                        <input type="text" name="note" id="payModalNote" value="{{ $match->prize_notes }}"
                            placeholder="VD: Đã bao chầu nước, đã chuyển khoản..."
                            class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('payPrizeModal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">Hủy</button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">
                            {{ $match->prize_status === 'rejected' ? 'Xác nhận trả kèo lại' : 'Xác nhận trả kèo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Prize Feedback Modal -->
    <div id="rejectPrizeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60"
            onclick="document.getElementById('rejectPrizeModal').classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Phản Hồi Không Đồng Ý Trả Kèo</h3>
                    <button type="button" onclick="document.getElementById('rejectPrizeModal').classList.add('hidden')"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                </div>

                <div class="bg-rose-50 border border-rose-200 rounded-lg p-3 text-sm text-rose-900">
                    <div><b>Kèo:</b> {{ $match->prize }}</div>
                    <div class="mt-1"><b>Người trả:</b> <span
                            id="rejectModalDebtorName">{{ $match->prizePaidBy?->name ?? 'Người thua' }}</span></div>
                    @if($match->prize_notes)
                        <div class="mt-1 text-xs"><b>Ghi chú đối thủ:</b> "{{ $match->prize_notes }}"</div>
                    @endif
                </div>

                <form method="POST" action="{{ route('matches.reject-prize', $match->id) }}" class="space-y-4 text-sm" onsubmit="confirmMatchAction(event)"
                    data-confirm-title="Gửi phản hồi?" data-confirm-text="Người trả kèo sẽ thấy phản hồi của bạn." data-confirm-label="Gửi phản hồi">
                    @csrf
                    <input type="hidden" name="pair_slot" id="rejectModalPairSlot" value="primary">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Lý do không đồng ý (tùy chọn)</label>
                        <input type="text" name="reason" placeholder="VD: Chưa nhận được nước, chưa thấy chuyển tiền..."
                            class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('rejectPrizeModal').classList.add('hidden')"
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

    <!-- Admin Cancel Match Modal -->
    @auth
        @if(auth()->user()->isAdmin())
            <div id="adminCancelModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
                <div class="fixed inset-0 bg-gray-900/60"
                    onclick="document.getElementById('adminCancelModal').classList.add('hidden')"></div>
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                                <span>🚫 Hủy trận đấu #{{ $match->id }}</span>
                            </h3>
                            <button type="button" onclick="document.getElementById('adminCancelModal').classList.add('hidden')"
                                class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                        </div>

                        <p class="text-sm text-gray-600">
                            Khi hủy trận đấu, kết quả trận này sẽ không được tính vào bảng xếp hạng, chuỗi thắng/thua cũng như nợ
                            kèo của các cơ thủ.
                        </p>

                        <form method="POST" action="{{ route('admin.matches.cancel', $match->id) }}" class="space-y-4 text-sm" onsubmit="confirmMatchAction(event)"
                            data-confirm-title="Hủy trận đấu?" data-confirm-text="Kết quả của trận sẽ bị hủy." data-confirm-label="Hủy trận">
                            @csrf
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1.5">Lý do hủy trận (tùy chọn)</label>
                                <textarea name="reason" rows="3" placeholder="Nhập lý do admin hủy trận..."
                                    class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500"></textarea>
                            </div>

                            <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                                <button type="button" onclick="document.getElementById('adminCancelModal').classList.add('hidden')"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">
                                    Đóng
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">
                                    Xác nhận Hủy trận
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endauth

    <script>
        async function confirmMatchAction(event) {
            event.preventDefault();
            const form = event.currentTarget;
            if (form.dataset.confirming) return;
            form.dataset.confirming = 'true';

            const { confirmTitle, confirmText, confirmLabel } = form.dataset;
            const confirmed = typeof window.confirmSiteAction === 'function'
                ? await window.confirmSiteAction({ title: confirmTitle, text: confirmText, buttons: ['Quay lại', confirmLabel] })
                : window.confirm(confirmText);

            if (confirmed) {
                form.submit();
            } else {
                delete form.dataset.confirming;
            }
        }

        function openPayPrizeModalExplicit(slot = 'primary', title = 'Xác nhận trả kèo', defaultNote = '') {
            const slotInput = document.getElementById('payModalPairSlot');
            if (slotInput) slotInput.value = slot;

            const titleEl = document.getElementById('payModalTitle');
            if (titleEl && title) titleEl.textContent = title;

            const noteInput = document.getElementById('payModalNote');
            if (noteInput && defaultNote !== undefined) noteInput.value = defaultNote;

            document.getElementById('payPrizeModal').classList.remove('hidden');
        }

        function openRejectPrizeModalExplicit(slot = 'primary', debtorName = '') {
            const slotInput = document.getElementById('rejectModalPairSlot');
            if (slotInput) slotInput.value = slot;

            const debtorEl = document.getElementById('rejectModalDebtorName');
            if (debtorEl && debtorName) debtorEl.textContent = debtorName;

            document.getElementById('rejectPrizeModal').classList.remove('hidden');
        }
    </script>
@endsection
