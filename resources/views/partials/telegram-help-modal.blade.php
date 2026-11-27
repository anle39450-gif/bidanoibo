<!-- Telegram Help Modal -->
<div 
    id="telegramHelpModal" 
    class="fixed inset-0 z-50 overflow-y-auto hidden" 
    aria-labelledby="telegramHelpTitle" 
    role="dialog" 
    aria-modal="true"
>
    <!-- Backdrop -->
    <div 
        class="fixed inset-0 bg-gray-900/60 transition-opacity" 
        onclick="closeTelegramHelpModal()"
    ></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-200">
            
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                        TG
                    </div>
                    <div>
                        <h3 id="telegramHelpTitle" class="text-base font-bold text-gray-900">
                            Hướng Dẫn Kết Nối và Sử Dụng Telegram Bot
                        </h3>
                        <p class="text-xs text-gray-500">Mã kết nối cá nhân, ghi trận đơn/đôi và danh sách lệnh bot</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    onclick="closeTelegramHelpModal()" 
                    class="text-gray-400 hover:text-gray-600 rounded-lg p-1.5 hover:bg-gray-100 transition cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Content -->
            <div class="px-6 py-5 space-y-5 text-sm text-gray-700 max-h-[75vh] overflow-y-auto">
                
                @php
                    $botConfig = \App\Models\TelegramBotConfig::current();
                    $currentUser = auth()->user();
                @endphp

                <!-- 1. Personal Connection Box -->
                @if($currentUser)
                    <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ $currentUser->avatar_url }}" alt="{{ $currentUser->name }}" class="w-8 h-8 rounded-full border border-blue-200 object-cover">
                                <div>
                                    <div class="font-bold text-gray-900 text-sm leading-tight">{{ $currentUser->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">@<span>{{ $currentUser->username }}</span></div>
                                </div>
                            </div>

                            @if($currentUser->telegram_id)
                                <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-1 rounded-full flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Đã kết nối (ID: {{ $currentUser->telegram_id }})
                                </span>
                            @else
                                <span class="bg-amber-100 text-amber-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                                    Chưa kết nối Telegram
                                </span>
                            @endif
                        </div>

                        @if(!$currentUser->telegram_id)
                            <div class="bg-white p-3.5 rounded-lg border border-blue-100 space-y-2.5 text-xs">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <span class="text-gray-600 font-medium">Mã kết nối riêng của bạn:</span>
                                    <div class="flex items-center gap-2">
                                        <code class="text-sm font-bold font-mono text-blue-700 bg-blue-50 px-2.5 py-0.5 rounded border border-blue-200 select-all">
                                            {{ $currentUser->getTelegramLinkCode() }}
                                        </code>
                                    </div>
                                </div>

                                <div class="pt-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                    @if($botConfig->bot_username)
                                        <a 
                                            href="https://t.me/{{ $botConfig->bot_username }}?start={{ $currentUser->getTelegramLinkCode() }}" 
                                            target="_blank" 
                                            class="bg-primary hover:bg-primary-hover text-white font-semibold text-xs py-2 px-3.5 rounded-lg text-center transition shadow-xs flex items-center justify-center gap-1.5"
                                        >
                                            <span>Mở Telegram kết nối ngay</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    @endif
                                    <span class="text-gray-400 text-center sm:text-left self-center">Hoặc gửi tin nhắn: <code class="font-mono text-gray-800 bg-gray-100 px-1 py-0.5 rounded">/start {{ $currentUser->getTelegramLinkCode() }}</code> cho Bot</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- 2. Ghi nhận trận ĐƠN (1vs1) -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-sm">
                        <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0">1</span>
                        <span>Ghi nhận trận ĐƠN (1vs1) qua Telegram</span>
                    </div>
                    <div class="pl-7 space-y-2 text-xs text-gray-600 leading-relaxed">
                        <div>
                            <p class="font-semibold text-gray-800 mb-1">Cách 1 - Tạo nhanh với chính bạn (khi đã đăng nhập Bot):</p>
                            <div class="bg-gray-100 border border-gray-200 rounded-lg p-2 font-mono text-xs text-gray-900 font-bold">
                                /vs [đối_thủ] &nbsp;<span class="text-gray-400 font-normal">(hoặc /[đối_thủ])</span>
                            </div>
                            <p class="mt-1"><em>Ví dụ:</em> Bạn vừa đánh với Bình, gõ: <code class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-mono font-semibold">/vs binh</code> (hoặc <code class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-mono font-semibold">/binh</code>)</p>
                        </div>

                        <div class="pt-1.5 border-t border-gray-100">
                            <p class="font-semibold text-gray-800 mb-1">Cách 2 - Chỉ định cả 2 cơ thủ:</p>
                            <div class="bg-gray-100 border border-gray-200 rounded-lg p-2 font-mono text-xs text-gray-900 font-bold">
                                /user1 user2 &nbsp;<span class="text-gray-400 font-normal">(hoặc /tran user1 user2)</span>
                            </div>
                            <p class="mt-1"><em>Ví dụ:</em> Trận giữa Admin và Bình, gõ: <code class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-mono font-semibold">/admin binh</code></p>
                        </div>

                        <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 p-2 rounded-lg">
                            💡 <b>Lưu ý:</b> Tên cơ thủ là <b>username</b> đã được tạo trên website (không phân biệt chữ hoa hay thường). Bot sẽ hiển thị ngay 2 nút bấm chọn ai thắng để bạn chạm chọn!
                        </p>
                    </div>
                </div>

                <!-- 3. Ghi nhận trận ĐÔI (2vs2) -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-sm">
                        <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0">2</span>
                        <span>Ghi nhận trận ĐÔI (2vs2)</span>
                    </div>
                    <div class="pl-7 space-y-2 text-xs text-gray-600 leading-relaxed">
                        <div>
                            <p class="font-semibold text-gray-800 mb-1">Cách 1 - Ghi nhận qua Telegram Bot:</p>
                            <p>Gõ cú pháp 4 cơ thủ (2 người Đội 1 và 2 người Đội 2):</p>
                            <div class="bg-gray-100 border border-gray-200 rounded-lg p-2.5 font-mono text-xs text-gray-900 font-bold mt-1">
                                /user1 user2 vs user3 user4 &nbsp;<span class="text-gray-400 font-normal">(hoặc /doi u1 u2 u3 u4)</span>
                            </div>
                            <p class="mt-1"><em>Ví dụ:</em> Đội (Admin & Bình) đấu với Đội (Cường & Dũng), gõ:</p>
                            <code class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-md font-mono font-bold block text-xs mt-0.5">/admin binh vs cuong dung</code>
                            <p class="mt-1">Bot sẽ hiện 2 nút: <span class="font-semibold text-gray-800">[🏆 Đội 1 Thắng]</span> hoặc <span class="font-semibold text-gray-800">[🏆 Đội 2 Thắng]</span>. Chạm vào đội thắng là xong!</p>
                        </div>

                        <div class="pt-1 border-t border-gray-100">
                            <p class="font-semibold text-gray-800 mb-0.5">Cách 2 - Ghi nhận trên Website:</p>
                            <p>Bấm nút <b>+ Ghi nhận trận</b> ở góc trên bên phải, chuyển sang tab <b>Đôi (2vs2)</b>, chọn đồng đội của bạn và 2 đối thủ rồi bấm Lưu trận đấu.</p>
                        </div>
                    </div>
                </div>

                <!-- 4. Xác nhận kết quả tự động trong 24h -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-sm">
                        <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0">3</span>
                        <span>Xác nhận kết quả tự động trong 24h</span>
                    </div>
                    <div class="pl-7 space-y-1.5 text-xs text-gray-600 leading-relaxed">
                        <p>Sau khi trận đấu được tạo (cả đơn và đôi), Bot tự động gửi tin nhắn đến các cơ thủ đối thủ:</p>
                        <div class="flex items-center gap-2 py-1">
                            <span class="inline-flex items-center px-2.5 py-1 bg-green-50 border border-green-200 text-green-700 rounded-md font-semibold text-xs">
                                ✅ Chấp nhận kết quả
                            </span>
                            <span class="inline-flex items-center px-2.5 py-1 bg-red-50 border border-red-200 text-red-700 rounded-md font-semibold text-xs">
                                ❌ Khiếu nại kết quả
                            </span>
                        </div>
                        <p>Đối thủ chỉ cần bấm "Chấp nhận" ngay trên Telegram là xong. Nếu sau 24h đối thủ không phản hồi, hệ thống sẽ tự động chốt kết quả chính thức.</p>
                    </div>
                </div>

                <!-- 5. Bảng danh sách các lệnh Bot -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-gray-900 font-bold text-sm">
                        <span class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0">4</span>
                        <span>Danh sách tất cả lệnh của Bot</span>
                    </div>
                    <div class="pl-7 overflow-hidden rounded-xl border border-gray-200">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 font-semibold uppercase">
                                <tr>
                                    <th class="py-2 px-3">Lệnh</th>
                                    <th class="py-2 px-3">Ý nghĩa & Chức năng</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/login [user] [pass]</td>
                                    <td class="py-2 px-3 text-gray-600">Đăng nhập tài khoản trực tiếp trên Telegram (VD: /login admin password)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/password [mới]</td>
                                    <td class="py-2 px-3 text-gray-600">Đổi mật khẩu tài khoản trực tiếp trên Telegram (VD: /password 123456)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/logout</td>
                                    <td class="py-2 px-3 text-gray-600">Đăng xuất tài khoản khỏi Telegram Bot</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/vs [đối_thủ]</td>
                                    <td class="py-2 px-3 text-gray-600">Tạo nhanh trận đơn giữa bạn và đối thủ (VD: /vs binh)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/u1 u2</td>
                                    <td class="py-2 px-3 text-gray-600">Tạo trận đơn chỉ định 2 cơ thủ (VD: /admin binh)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/u1 u2 vs u3 u4</td>
                                    <td class="py-2 px-3 text-gray-600">Tạo trận đôi 2vs2 (VD: /admin binh vs cuong dung)</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/start [mã]</td>
                                    <td class="py-2 px-3 text-gray-600">Kết nối tài khoản website với Bot</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/help</td>
                                    <td class="py-2 px-3 text-gray-600">Xem hướng dẫn sử dụng và cú pháp ghi trận</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/stats</td>
                                    <td class="py-2 px-3 text-gray-600">Xem thống kê cá nhân, tỷ lệ thắng, chuỗi trận</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/rank</td>
                                    <td class="py-2 px-3 text-gray-600">Bảng xếp hạng Top cơ thủ và Loser của tháng</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/pending</td>
                                    <td class="py-2 px-3 text-gray-600">Danh sách các trận đấu đang chờ bạn xác nhận</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/history</td>
                                    <td class="py-2 px-3 text-gray-600">Xem 5 trận đấu gần đây nhất của bạn</td>
                                </tr>
                                <tr>
                                    <td class="py-2 px-3 font-mono font-bold text-blue-700">/duel [user]</td>
                                    <td class="py-2 px-3 text-gray-600">Xem lịch sử và tỷ lệ đối đầu trực tiếp (VD: /duel an)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">Telegram Bot Bida Nội Bộ</span>
                <button 
                    type="button" 
                    onclick="closeTelegramHelpModal()" 
                    class="py-2 px-5 bg-primary hover:bg-primary-hover text-white font-medium rounded-lg text-xs sm:text-sm shadow-xs transition cursor-pointer"
                >
                    Đã hiểu
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    function openTelegramHelpModal() {
        var modal = document.getElementById('telegramHelpModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeTelegramHelpModal() {
        var modal = document.getElementById('telegramHelpModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTelegramHelpModal();
        }
    });
</script>
