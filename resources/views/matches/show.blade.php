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
        <div>
            <a href="{{ route('matches.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-semibold inline-flex items-center gap-1">
                ← Quay lại danh sách
            </a>
        </div>

        <!-- Match Detail Card -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 sm:p-7 space-y-6">

            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-200 gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-3 py-1 rounded-md text-sm font-bold
                        {{ $match->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($match->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                        {{ $statusLabel }}
                    </span>
                    <span class="text-sm font-medium text-gray-500">Mã trận: #{{ $match->id }}</span>
                    <span class="text-sm font-medium text-gray-500">• Diễn ra: {{ $match->played_at->format('d/m/Y H:i') }}</span>
                </div>

                <span class="text-sm text-gray-500">
                    Người tạo: <b class="text-gray-800">{{ $match->creator->name }}</b> ({{ $match->created_from === 'telegram' ? 'Qua Telegram' : 'Qua Website' }})
                </span>
            </div>

            <!-- 24h Countdown alert if pending -->
            @if($isPending)
                @php
                    $remainingSecs = $match->getRemainingSecondsForAutoConfirm();
                    $hours = floor($remainingSecs / 3600);
                    $mins = floor(($remainingSecs % 3600) / 60);
                @endphp
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg text-sm text-amber-900 flex flex-col sm:flex-row items-center justify-between gap-3 shadow-sm">
                    <div>
                        <b>Quy tắc tự động xác nhận sau 24 giờ:</b><br>
                        Nếu đối thủ không phản hồi trước <b>{{ $match->auto_confirm_at?->format('H:i d/m/Y') }}</b>, hệ thống sẽ tự động chốt kết quả.
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
                            <img src="{{ $match->winner->avatar_url }}" alt="{{ $match->winner->name }}" class="w-12 h-12 rounded-full border-2 border-green-300 object-cover shadow-sm">
                            <div>
                                <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">{{ $match->winner->name }}</div>
                                <div class="text-xs sm:text-sm text-gray-500 mt-0.5">@<span>{{ $match->winner->username }}</span></div>
                            </div>
                        </div>
                        @if($match->winnerPartner)
                            <div class="flex items-center gap-3.5 pt-2 border-t border-green-200/60">
                                <img src="{{ $match->winnerPartner->avatar_url }}" alt="{{ $match->winnerPartner->name }}" class="w-12 h-12 rounded-full border-2 border-green-300 object-cover shadow-sm">
                                <div>
                                    <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">{{ $match->winnerPartner->name }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">@<span>{{ $match->winnerPartner->username }}</span></div>
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
                            <img src="{{ $match->loser->avatar_url }}" alt="{{ $match->loser->name }}" class="w-12 h-12 rounded-full border-2 border-red-300 object-cover shadow-sm">
                            <div>
                                <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">{{ $match->loser->name }}</div>
                                <div class="text-xs sm:text-sm text-gray-500 mt-0.5">@<span>{{ $match->loser->username }}</span></div>
                            </div>
                        </div>
                        @if($match->loserPartner)
                            <div class="flex items-center gap-3.5 pt-2 border-t border-red-200/60">
                                <img src="{{ $match->loserPartner->avatar_url }}" alt="{{ $match->loserPartner->name }}" class="w-12 h-12 rounded-full border-2 border-red-300 object-cover shadow-sm">
                                <div>
                                    <div class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">{{ $match->loserPartner->name }}</div>
                                    <div class="text-xs sm:text-sm text-gray-500 mt-0.5">@<span>{{ $match->loserPartner->username }}</span></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Prize -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 flex items-center justify-between">
                <div>
                    <div class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider">Phần thưởng / Kèo giao lưu</div>
                    <div class="text-lg sm:text-xl font-bold text-gray-900 mt-1">{{ $match->prize }}</div>
                </div>
            </div>

            <!-- Notes & Evidence -->
            @if($match->notes || $match->evidence_path)
                <div class="space-y-3 pt-2">
                    @if($match->notes)
                        <div>
                            <div class="text-xs sm:text-sm font-semibold text-gray-700 uppercase mb-1.5">Ghi chú</div>
                            <p class="text-sm sm:text-base text-gray-800 bg-gray-50 p-3.5 rounded-lg border border-gray-200 italic">"{{ $match->notes }}"</p>
                        </div>
                    @endif

                    @if($match->evidence_path)
                        <div>
                            <div class="text-xs sm:text-sm font-semibold text-gray-700 uppercase mb-2">Ảnh bằng chứng</div>
                            <img src="{{ asset('storage/' . $match->evidence_path) }}" alt="Bằng chứng" class="rounded-lg border border-gray-300 max-h-96 shadow-sm">
                        </div>
                    @endif
                </div>
            @endif

            <!-- Action Box if waiting for current user -->
            @if($isWaitingForUser)
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 space-y-3 shadow-sm">
                    <div class="text-sm font-bold text-blue-900 uppercase tracking-wide">Bạn là người tham gia trận đấu này!</div>
                    <p class="text-sm text-blue-800">Vui lòng xác nhận kết quả hoặc gửi khiếu nại nếu kết quả khai báo không chính xác.</p>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <form method="POST" action="{{ route('matches.confirm', $match->id) }}">
                            @csrf
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold text-sm px-5 py-2.5 rounded-lg shadow-sm cursor-pointer transition">
                                Xác nhận kết quả
                            </button>
                        </form>

                        <button 
                            type="button" 
                            onclick="document.getElementById('disputeModal').classList.remove('hidden')" 
                            class="bg-white hover:bg-gray-50 text-red-600 border border-red-300 font-semibold text-sm px-5 py-2.5 rounded-lg shadow-sm cursor-pointer transition"
                        >
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
                        <img src="{{ asset('storage/' . $match->dispute_evidence_path) }}" alt="Evidence" class="rounded-lg max-h-56 border border-red-200 mt-2 shadow-sm">
                    @endif
                </div>
            @endif

            <!-- Admin Resolution Form -->
            @auth
                @if(auth()->user()->isAdmin() && $isDisputed)
                    <div class="bg-amber-50 border border-amber-300 rounded-xl p-5 space-y-3.5 shadow-sm">
                        <div class="text-sm font-bold text-amber-900 uppercase tracking-wide">Xử lý tranh chấp (Dành cho Quản trị viên)</div>
                        <form method="POST" action="{{ route('admin.matches.resolve', $match->id) }}" class="space-y-3.5">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-sm">
                                <label class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                    <input type="radio" name="resolution" value="confirm_initial" checked>
                                    <span class="font-medium">Giữ nguyên kết quả</span>
                                </label>
                                <label class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                    <input type="radio" name="resolution" value="reverse_winner">
                                    <span class="font-medium">Đảo ngược người thắng</span>
                                </label>
                                <label class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                    <input type="radio" name="resolution" value="cancel">
                                    <span class="font-medium">Hủy trận đấu</span>
                                </label>
                            </div>

                            <textarea name="admin_notes" required rows="2" placeholder="Nhập lý do xử lý của quản trị viên..." class="w-full text-sm border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>

                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold text-sm px-4 py-2 rounded-lg cursor-pointer transition shadow-sm">
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
                        <div class="p-3.5 text-xs sm:text-sm flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-4">
                            <div>
                                <span class="font-bold text-gray-900">{{ $h->user ? $h->user->name : 'Hệ thống tự động' }}</span>:
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
        <div class="fixed inset-0 bg-gray-900/60" onclick="document.getElementById('disputeModal').classList.add('hidden')"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Khiếu nại kết quả trận đấu</h3>
                    <button type="button" onclick="document.getElementById('disputeModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
                </div>

                <form method="POST" action="{{ route('matches.dispute', $match->id) }}" enctype="multipart/form-data" class="space-y-4 text-sm">
                    @csrf
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Lý do khiếu nại <span class="text-red-500">*</span></label>
                        <textarea name="reason" required rows="3" placeholder="Nêu rõ lý do..." class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                    </div>

                    <div>
                        <label class="block font-semibold text-gray-700 mb-1.5">Ảnh bằng chứng (tùy chọn)</label>
                        <input type="file" name="evidence" accept="image/*" class="w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="document.getElementById('disputeModal').classList.add('hidden')" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">Gửi khiếu nại</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
