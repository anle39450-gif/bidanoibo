@extends('layouts.app', ['title' => 'Dashboard - Bida Nội Bộ'])

@section('content')
    <div class="space-y-6">

        <!-- Welcome Header -->
        <div
            class="bg-white border border-gray-200 rounded-lg p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">
                    Bảng Điều Khiển
                </h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">
                    Lưu lại kết quả các trận bida giao lưu, tự động xác nhận sau 24h và tính chuỗi thắng thua.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                @auth
                    <button type="button" onclick="triggerCreateMatchModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                        + Ghi nhận trận đấu
                    </button>
                @else
                    <a href="{{ route('login') }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                        + Ghi nhận trận đấu
                    </a>
                @endauth

                <a href="{{ route('calendar') }}"
                    class="bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-sm font-medium px-4 py-2.5 rounded-lg transition shadow-sm flex items-center gap-2">
                    <span>Xem Lịch Đấu</span>
                </a>
            </div>
        </div>

        @auth
            <!-- Personal Stats & Settlement Summary (Light Theme matching app) -->
            <div class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-3.5">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-12 h-12 rounded-full border border-gray-200 object-cover shadow-xs">
                        <div>
                            <div class="text-xs uppercase tracking-wider text-blue-600 font-bold">Thành tích & Kèo của bạn</div>
                            <div class="text-lg sm:text-xl font-bold text-gray-900">{{ auth()->user()->name }} <span class="text-xs text-gray-400 font-normal font-mono">@<span>{{ auth()->user()->username }}</span></span></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 text-xs sm:text-sm">
                        @if($myStats && $myStats['current_streak_count'] > 0)
                            <span class="px-3 py-1 rounded-full font-bold {{ $myStats['current_streak_type'] === 'win' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                Chuỗi: {{ $myStats['current_streak_count'] }} trận {{ $myStats['current_streak_type'] === 'win' ? 'Thắng' : 'Thua' }}
                            </span>
                        @endif
                        <a href="{{ route('members.show', auth()->user()->username) }}" class="text-blue-600 hover:text-blue-700 font-semibold underline text-xs sm:text-sm">
                            Xem chi tiết hồ sơ →
                        </a>
                    </div>
                </div>

                <!-- Personal Overview Charts (Biểu đồ tổng quan cá nhân: trực quan, ít số) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                    
                    <!-- Left: Biểu đồ Vòng Khuyên Tỷ Lệ Thắng (Donut Chart) -->
                    <div class="lg:col-span-6 bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-5 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xs">
                        <div class="relative w-36 h-36 shrink-0 flex items-center justify-center">
                            @php
                                $winRate = (float) ($myStats['win_rate'] ?? 0);
                                $circumference = 2 * 3.14159 * 50;
                                $strokeDash = ($winRate / 100) * $circumference;
                            @endphp
                            <!-- SVG Donut Chart -->
                            <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 120 120">
                                <!-- Background Circle (Losses) -->
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#fee2e2" stroke-width="12" />
                                <!-- Foreground Circle (Wins) -->
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#22c55e" stroke-width="12"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $circumference - $strokeDash }}"
                                    stroke-linecap="round"
                                    class="transition-all duration-1000 ease-out" />
                            </svg>
                            <!-- Center Label -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-2xl font-black text-gray-900 leading-none">{{ $myStats['win_rate'] ?? 0 }}%</span>
                                <span class="text-[11px] font-semibold text-gray-400 mt-1 uppercase tracking-wider">Tỷ lệ thắng</span>
                            </div>
                        </div>

                        <!-- Legend & Counts -->
                        <div class="flex-1 w-full space-y-3">
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Hiệu suất đối đầu</div>
                            
                            <!-- Wins visual bar -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-gray-700 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                        <span>Trận Thắng</span>
                                    </span>
                                    <span class="font-bold text-green-700">{{ $myStats['wins'] ?? 0 }} trận</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-green-500 h-2 rounded-full transition-all duration-700" style="width: {{ $myStats['win_rate'] ?? 0 }}%;"></div>
                                </div>
                            </div>

                            <!-- Losses visual bar -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-gray-700 flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                                        <span>Trận Thua</span>
                                    </span>
                                    <span class="font-bold text-red-600">{{ $myStats['losses'] ?? 0 }} trận</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-red-400 h-2 rounded-full transition-all duration-700" style="width: {{ 100 - ($myStats['win_rate'] ?? 0) }}%;"></div>
                                </div>
                            </div>

                            <div class="pt-1 text-[11px] text-gray-400 flex items-center justify-between border-t border-gray-100">
                                <span>Tổng trận đã đấu:</span>
                                <span class="font-bold text-gray-700">{{ $myStats['total_matches'] ?? 0 }} trận</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Biểu đồ Thanh Phân Bổ Kèo (Debts vs Receivables Visual Bar) -->
                    <div class="lg:col-span-6 bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-2xs">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Tình trạng Kèo & Nghĩa vụ</div>
                                <div class="text-sm font-extrabold text-gray-900 mt-0.5">
                                    @if($myUnpaidDebtsCount > 0)
                                        <span class="text-amber-700">Có {{ $myUnpaidDebtsCount }} kèo đang cần trả</span>
                                    @else
                                        <span class="text-emerald-700">Đã thanh toán sòng phẳng tất cả các kèo</span>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $myUnpaidDebtsCount > 0 ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $myUnpaidDebtsCount > 0 ? 'Cần trả kèo' : 'Sạch nợ' }}
                            </span>
                        </div>

                        <!-- Segmented Progress Bar -->
                        @php
                            $totalDebtsMetric = max($myUnpaidDebtsCount + $myPaidDebtsCount + $myUnpaidReceivablesCount, 1);
                            $unpaidPct = round(($myUnpaidDebtsCount / $totalDebtsMetric) * 100);
                            $paidPct = round(($myPaidDebtsCount / $totalDebtsMetric) * 100);
                            $recPct = 100 - $unpaidPct - $paidPct;
                        @endphp
                        <div class="space-y-1.5">
                            <div class="w-full bg-gray-100 rounded-full h-3.5 flex overflow-hidden p-0.5 border border-gray-200">
                                @if($myUnpaidDebtsCount > 0)
                                    <div class="bg-amber-500 rounded-l-full h-full transition-all duration-700" style="width: {{ max($unpaidPct, 8) }}%;" title="Kèo Cần Trả (Nợ): {{ $myUnpaidDebtsCount }}"></div>
                                @endif
                                @if($myPaidDebtsCount > 0)
                                    <div class="bg-emerald-500 h-full transition-all duration-700 {{ $myUnpaidDebtsCount == 0 ? 'rounded-l-full' : '' }} {{ $myUnpaidReceivablesCount == 0 ? 'rounded-r-full' : '' }}" style="width: {{ max($paidPct, 8) }}%;" title="Kèo Đã Trả: {{ $myPaidDebtsCount }}"></div>
                                @endif
                                @if($myUnpaidReceivablesCount > 0)
                                    <div class="bg-blue-500 rounded-r-full h-full transition-all duration-700 {{ ($myUnpaidDebtsCount == 0 && $myPaidDebtsCount == 0) ? 'rounded-l-full' : '' }}" style="width: {{ max($recPct, 8) }}%;" title="Kèo Chờ Nhận: {{ $myUnpaidReceivablesCount }}"></div>
                                @endif
                            </div>
                            <div class="text-[11px] text-gray-400 text-right">Phân bổ tỷ lệ thanh toán kèo</div>
                        </div>

                        <!-- 3 Visual Badges / Chips -->
                        <div class="grid grid-cols-3 gap-2 pt-1 border-t border-gray-100 text-center">
                            <!-- Unpaid Debts -->
                            <div class="p-2 rounded-lg {{ $myUnpaidDebtsCount > 0 ? 'bg-amber-50 border border-amber-200' : 'bg-gray-100/60' }}">
                                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 truncate">Kèo Cần Trả (Nợ)</div>
                                <div class="text-base sm:text-lg font-black {{ $myUnpaidDebtsCount > 0 ? 'text-amber-600' : 'text-gray-800' }} mt-0.5">
                                    {{ $myUnpaidDebtsCount }}
                                </div>
                            </div>

                            <!-- Paid Debts -->
                            <div class="p-2 rounded-lg bg-gray-100/60">
                                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 truncate">Kèo Đã Trả / Đang Chờ</div>
                                <div class="text-base sm:text-lg font-black text-emerald-600 mt-0.5">
                                    {{ $myPaidDebtsCount }}
                                </div>
                            </div>

                            <!-- Receivables -->
                            <div class="p-2 rounded-lg bg-gray-100/60">
                                <div class="text-[10px] sm:text-xs font-semibold text-gray-500 truncate">Chờ Nhận</div>
                                <div class="text-base sm:text-lg font-black text-blue-600 mt-0.5">
                                    {{ $myUnpaidReceivablesCount }}
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- AI Coach Performance Analysis (Nhận định phong độ từ AI ChatGPT) -->
                @if(isset($myAiAnalysis))
                    <div id="ai_coach_box" class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl p-5 sm:p-6 shadow-xl border border-indigo-800/40 space-y-4 relative overflow-hidden">
                        <!-- Background glow effect -->
                        <div class="absolute -top-24 -right-24 w-60 h-60 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                        <div class="absolute -bottom-24 -left-24 w-60 h-60 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

                        <!-- Header -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-indigo-800/40 pb-3 relative z-10">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-cyan-400 text-white flex items-center justify-center font-black shadow-md text-base">
                                    🤖
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm sm:text-base font-extrabold text-white tracking-wide">
                                            AI Billiards Coach • Nhận Định Phong Độ
                                        </h3>
                                        <span class="bg-indigo-500/20 text-cyan-300 border border-indigo-500/30 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            GPT-4o Mini
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-indigo-300/80 mt-0.5">
                                        Phân tích thời gian thực dựa trên toàn bộ chỉ số thi đấu của bạn
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <span id="ai_generated_time" class="text-[11px] text-indigo-300/70 font-mono">
                                    Cập nhật: {{ $myAiAnalysis['generated_at'] }}
                                </span>
                                <button type="button" onclick="refreshPlayerAiAnalysis()" id="btn_refresh_ai"
                                    class="bg-indigo-600/60 hover:bg-indigo-600 text-indigo-100 hover:text-white border border-indigo-500/30 font-semibold text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 cursor-pointer shadow-sm">
                                    <span id="ai_refresh_icon">🔄</span>
                                    <span>Làm mới</span>
                                </button>
                            </div>
                        </div>

                        <!-- Content Area -->
                        <div class="space-y-4 relative z-10">
                            <!-- Title Badge & General Assessment -->
                            <div class="space-y-2">
                                <div class="inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-400 to-orange-400 text-gray-950 font-black text-xs px-3 py-1 rounded-full shadow-sm">
                                    <span>🎯</span>
                                    <span id="ai_title_text">{{ $myAiAnalysis['title'] }}</span>
                                </div>
                                <p id="ai_assessment_text" class="text-sm text-indigo-100 leading-relaxed font-normal">
                                    {{ $myAiAnalysis['assessment'] }}
                                </p>
                            </div>

                            <!-- 3 Insight Cards Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1">
                                <!-- Card 1: Strengths -->
                                <div class="bg-white/5 border border-white/10 rounded-xl p-3.5 space-y-1 backdrop-blur-xs">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-emerald-400">
                                        <span>💎</span>
                                        <span>Điểm mạnh nổi bật</span>
                                    </div>
                                    <p id="ai_strengths_text" class="text-xs text-gray-300 leading-relaxed">
                                        {{ $myAiAnalysis['strengths'] }}
                                    </p>
                                </div>

                                <!-- Card 2: Tactical Advice -->
                                <div class="bg-white/5 border border-white/10 rounded-xl p-3.5 space-y-1 backdrop-blur-xs">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-amber-300">
                                        <span>💡</span>
                                        <span>Chiến thuật & Tâm lý</span>
                                    </div>
                                    <p id="ai_advice_text" class="text-xs text-gray-300 leading-relaxed">
                                        {{ $myAiAnalysis['advice'] }}
                                    </p>
                                </div>

                                <!-- Card 3: Prediction -->
                                <div class="bg-white/5 border border-white/10 rounded-xl p-3.5 space-y-1 backdrop-blur-xs">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-cyan-300">
                                        <span>🔮</span>
                                        <span>Dự báo trận tới</span>
                                    </div>
                                    <p id="ai_prediction_text" class="text-xs text-gray-300 leading-relaxed">
                                        {{ $myAiAnalysis['prediction'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Client-side script to refresh AI analysis via AJAX -->
                    <script>
                        async function refreshPlayerAiAnalysis() {
                            const btn = document.getElementById('btn_refresh_ai');
                            const icon = document.getElementById('ai_refresh_icon');
                            if (!btn) return;

                            btn.disabled = true;
                            if (icon) icon.classList.add('animate-spin');

                            try {
                                const res = await fetch('{{ route("dashboard.ai-analysis.refresh") }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                        'Accept': 'application/json'
                                    }
                                });

                                const data = await res.json();
                                if (data.success && data.analysis) {
                                    const a = data.analysis;
                                    document.getElementById('ai_title_text').textContent = a.title;
                                    document.getElementById('ai_assessment_text').textContent = a.assessment;
                                    document.getElementById('ai_strengths_text').textContent = a.strengths;
                                    document.getElementById('ai_advice_text').textContent = a.advice;
                                    document.getElementById('ai_prediction_text').textContent = a.prediction;
                                    document.getElementById('ai_generated_time').textContent = 'Cập nhật: ' + a.generated_at;
                                } else {
                                    alert(data.message || 'Không thể làm mới nhận định AI.');
                                }
                            } catch (e) {
                                console.error('Error refreshing AI analysis', e);
                                alert('Lỗi kết nối khi làm mới nhận định AI.');
                            } finally {
                                btn.disabled = false;
                                if (icon) icon.classList.remove('animate-spin');
                            }
                        }
                    </script>
                @endif

                <!-- Lời rủ bắt kèo ngoài đang chờ bạn phản hồi -->
                @if(isset($myPendingSideBetInvites) && $myPendingSideBetInvites->isNotEmpty())
                    <div class="bg-purple-50/80 border border-purple-200 rounded-xl p-4 mt-2 shadow-2xs space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-base">🎯</span>
                                <span class="text-sm font-black text-purple-900">
                                    Lời rủ vào kèo ngoài đang chờ bạn chốt ({{ $myPendingSideBetInvites->count() }} kèo)
                                </span>
                            </div>
                            <span class="text-xs text-purple-700 font-medium">Bấm nhận kèo hoặc từ chối</span>
                        </div>

                        <div class="divide-y divide-purple-100 space-y-2">
                            @foreach($myPendingSideBetInvites as $invite)
                                <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-extrabold text-gray-900">{{ $invite->creator->name }}</span>
                                            <span class="text-gray-500 text-xs">rủ bạn bắt kèo trận:</span>
                                            <span class="font-bold text-gray-800 bg-white px-2 py-0.5 rounded border border-purple-200 text-xs">
                                                {{ $invite->challenge->challenger_team_names }} vs {{ $invite->challenge->opponent_team_names }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-purple-900 flex items-center gap-2 flex-wrap">
                                            <span>👉 {{ $invite->creator->name }} bắt <b class="text-rose-700">{{ $invite->creator_team_name }}</b></span>
                                            <span>• Bạn nhận phe: <b class="text-emerald-700">{{ $invite->target_team_name }}</b></span>
                                            <span>• Món kèo: <b class="bg-amber-100 text-amber-900 px-1.5 py-0.2 rounded border border-amber-200">{{ $invite->prize }}</b></span>
                                            @if($invite->notes)
                                                <span class="text-gray-500 italic">"{{ $invite->notes }}"</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
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
                    </div>
                @endif

                <!-- If there are unpaid debts, show list with Quick Pay button! -->
                @if($myUnpaidMatches->isNotEmpty())
                    <div class="bg-amber-50/60 border border-amber-200 rounded-lg p-4 mt-2">
                        <div class="text-sm font-bold text-amber-900 flex items-center justify-between mb-2.5">
                            <span>Danh sách trận bạn thua chưa trả kèo ({{ $myUnpaidDebtsCount }} trận)</span>
                            <span class="text-xs text-amber-700 font-normal">Bấm "Trả kèo ngay" để cập nhật</span>
                        </div>
                        <div class="divide-y divide-amber-200/60 space-y-2">
                            @foreach($myUnpaidMatches as $debtMatch)
                                @php
                                    $userSlot = $debtMatch->getPrizeSlotForUser(auth()->user());
                                    $userStatus = $debtMatch->getPrizeStatusForUser(auth()->user());
                                    $counterpart = $debtMatch->getCounterpartForUser(auth()->user());
                                    $userNotes = $debtMatch->getPrizeNotesForUser(auth()->user());
                                    $userFeedback = $debtMatch->getPrizeFeedbackForUser(auth()->user());
                                @endphp
                                <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                                    <div>
                                        <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                            @if($debtMatch->isDoubles())
                                                <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 text-[11px] font-extrabold px-2 py-0.5 rounded-full border border-purple-200">
                                                    <span>👥</span> Kèo đôi tách chéo
                                                </span>
                                                <span>Bạn nợ riêng: <b class="text-blue-700 font-black text-sm">{{ $counterpart?->name }}</b></span>
                                            @else
                                                <span>Thua trước: <span class="text-blue-700">{{ $debtMatch->winner_names }}</span></span>
                                            @endif
                                            <span class="text-xs text-gray-400 font-normal font-mono">({{ $debtMatch->played_at->format('d/m/Y') }})</span>
                                            @if($userStatus === 'rejected')
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded font-bold">Người nhận không đồng ý</span>
                                            @elseif($userStatus === 'paid')
                                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded font-bold">Đã trả (Chờ đối thủ OK)</span>
                                            @else
                                                <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa trả</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600 mt-0.5">
                                            @if($debtMatch->isDoubles())
                                                Trận đôi: <span class="text-gray-700 font-medium">{{ $debtMatch->team1_names }} vs {{ $debtMatch->team2_names }}</span> •
                                            @endif
                                            Kèo phải trả: <b class="text-amber-900 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded">{{ $debtMatch->prize }}</b>
                                            @if($userNotes)
                                                <span class="text-gray-500 italic ml-1">"{{ $userNotes }}"</span>
                                            @endif
                                        </div>
                                        @if($userStatus === 'rejected' && $userFeedback)
                                            <div class="text-xs text-red-600 mt-1 italic font-medium">
                                                Lý do không đồng ý: "{{ $userFeedback }}"
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <form method="POST" action="{{ route('matches.pay-prize', $debtMatch->id) }}" onsubmit="return confirm('Xác nhận bạn đã trả kèo \'{{ $debtMatch->prize }}\' {{ $debtMatch->isDoubles() && $counterpart ? 'cho ' . $counterpart->name : '' }}?')">
                                            @csrf
                                            <input type="hidden" name="pair_slot" value="{{ $userSlot }}">
                                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs px-3.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                @if($debtMatch->isDoubles() && $counterpart)
                                                    {{ $userStatus === 'rejected' ? 'Trả kèo lại cho ' . $counterpart->name : ($userStatus === 'paid' ? 'Cập nhật trả kèo' : 'Trả kèo cho ' . $counterpart->name) }}
                                                @else
                                                    {{ $userStatus === 'rejected' ? 'Trả kèo lại' : ($userStatus === 'paid' ? 'Cập nhật trả kèo' : 'Trả kèo ngay') }}
                                                @endif
                                            </button>
                                        </form>
                                        <a href="{{ route('matches.show', $debtMatch->id) }}" class="text-xs text-blue-600 hover:underline font-medium">
                                            Chi tiết →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Kèo ngoài bạn thua chưa trả -->
                @if(isset($myUnpaidSideBets) && $myUnpaidSideBets->isNotEmpty())
                    <div class="bg-amber-50/60 border border-amber-200 rounded-lg p-4 mt-2">
                        <div class="text-sm font-bold text-amber-900 flex items-center justify-between mb-2.5">
                            <span class="flex items-center gap-1.5">
                                <span>🎯</span>
                                <span>Kèo ngoài bạn thua chưa trả ({{ $myUnpaidSideBets->count() }} kèo)</span>
                            </span>
                            <span class="text-xs text-amber-700 font-normal">Bấm "Đã trả kèo" sau khi thanh toán</span>
                        </div>
                        <div class="divide-y divide-amber-200/60 space-y-2">
                            @foreach($myUnpaidSideBets as $sb)
                                <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                                    <div>
                                        <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                            <span>Thua kèo ngoài trước: <b class="text-blue-700 font-black">{{ $sb->winner?->name }}</b></span>
                                            @if($sb->prize_status === 'paid')
                                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded font-bold">Đã trả (Chờ đối thủ xác nhận)</span>
                                            @else
                                                <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa trả</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600 mt-0.5">
                                            Trận: <span class="text-gray-700 font-medium">{{ $sb->challenge->challenger_team_names }} vs {{ $sb->challenge->opponent_team_names }}</span> •
                                            Món kèo: <b class="text-amber-900 bg-amber-100 border border-amber-200 px-2 py-0.5 rounded">{{ $sb->prize }}</b>
                                            @if($sb->notes)
                                                <span class="text-gray-500 italic ml-1">"{{ $sb->notes }}"</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
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
                    </div>
                @endif

                <!-- If there are unpaid receivables (kèo người khác chưa trả cho bạn), show list! -->
                @if($myUnpaidReceivables->isNotEmpty())
                    <div class="bg-blue-50/60 border border-blue-200 rounded-lg p-4 mt-2">
                        <div class="text-sm font-bold text-blue-900 flex items-center justify-between mb-2.5">
                            <span>Kèo đối thủ chưa trả cho bạn (Chờ nhận: {{ $myUnpaidReceivablesCount }} trận)</span>
                            <span class="text-xs text-blue-700 font-normal">Trận bạn thắng đối thủ chưa hoàn tất trả kèo</span>
                        </div>
                        <div class="divide-y divide-blue-200/60 space-y-2">
                            @foreach($myUnpaidReceivables as $recMatch)
                                @php
                                    $userSlot = $recMatch->getPrizeSlotForUser(auth()->user());
                                    $userStatus = $recMatch->getPrizeStatusForUser(auth()->user());
                                    $counterpart = $recMatch->getCounterpartForUser(auth()->user());
                                    $userNotes = $recMatch->getPrizeNotesForUser(auth()->user());
                                    $userFeedback = $recMatch->getPrizeFeedbackForUser(auth()->user());
                                @endphp
                                <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                                    <div>
                                        <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                            @if($recMatch->isDoubles())
                                                <span class="inline-flex items-center gap-1 bg-purple-100 text-purple-800 text-[11px] font-extrabold px-2 py-0.5 rounded-full border border-purple-200">
                                                    <span>👥</span> Kèo đôi tách chéo
                                                </span>
                                                <span><b class="text-red-600 font-black text-sm">{{ $counterpart?->name }}</b> nợ riêng bạn</span>
                                            @else
                                                <span>Thắng trước: <span class="text-red-600">{{ $recMatch->loser_names }}</span></span>
                                            @endif
                                            <span class="text-xs text-gray-400 font-normal font-mono">({{ $recMatch->played_at->format('d/m/Y') }})</span>
                                            @if($userStatus === 'paid')
                                                <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded font-bold">Đối thủ đã trả (Cần bạn xác nhận)</span>
                                            @elseif($userStatus === 'rejected')
                                                <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded font-bold">Bạn đã phản hồi Không đồng ý</span>
                                            @else
                                                <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa trả</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600 mt-0.5">
                                            @if($recMatch->isDoubles())
                                                Trận đôi: <span class="text-gray-700 font-medium">{{ $recMatch->team1_names }} vs {{ $recMatch->team2_names }}</span> •
                                            @endif
                                            Kèo chờ nhận: <b class="text-blue-900 bg-blue-100 border border-blue-200 px-2 py-0.5 rounded">{{ $recMatch->prize }}</b>
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
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if($userStatus === 'paid')
                                            <form method="POST" action="{{ route('matches.confirm-prize', $recMatch->id) }}" onsubmit="return confirm('Xác nhận bạn ĐÃ OK / đã nhận đủ kèo \'{{ $recMatch->prize }}\' {{ $recMatch->isDoubles() && $counterpart ? 'từ ' . $counterpart->name : '' }}?')">
                                                @csrf
                                                <input type="hidden" name="pair_slot" value="{{ $userSlot }}">
                                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                    Đã OK
                                                </button>
                                            </form>
                                            <button type="button" onclick="openRejectPrizeModal({{ $recMatch->id }}, '{{ addslashes($recMatch->prize) }}', '{{ addslashes($counterpart?->name ?? $recMatch->loser_names) }}', '{{ $userSlot }}')" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-xs px-2.5 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                Không đồng ý
                                            </button>
                                        @endif
                                        <a href="{{ route('matches.show', $recMatch->id) }}" class="text-xs text-blue-600 hover:underline font-medium">
                                            Xem chi tiết →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Kèo ngoài đối thủ nợ bạn -->
                @if(isset($myUnpaidSideBetReceivables) && $myUnpaidSideBetReceivables->isNotEmpty())
                    <div class="bg-blue-50/60 border border-blue-200 rounded-lg p-4 mt-2">
                        <div class="text-sm font-bold text-blue-900 flex items-center justify-between mb-2.5">
                            <span class="flex items-center gap-1.5">
                                <span>🎯</span>
                                <span>Kèo ngoài đối thủ chưa trả cho bạn ({{ $myUnpaidSideBetReceivables->count() }} kèo)</span>
                            </span>
                            <span class="text-xs text-blue-700 font-normal">Kèo ngoài bạn thắng chờ nhận</span>
                        </div>
                        <div class="divide-y divide-blue-200/60 space-y-2">
                            @foreach($myUnpaidSideBetReceivables as $sb)
                                <div class="pt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                                    <div>
                                        <div class="font-bold text-gray-900 flex items-center flex-wrap gap-2">
                                            <span><b class="text-red-600 font-black">{{ $sb->loser?->name }}</b> nợ bạn</span>
                                            @if($sb->prize_status === 'paid')
                                                <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded font-bold">Đối thủ báo đã trả (Cần bạn xác nhận)</span>
                                            @else
                                                <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chưa trả</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-600 mt-0.5">
                                            Trận: <span class="text-gray-700 font-medium">{{ $sb->challenge->challenger_team_names }} vs {{ $sb->challenge->opponent_team_names }}</span> •
                                            Món kèo: <b class="text-blue-900 bg-blue-100 border border-blue-200 px-2 py-0.5 rounded">{{ $sb->prize }}</b>
                                            @if($sb->notes)
                                                <span class="text-gray-500 italic ml-1">"{{ $sb->notes }}"</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        @if($sb->prize_status === 'paid')
                                            <button type="button" onclick="handleConfirmSideBet({{ $sb->id }})"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shadow-xs transition cursor-pointer">
                                                Xác nhận đã nhận OK
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endauth

        <!-- System Overview Charts (Biểu đồ tổng quan hệ thống: ít số, dễ nhìn) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Chart 1: Biểu đồ Cột Hoạt Động Thi Đấu 7 Ngày Gần Nhất (7 Cols) -->
            <div class="lg:col-span-7 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <span>Biểu đồ thi đấu 7 ngày gần nhất</span>
                        </h2>
                        <div class="text-xs text-gray-400 mt-0.5">Số lượng trận giao lưu diễn ra trong tuần</div>
                    </div>
                    <span class="text-xs font-extrabold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $last7Days->sum('count') }} trận / tuần
                    </span>
                </div>

                <!-- 7 Columns Visual Bar Chart -->
                <div class="pt-2">
                    <div class="h-44 flex items-end justify-between gap-2 sm:gap-4 px-2 pb-2 border-b border-gray-100">
                        @foreach($last7Days as $day)
                            @php
                                $heightPct = $day['count'] > 0 ? max(round(($day['count'] / $max7DaysCount) * 100), 12) : 6;
                            @endphp
                            <div class="flex-1 flex flex-col items-center h-full justify-end group cursor-pointer relative">
                                
                                <!-- Floating Tooltip on Hover / Top Count -->
                                <div class="mb-1 text-[11px] font-extrabold {{ $day['is_today'] ? 'text-emerald-700' : 'text-gray-600' }} transition-transform group-hover:-translate-y-0.5">
                                    {{ $day['count'] }}
                                </div>

                                <!-- Vertical Column Bar -->
                                <div class="w-full max-w-[42px] rounded-t-lg transition-all duration-700 ease-out flex items-center justify-center relative
                                    {{ $day['is_today'] 
                                        ? 'bg-gradient-to-t from-emerald-600 to-teal-400 shadow-sm ring-2 ring-emerald-300' 
                                        : ($day['count'] > 0 
                                            ? 'bg-gradient-to-t from-blue-600 to-indigo-500 hover:from-blue-500 hover:to-indigo-400 shadow-2xs' 
                                            : 'bg-gray-100 hover:bg-gray-200') }}"
                                    style="height: {{ $heightPct }}%;">
                                </div>

                                <!-- Day Label Underneath -->
                                <div class="mt-2 text-center">
                                    <div class="text-[11px] sm:text-xs font-bold {{ $day['is_today'] ? 'text-emerald-700' : 'text-gray-700' }}">
                                        {{ $day['day_name'] }}
                                    </div>
                                    <div class="text-[9px] sm:text-[10px] text-gray-400 font-mono">
                                        {{ $day['date'] }}
                                    </div>
                                </div>

                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer Summary Note -->
                <div class="text-xs text-gray-500 flex items-center justify-between pt-1">
                    <span class="flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="text-gray-600">Cột xanh lá là ngày Hôm nay</span>
                    </span>
                    <a href="{{ route('calendar') }}" class="text-blue-600 hover:underline font-semibold">
                        Xem chi tiết lịch →
                    </a>
                </div>
            </div>

            <!-- Chart 2: Biểu đồ Phân Bố Trạng Thái & Quy Mô Toàn Hệ Thống (5 Cols) -->
            <div class="lg:col-span-5 bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-sm flex flex-col justify-between space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900">Phân bố trạng thái trận</h2>
                        <div class="text-xs text-gray-400 mt-0.5">Tỷ lệ xác nhận và hoàn tất</div>
                    </div>
                    <span class="text-xs font-extrabold px-2.5 py-1 rounded-full bg-gray-100 text-gray-800">
                        {{ $totalMembers }} cơ thủ
                    </span>
                </div>

                <!-- Donut / Segmented Progress Bar -->
                <div class="space-y-3 py-2">
                    <div class="w-full bg-gray-100 rounded-full h-4 flex overflow-hidden p-0.5 border border-gray-200 shadow-inner">
                        @if($totalMatches > 0)
                            <div class="bg-emerald-500 rounded-l-full h-full transition-all duration-700" style="width: {{ max($statusDistribution['confirmed_pct'], 8) }}%;" title="Đã xác nhận: {{ $totalMatches }} trận"></div>
                        @endif
                        @if($pendingCount > 0)
                            <div class="bg-amber-400 h-full transition-all duration-700 {{ $totalMatches == 0 ? 'rounded-l-full' : '' }} {{ $disputedCount == 0 ? 'rounded-r-full' : '' }}" style="width: {{ max($statusDistribution['pending_pct'], 8) }}%;" title="Chờ xác nhận: {{ $pendingCount }} trận"></div>
                        @endif
                        @if($disputedCount > 0)
                            <div class="bg-red-500 rounded-r-full h-full transition-all duration-700 {{ ($totalMatches == 0 && $pendingCount == 0) ? 'rounded-l-full' : '' }}" style="width: {{ max($statusDistribution['disputed_pct'], 8) }}%;" title="Tranh chấp: {{ $disputedCount }} trận"></div>
                        @endif
                    </div>

                    <!-- Visual Segment Breakdown List -->
                    <div class="space-y-2 pt-1">
                        <!-- Confirmed -->
                        <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-emerald-50/70 border border-emerald-100">
                            <span class="flex items-center gap-2 font-bold text-emerald-900">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                <span>Đã xác nhận chính thức</span>
                            </span>
                            <span class="font-black text-emerald-700">{{ $totalMatches }} trận <span class="font-normal text-emerald-600">({{ $statusDistribution['confirmed_pct'] }}%)</span></span>
                        </div>

                        <!-- Pending -->
                        <div class="flex items-center justify-between text-xs p-2 rounded-lg bg-amber-50/70 border border-amber-100">
                            <span class="flex items-center gap-2 font-bold text-amber-900">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                                <span>Chờ đối thủ xác nhận</span>
                            </span>
                            <span class="font-black text-amber-700">{{ $pendingCount }} trận <span class="font-normal text-amber-600">({{ $statusDistribution['pending_pct'] }}%)</span></span>
                        </div>

                        <!-- Disputed -->
                        <div class="flex items-center justify-between text-xs p-2 rounded-lg {{ $disputedCount > 0 ? 'bg-red-50 border border-red-200' : 'bg-gray-50/70 border border-gray-100 text-gray-400' }}">
                            <span class="flex items-center gap-2 font-bold {{ $disputedCount > 0 ? 'text-red-900' : 'text-gray-500' }}">
                                <span class="w-2.5 h-2.5 rounded-full {{ $disputedCount > 0 ? 'bg-red-500 animate-ping' : 'bg-gray-300' }}"></span>
                                <span>Đang tranh chấp</span>
                            </span>
                            <span class="font-black {{ $disputedCount > 0 ? 'text-red-700' : 'text-gray-500' }}">{{ $disputedCount }} trận</span>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] text-gray-400 pt-1 border-t border-gray-100 flex items-center justify-between">
                    <span>Tổng số trận ghi nhận: <b>{{ $statusDistribution['total'] }}</b></span>
                    <span>Thành viên hoạt động: <b>{{ $totalMembers }}</b></span>
                </div>
            </div>

        </div>

        <!-- Spotlight Cards: Quán quân & Chiến thần bao kèo -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Top Player -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded">TOP 1</span>
                    <div>
                        <span class="text-xs sm:text-sm font-semibold text-amber-600 uppercase tracking-wider">Quán quân
                            tháng này</span>
                        @if($topPlayer)
                            <div class="text-lg sm:text-xl font-bold text-gray-900 mt-0.5">{{ $topPlayer['user']->name }}</div>
                            <div class="text-sm text-gray-600 font-medium mt-0.5">
                                Thắng: <b class="text-green-600">{{ $topPlayer['wins'] }}</b> trận
                                ({{ $topPlayer['win_rate'] }}%)
                            </div>
                        @else
                            <div class="text-sm text-gray-400 mt-1">Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
                @if($topPlayer)
                    <a href="{{ route('members.show', $topPlayer['user']->username) }}"
                        class="text-sm font-semibold text-blue-600 hover:underline shrink-0">
                        Xem hồ sơ →
                    </a>
                @endif
            </div>

            <!-- Loser of the Month -->
            <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <span class="px-2.5 py-1 bg-red-100 text-red-800 text-xs font-bold rounded">KÈO NHIỀU</span>
                    <div>
                        <span class="text-xs sm:text-sm font-semibold text-red-600 uppercase tracking-wider">Chiến thần bao
                            kèo</span>
                        @if($loserOfMonth)
                            <div class="text-lg sm:text-xl font-bold text-gray-900 mt-0.5">{{ $loserOfMonth['user']->name }}
                            </div>
                            <div class="text-sm text-gray-600 font-medium mt-0.5">
                                Thua: <b class="text-red-600">{{ $loserOfMonth['losses'] }}</b> trận •
                                <i>{{ $loserOfMonth['humorous_title'] }}</i>
                            </div>
                        @else
                            <div class="text-sm text-gray-400 mt-1">Chưa có dữ liệu</div>
                        @endif
                    </div>
                </div>
                @if($loserOfMonth)
                    <a href="{{ route('members.show', $loserOfMonth['user']->username) }}"
                        class="text-sm font-semibold text-blue-600 hover:underline shrink-0">
                        Xem hồ sơ →
                    </a>
                @endif
            </div>
        </div>

        <!-- Main 2-column layout: Recent Matches & Mini Leaderboard -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left 2 Cols: Recent Matches -->
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Trận đấu gần đây</h2>
                    <a href="{{ route('matches.index') }}" class="text-sm font-semibold text-blue-600 hover:underline">
                        Xem tất cả ({{ $totalMatches }}) →
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($recentMatches as $m)
                        @php
                            $isConfirmed = in_array($m->status, ['confirmed', 'resolved']);
                        @endphp
                        <div class="p-4 hover:bg-gray-50 transition flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 text-xs sm:text-sm">
                                    <span
                                        class="px-2.5 py-0.5 rounded text-xs font-semibold
                                                                                {{ $m->status === 'confirmed' ? 'bg-green-100 text-green-800' : ($m->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $m->status === 'confirmed' ? 'Đã xác nhận' : ($m->status === 'pending' ? 'Chờ xác nhận' : 'Tranh chấp') }}
                                    </span>
                                    <span class="text-gray-400">{{ $m->played_at->format('d/m/Y H:i') }}</span>
                                </div>

                                <div class="text-base font-bold text-gray-900">
                                    <span>{{ $m->team1_names }}</span>
                                    <span class="text-gray-400 font-normal px-1">vs</span>
                                    <span>{{ $m->team2_names }}</span>
                                    @if($isConfirmed)
                                        <span class="ml-1 text-xs sm:text-sm text-green-700 font-medium">({{ $m->winner_names }}
                                            thắng)</span>
                                    @endif
                                </div>

                                <div class="text-sm text-gray-600 flex items-center gap-2">
                                    <span>Kèo: <b class="text-gray-900">{{ $m->prize }}</b></span>
                                    @if($isConfirmed)
                                        @if($m->isPrizePaid())
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-green-100 text-green-800">Đã trả kèo</span>
                                        @else
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-amber-100 text-amber-800">Chưa trả</span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <a href="{{ route('matches.show', $m->id) }}"
                                class="text-sm font-semibold text-blue-600 hover:underline shrink-0">
                                Chi tiết →
                            </a>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500">
                            Chưa có trận đấu nào được ghi nhận.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right 1 Col: Mini Leaderboard -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden self-start">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-900">Bảng xếp hạng tháng</h2>
                    <a href="{{ route('leaderboard') }}" class="text-sm font-semibold text-blue-600 hover:underline">
                        Đầy đủ →
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse($miniLeaderboard as $idx => $item)
                        <div class="p-3.5 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span
                                    class="w-5 text-center text-sm font-extrabold {{ $idx === 0 ? 'text-amber-600' : 'text-gray-400' }}">
                                    {{ $idx + 1 }}
                                </span>
                                <img src="{{ $item['user']->avatar_url }}" alt="{{ $item['user']->name }}"
                                    class="w-9 h-9 rounded-full border border-gray-200 object-cover shrink-0">
                                <div class="truncate">
                                    <a href="{{ route('members.show', $item['user']->username) }}"
                                        class="text-sm font-bold text-gray-900 hover:underline block leading-tight truncate">
                                        {{ $item['user']->name }}
                                    </a>
                                    <span class="text-xs text-gray-400">@<span>{{ $item['user']->username }}</span></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-900">{{ $item['wins'] }} Thắng</div>
                                    <div class="text-xs text-gray-500 font-medium">{{ $item['win_rate'] }}%</div>
                                </div>
                                @if(auth()->check() && $item['user']->id !== auth()->id())
                                    <button type="button" onclick="openSendChallengeModal({{ $item['user']->id }})"
                                        title="Khiêu chiến {{ $item['user']->name }}"
                                        class="text-xs text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded-lg border border-red-200 font-bold transition cursor-pointer shrink-0">
                                        ⚔️
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">
                            Chưa có dữ liệu bảng xếp hạng.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

    <!-- Reject Prize Feedback Modal -->
    <div id="rejectPrizeModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60" onclick="closeRejectPrizeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-xl p-6 max-w-md w-full shadow-2xl border border-gray-200 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                    <h3 class="text-lg font-bold text-gray-900">Phản Hồi Không Đồng Ý Trả Kèo</h3>
                    <button type="button" onclick="closeRejectPrizeModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold cursor-pointer">&times;</button>
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
                        <input type="text" name="reason" placeholder="VD: Chưa nhận được nước, chưa thấy chuyển khoản..." class="w-full border border-gray-300 rounded-lg p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500">
                    </div>

                    <div class="flex justify-end gap-2.5 pt-3 border-t border-gray-200">
                        <button type="button" onclick="closeRejectPrizeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-medium hover:bg-gray-50 cursor-pointer">Hủy</button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold cursor-pointer shadow-sm">
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