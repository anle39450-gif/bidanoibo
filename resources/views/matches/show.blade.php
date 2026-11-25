@extends('layouts.app', ['title' => 'Chi tiết trận đấu #' . $match->id . ' - Bida Nội Bộ'])

@section('content')
    @php
        $isPending = ($match->status === 'pending');
        $isDisputed = ($match->status === 'disputed');
        $isConfirmed = in_array($match->status, ['confirmed', 'resolved']);

        $statusLabel = match ($match->status) {
            'confirmed' => 'Đã xác nhận',
            'resolved' => 'Tranh chấp đã xử lý',
            'pending' => 'Chờ xác nhận',
            'disputed' => 'Đang khiếu nại',
            'cancelled' => 'Đã hủy',
            default => $match->status,
        };
    @endphp

    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Back button -->
        <div class="flex items-center justify-between">
            <a href="{{ route('matches.index') }}"
                class="text-sm text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1">
                ← Quay lại danh sách
            </a>
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.index') }}"
                        class="text-xs sm:text-sm text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                        ⚙️ Bảng Quản Trị Viên
                    </a>
                @endif
            @endauth
        </div>

        @if($match->trashed())
            <div class="bg-red-50 border-2 border-red-300 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="text-3xl">🗑️</span>
                    <div>
                        <h4 class="text-base font-bold text-red-900">Trận đấu này đang nằm trong Thùng rác</h4>
                        <p class="text-xs sm:text-sm text-red-700 mt-0.5">
                            Đã xóa tạm thời lúc {{ $match->deleted_at->format('H:i d/m/Y') }}. Trận này bị ẩn hoàn toàn khỏi danh sách thi đấu và bảng xếp hạng.
                        </p>
                    </div>
                </div>
                @auth
                    @if(auth()->user()->isAdmin())
                        <div class="flex items-center gap-2 shrink-0">
                            <form method="POST" action="{{ route('admin.trash.restore', $match->id) }}">
                                @csrf
                                <button type="submit"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs sm:text-sm px-4 py-2 rounded-lg shadow-sm transition cursor-pointer">
                                    ♻️ Khôi phục trận
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.trash.force-delete', $match->id) }}"
                                onsubmit="return confirm('CẢNH BÁO: Bạn chắc chắn muốn xóa vĩnh viễn trận đấu này? Thao tác này KHÔNG THỂ hoàn tác!')">
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

        <!-- Match Detail Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 sm:p-7 space-y-6">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-200 gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span
                        class="px-3 py-1 rounded-md text-sm font-bold
                            {{ $match->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($match->status === 'pending' ? 'bg-amber-100 text-amber-800' : ($match->status === 'cancelled' ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-800')) }}">
                        {{ $statusLabel }}
                    </span>
                    @if($match->is_hidden)
                        <span class="bg-amber-100 text-amber-800 text-xs px-2.5 py-1 rounded-md font-bold flex items-center gap-1">
                            🙈 Đang ẩn
                        </span>
                    @endif
                    <span class="text-sm font-medium text-gray-500">Mã trận: #{{ $match->id }}</span>
                    <span class="text-sm font-medium text-gray-500">• Diễn ra:
                        {{ $match->played_at->format('d/m/Y H:i') }}</span>
                </div>

                <span class="text-sm text-gray-500">
                    Người tạo: <b class="text-gray-800">{{ $match->creator->name }}</b>
                    ({{ $match->created_from === 'telegram' ? 'Qua Telegram' : 'Qua Website' }})
                </span>
            </div>

            <!-- Admin Omnipotent Action Bar -->
            @auth
                @if(auth()->user()->isAdmin())
                    <div class="bg-indigo-50/80 border border-indigo-200 rounded-xl p-4 sm:p-5 space-y-3">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <span class="text-xs sm:text-sm font-bold text-indigo-950 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Toàn Quyền Quản Trị Viên (Admin Omnipotent Controls)
                            </span>
                            <span class="text-xs text-indigo-700 font-medium">
                                Hiển thị: <b>{{ $match->is_hidden ? 'Đang ẩn' : 'Đang hiện' }}</b> • Thùng rác: <b>{{ $match->trashed() ? 'Đang trong thùng rác' : 'Bình thường' }}</b>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            @if(!$match->trashed())
                                {{-- 1. Cancel Match --}}
                                @if($match->status !== 'cancelled')
                                    <button type="button" onclick="document.getElementById('adminCancelModal').classList.remove('hidden')"
                                        class="bg-rose-600 hover:bg-rose-700 text-white text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                        <span>🚫 Hủy trận</span>
                                    </button>
                                @endif

                                {{-- 2. Toggle Hidden --}}
                                <form method="POST" action="{{ route('admin.matches.toggle-hidden', $match->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                        <span>{{ $match->is_hidden ? '👁️ Mở hiển thị' : '🙈 Ẩn trận đấu' }}</span>
                                    </button>
                                </form>

                                {{-- 3. Move to Trash --}}
                                <form method="POST" action="{{ route('admin.matches.trash', $match->id) }}"
                                    onsubmit="return confirm('Bạn có chắc muốn chuyển trận đấu #{{ $match->id }} vào thùng rác?')">
                                    @csrf
                                    <button type="submit"
                                        class="bg-white hover:bg-red-50 text-red-700 border border-red-200 text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                        <span>🗑️ Cho vào thùng rác</span>
                                    </button>
                                </form>
                            @else
                                {{-- Trashed Controls --}}
                                <form method="POST" action="{{ route('admin.trash.restore', $match->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                        <span>♻️ Khôi phục trận</span>
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.trash.force-delete', $match->id) }}"
                                    onsubmit="return confirm('CẢNH BÁO: Xóa vĩnh viễn trận đấu này? Thao tác này không thể hoàn tác!')">
                                    @csrf
                                    <button type="submit"
                                        class="bg-red-600 hover:bg-red-700 text-white text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                        <span>💥 Xóa vĩnh viễn</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            @endauth

            <!-- 24h Countdown alert if pending -->
            @if($isPending)
                @php
                    $remainingSecs = $match->getRemainingSecondsForAutoConfirm();
                    $hours = floor($remainingSecs / 3600);
                    $mins = floor(($remainingSecs % 3600) / 60);
                @endphp
                <div
                    class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg text-sm text-amber-900 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
                    <div>
                        <b>Quy tắc tự động xác nhận sau 24 giờ:</b><br>
                        Nếu đối thủ không phản hồi trước <b>{{ $match->auto_confirm_at?->format('H:i d/m/Y') }}</b>, hệ thống sẽ
                        tự động chốt kết quả.
                    </div>
                    <div class="font-bold bg-white px-3.5 py-2 rounded-lg border border-amber-300 shrink-0 text-sm">
                        Còn {{ $hours }}h {{ $mins }}m
                    </div>
                </div>
            @endif

            <!-- Players: Winner & Loser -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Winner -->
                <div class="border border-green-200 bg-green-50/50 rounded-xl p-5">
                    <div class="text-xs sm:text-sm font-bold text-green-800 uppercase tracking-wider mb-3">
                        {{ $match->isDoubles() ? 'Đội thắng' : 'Người thắng' }}
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3.5">
                            <img src="{{ $match->winner->avatar_url }}" alt="{{ $match->winner->name }}"
                                class="w-12 h-12 rounded-full border-2 border-green-300 object-cover shadow-sm">
                            <div>
                                <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">
                                    {{ $match->winner->name }}</div>
                                <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                    @<span>{{ $match->winner->username }}</span></div>
                            </div>
                        </div>
                        @if($match->winnerPartner)
                            <div class="flex items-center gap-3.5 pt-2 border-t border-green-200/60">
                                <img src="{{ $match->winnerPartner->avatar_url }}" alt="{{ $match->winnerPartner->name }}"
                                    class="w-12 h-12 rounded-full border-2 border-green-300 object-cover shadow-sm">
                                <div>
                                    <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">
                                        {{ $match->winnerPartner->name }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                        @<span>{{ $match->winnerPartner->username }}</span></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Loser -->
                <div class="border border-red-200 bg-red-50/50 rounded-xl p-5">
                    <div class="text-xs sm:text-sm font-bold text-red-800 uppercase tracking-wider mb-3">
                        {{ $match->isDoubles() ? 'Đội thua (Bao kèo)' : 'Người thua (Bao kèo)' }}
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3.5">
                            <img src="{{ $match->loser->avatar_url }}" alt="{{ $match->loser->name }}"
                                class="w-12 h-12 rounded-full border-2 border-red-300 object-cover shadow-sm">
                            <div>
                                <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">
                                    {{ $match->loser->name }}</div>
                                <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                    @<span>{{ $match->loser->username }}</span></div>
                            </div>
                        </div>
                        @if($match->loserPartner)
                            <div class="flex items-center gap-3.5 pt-2 border-t border-red-200/60">
                                <img src="{{ $match->loserPartner->avatar_url }}" alt="{{ $match->loserPartner->name }}"
                                    class="w-12 h-12 rounded-full border-2 border-red-300 object-cover shadow-sm">
                                <div>
                                    <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">
                                        {{ $match->loserPartner->name }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">
                                        @<span>{{ $match->loserPartner->username }}</span></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Prize & Settlement -->
            @if($match->isDoubles())
                <!-- 2vs2 Doubles Cross Settlement (Tách Chéo Độc Lập) -->
                <div class="border border-purple-200 bg-purple-50/40 rounded-xl p-5 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-purple-200/60 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="bg-purple-100 text-purple-800 text-xs font-black px-2.5 py-0.5 rounded-full border border-purple-300 uppercase tracking-wider inline-flex items-center gap-1">
                                    <span>👥</span> Kèo đôi tách chéo độc lập
                                </span>
                                @if($match->isPrizeConfirmed())
                                    <span class="bg-emerald-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full">
                                        Cả 2 cặp đã OK hoàn tất
                                    </span>
                                @endif
                            </div>
                            <div class="text-xl sm:text-2xl font-black text-gray-900 mt-1">
                                Kèo trận: {{ $match->prize }}
                            </div>
                            <div class="text-xs text-gray-600 mt-0.5">
                                Mỗi người bên đội thua trả riêng 1 phần kèo cho 1 người tương ứng bên đội thắng.
                            </div>
                        </div>
                    </div>

                    <!-- 2 Side-by-side Cross Pairs -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Pair 1: Loser -> Winner -->
                        <div class="bg-white border rounded-xl p-4 shadow-2xs flex flex-col justify-between space-y-3 {{ $match->prize_status === 'confirmed' ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-gray-200' }}">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold uppercase tracking-wider text-purple-900 bg-purple-100 px-2 py-0.5 rounded">
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
                                    <span class="text-red-700 font-bold">{{ $match->loser?->name }}</span> trả kèo cho <span class="text-emerald-700 font-bold">{{ $match->winner?->name }}</span>
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
                                        ✓ {{ $match->winner?->name }} đã xác nhận OK lúc {{ $match->prize_confirmed_at?->format('H:i d/m/Y') }}
                                        @if($match->prize_feedback)
                                            <div class="italic">"{{ $match->prize_feedback }}"</div>
                                        @endif
                                    </div>
                                @elseif($match->prize_status === 'rejected')
                                    <div class="text-xs text-rose-700 font-medium">
                                        ✕ {{ $match->winner?->name }} không đồng ý lúc {{ $match->prize_confirmed_at?->format('H:i d/m/Y') }}
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
                                            onsubmit="return confirm('Xác nhận bạn ĐÃ OK / đã nhận đủ kèo từ {{ $match->loser?->name }}?')">
                                            @csrf
                                            <input type="hidden" name="pair_slot" value="primary">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                Đã OK
                                            </button>
                                        </form>
                                        <button type="button"
                                            onclick="openRejectPrizeModalExplicit('primary', '{{ addslashes($match->loser?->name ?? 'Người thua') }}')"
                                            class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                            Không đồng ý
                                        </button>
                                    @endif

                                    @if($match->canUserPayPrize(auth()->user(), 'primary'))
                                        <button type="button" onclick="openPayPrizeModalExplicit('primary', 'Trả kèo Cặp 1 (cho {{ $match->winner?->name }})', '{{ addslashes($match->prize_notes ?? '') }}')"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                            {{ $match->prize_status === 'rejected' ? 'Trả kèo lại cho ' . $match->winner?->name : ($match->prize_status === 'paid' ? 'Cập nhật trả kèo' : 'Trả kèo cho ' . $match->winner?->name) }}
                                        </button>
                                    @endif
                                </div>
                            @endauth
                        </div>

                        <!-- Pair 2: Loser Partner -> Winner Partner -->
                        <div class="bg-white border rounded-xl p-4 shadow-2xs flex flex-col justify-between space-y-3 {{ $match->prize_partner_status === 'confirmed' ? 'border-emerald-300 ring-1 ring-emerald-200' : 'border-gray-200' }}">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold uppercase tracking-wider text-purple-900 bg-purple-100 px-2 py-0.5 rounded">
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
                                    <span class="text-red-700 font-bold">{{ $match->loserPartner?->name }}</span> trả kèo cho <span class="text-emerald-700 font-bold">{{ $match->winnerPartner?->name }}</span>
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
                                        ✓ {{ $match->winnerPartner?->name }} đã xác nhận OK lúc {{ $match->prize_partner_confirmed_at?->format('H:i d/m/Y') }}
                                        @if($match->prize_partner_feedback)
                                            <div class="italic">"{{ $match->prize_partner_feedback }}"</div>
                                        @endif
                                    </div>
                                @elseif($match->prize_partner_status === 'rejected')
                                    <div class="text-xs text-rose-700 font-medium">
                                        ✕ {{ $match->winnerPartner?->name }} không đồng ý lúc {{ $match->prize_partner_confirmed_at?->format('H:i d/m/Y') }}
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
                                            onsubmit="return confirm('Xác nhận bạn ĐÃ OK / đã nhận đủ kèo từ {{ $match->loserPartner?->name }}?')">
                                            @csrf
                                            <input type="hidden" name="pair_slot" value="partner">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                Đã OK
                                            </button>
                                        </form>
                                        <button type="button"
                                            onclick="openRejectPrizeModalExplicit('partner', '{{ addslashes($match->loserPartner?->name ?? 'Người thua') }}')"
                                            class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold px-2.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                            Không đồng ý
                                        </button>
                                    @endif

                                    @if($match->canUserPayPrize(auth()->user(), 'partner'))
                                        <button type="button" onclick="openPayPrizeModalExplicit('partner', 'Trả kèo Cặp 2 (cho {{ $match->winnerPartner?->name }})', '{{ addslashes($match->prize_partner_notes ?? '') }}')"
                                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                            {{ $match->prize_partner_status === 'rejected' ? 'Trả kèo lại cho ' . $match->winnerPartner?->name : ($match->prize_partner_status === 'paid' ? 'Cập nhật trả kèo' : 'Trả kèo cho ' . $match->winnerPartner?->name) }}
                                        </button>
                                    @endif
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>
            @else
                <!-- Singles Prize Settlement -->
                <div
                    class="border rounded-xl p-5 shadow-sm {{ $match->isPrizePaid() ? 'bg-green-50/50 border-green-200' : 'bg-gray-50 border-gray-200' }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Phần
                                    thưởng / Kèo giao lưu</span>
                                @if($match->prize_status === 'confirmed')
                                    <span
                                        class="bg-emerald-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                        Đã trả kèo (Người nhận đã OK)
                                    </span>
                                @elseif($match->prize_status === 'paid')
                                    <span
                                        class="bg-blue-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                        Đã trả kèo (Chờ xác nhận)
                                    </span>
                                @elseif($match->prize_status === 'rejected')
                                    <span
                                        class="bg-rose-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                        Người nhận không đồng ý
                                    </span>
                                @else
                                    <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
                                        Chưa trả kèo
                                    </span>
                                @endif
                            </div>
                            <div class="text-xl sm:text-2xl font-black text-gray-900 mt-1.5">{{ $match->prize }}</div>

                            @if($match->prize_paid_at)
                                <div class="text-xs sm:text-sm text-gray-700 mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span>Thực hiện trả bởi: <b>{{ $match->prizePaidBy?->name ?? 'Người thua' }}</b></span>
                                    <span>•</span>
                                    <span>Lúc: {{ $match->prize_paid_at->format('H:i d/m/Y') }}</span>
                                    @if($match->prize_notes)
                                        <span>•</span>
                                        <span class="italic font-medium">"{{ $match->prize_notes }}"</span>
                                    @endif
                                </div>
                            @endif

                            @if($match->prize_status === 'confirmed')
                                <div class="text-xs sm:text-sm text-emerald-800 mt-1 font-medium">
                                    Xác nhận ĐÃ OK bởi: <b>{{ $match->prizeConfirmedBy?->name ?? 'Người nhận' }}</b> lúc
                                    {{ $match->prize_confirmed_at?->format('H:i d/m/Y') }}
                                    @if($match->prize_feedback)
                                        <span class="italic"> - "{{ $match->prize_feedback }}"</span>
                                    @endif
                                </div>
                            @elseif($match->prize_status === 'rejected')
                                <div class="text-xs sm:text-sm text-rose-800 mt-1 font-medium">
                                    Phản hồi không đồng ý bởi: <b>{{ $match->prizeConfirmedBy?->name ?? 'Người nhận' }}</b> lúc
                                    {{ $match->prize_confirmed_at?->format('H:i d/m/Y') }}
                                    @if($match->prize_feedback)
                                        <span class="italic"> - Lý do: "{{ $match->prize_feedback }}"</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @auth
                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                @if($match->canUserConfirmPrize(auth()->user(), 'primary') && $match->prize_status === 'paid')
                                    <form method="POST" action="{{ route('matches.confirm-prize', $match->id) }}"
                                        onsubmit="return confirm('Xác nhận bạn ĐÃ OK / đã nhận đủ kèo cho trận đấu này?')">
                                        @csrf
                                        <input type="hidden" name="pair_slot" value="primary">
                                        <button type="submit"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                                            Đã OK
                                        </button>
                                    </form>
                                    <button type="button"
                                        onclick="openRejectPrizeModalExplicit('primary', '{{ addslashes($match->loser?->name ?? 'Người thua') }}')"
                                        class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                                        Không đồng ý
                                    </button>
                                @endif

                                @if($match->canUserPayPrize(auth()->user(), 'primary'))
                                    <button type="button" onclick="openPayPrizeModalExplicit('primary', 'Xác nhận trả kèo', '{{ addslashes($match->prize_notes ?? '') }}')"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                                        <span>{{ $match->prize_status === 'rejected' ? 'Trả kèo lại' : ($match->prize_status === 'paid' ? 'Cập nhật trả kèo' : ($match->isUserDebtor(auth()->user()) ? 'Trả kèo trận này' : 'Xác nhận đã trả kèo')) }}</span>
                                    </button>
                                @endif
                            </div>
                        @endauth
                    </div>
                </div>
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

            <!-- Action Box if waiting for current user -->
            @if($isWaitingForUser)
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 space-y-3 shadow-sm">
                    <div class="text-sm font-bold text-blue-900 uppercase tracking-wide">Bạn là người tham gia trận đấu này!
                    </div>
                    <p class="text-sm text-blue-800">Vui lòng xác nhận kết quả hoặc gửi khiếu nại nếu kết quả khai báo không
                        chính xác.</p>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <form method="POST" action="{{ route('matches.confirm', $match->id) }}">
                            @csrf
                            <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold text-sm px-5 py-2.5 rounded-lg shadow-sm cursor-pointer transition">
                                Xác nhận kết quả
                            </button>
                        </form>

                        <button type="button" onclick="document.getElementById('disputeModal').classList.remove('hidden')"
                            class="bg-white hover:bg-gray-50 text-red-600 border border-red-300 font-semibold text-sm px-5 py-2.5 rounded-lg shadow-sm cursor-pointer transition">
                            Khiếu nại kết quả
                        </button>
                    </div>
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
                        <form method="POST" action="{{ route('admin.matches.resolve', $match->id) }}" class="space-y-3.5">
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

            <!-- Audit History -->
            <div class="pt-4 border-t border-gray-200 space-y-3">
                <h4 class="text-xs sm:text-sm font-bold text-gray-700 uppercase tracking-wider">Lịch sử thao tác</h4>
                <div class="divide-y divide-gray-100 border border-gray-200 rounded-lg bg-gray-50/50">
                    @foreach($match->histories as $h)
                        <div
                            class="p-3.5 text-xs sm:text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-4">
                            <div>
                                <span
                                    class="font-bold text-gray-900">{{ $h->user ? $h->user->name : 'Hệ thống tự động' }}</span>:
                                <span class="text-gray-700">{{ $h->note }}</span>
                            </div>
                            <span class="text-gray-400 shrink-0 text-xs">{{ $h->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

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

                <form method="POST" action="{{ route('matches.dispute', $match->id) }}" enctype="multipart/form-data"
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

                <form method="POST" action="{{ route('matches.pay-prize', $match->id) }}" class="space-y-4 text-sm">
                    @csrf
                    <input type="hidden" name="pair_slot" id="payModalPairSlot" value="primary">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Ghi chú xác nhận (tùy chọn)</label>
                        <input type="text" name="note" id="payModalNote" value="{{ $match->prize_notes }}"
                            placeholder="VD: Đã bao chầu nước, đã chuyển khoản..."
                            class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('payPrizeModal').classList.add('hidden')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">Hủy</button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">
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
                    <div class="mt-1"><b>Người trả:</b> <span id="rejectModalDebtorName">{{ $match->prizePaidBy?->name ?? 'Người thua' }}</span></div>
                    @if($match->prize_notes)
                        <div class="mt-1 text-xs"><b>Ghi chú đối thủ:</b> "{{ $match->prize_notes }}"</div>
                    @endif
                </div>

                <form method="POST" action="{{ route('matches.reject-prize', $match->id) }}" class="space-y-4 text-sm">
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
                            Khi hủy trận đấu, kết quả trận này sẽ không được tính vào bảng xếp hạng, chuỗi thắng/thua cũng như nợ kèo của các cơ thủ.
                        </p>

                        <form method="POST" action="{{ route('admin.matches.cancel', $match->id) }}" class="space-y-4 text-sm">
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