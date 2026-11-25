@auth
@php
    $challengeOpponents = \App\Models\User::where('is_active', true)
        ->where('id', '!=', auth()->id())
        ->orderBy('name')
        ->get();
@endphp

<!-- ============================================================== -->
<!-- 1. FULLSCREEN SOS RED FLASHING EMERGENCY MODAL (PURE AJAX) -->
<!-- ============================================================== -->
<div id="ajaxSosEmergencyOverlay" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4 sm:p-6 overflow-y-auto"
    style="animation: ajax-sos-background-pulse 1s infinite alternate;">

    <!-- Styles for emergency SOS flashing red strobe and hazard borders -->
    <style>
        @keyframes ajax-sos-background-pulse {
            0% {
                background-color: rgba(185, 28, 28, 0.95);
                box-shadow: inset 0 0 140px rgba(255, 0, 0, 0.95);
            }
            100% {
                background-color: rgba(75, 5, 5, 0.97);
                box-shadow: inset 0 0 40px rgba(180, 0, 0, 0.6);
            }
        }

        @keyframes ajax-sos-border-flash {
            0%, 100% {
                border-color: #ef4444;
                box-shadow: 0 0 50px rgba(239, 68, 68, 0.9), 0 0 100px rgba(220, 38, 38, 0.6);
            }
            50% {
                border-color: #facc15;
                box-shadow: 0 0 60px rgba(250, 204, 21, 0.9), 0 0 120px rgba(234, 179, 8, 0.6);
            }
        }

        @keyframes ajax-sos-text-strobe {
            0%, 100% { color: #fef08a; transform: scale(1); }
            50% { color: #ffffff; transform: scale(1.02); }
        }

        .animate-ajax-sos-border {
            animation: ajax-sos-border-flash 0.8s infinite alternate;
        }

        .animate-ajax-sos-text {
            animation: ajax-sos-text-strobe 0.8s infinite alternate;
        }
    </style>

    <!-- Card Box with flashing border -->
    <div class="relative w-full max-w-lg bg-gray-950 text-white rounded-2xl border-4 animate-ajax-sos-border p-6 sm:p-8 shadow-2xl text-center space-y-5 overflow-hidden">
        
        <!-- Red Alert Siren Header -->
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-600/40 border border-red-500 text-xs sm:text-sm font-extrabold tracking-widest uppercase animate-ajax-sos-text">
                <span>🚨 BÁO ĐỘNG SOS: CÓ LỜI KHIÊU CHIẾN KHẨN CẤP! 🚨</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-white tracking-wide uppercase">
                BẠN ĐANG BỊ THÁCH ĐẤU!
            </h2>
        </div>

        <!-- Challenger Profile Highlight -->
        <div class="bg-red-950/70 border border-red-800/80 rounded-xl p-4 flex flex-col sm:flex-row items-center gap-4 text-left">
            <div class="relative shrink-0">
                <img id="sos_challenger_avatar" src="" alt="Challenger"
                    class="w-16 h-16 rounded-full border-2 border-yellow-400 object-cover shadow-lg">
                <span class="absolute -bottom-1 -right-1 bg-red-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full uppercase border border-white">
                    VS
                </span>
            </div>
            <div class="flex-1 text-center sm:text-left">
                <div class="text-xs uppercase text-red-300 font-bold tracking-wider">Kẻ thách đấu:</div>
                <div id="sos_challenger_name" class="text-lg font-black text-yellow-300"></div>
                <div id="sos_challenger_username" class="text-xs text-gray-300 font-mono"></div>
            </div>
        </div>

        <!-- Challenge Prize & Taunt Message -->
        <div class="bg-black/80 rounded-xl p-4 border border-gray-800 space-y-2.5 text-left">
            <div>
                <div class="text-xs uppercase text-gray-400 font-bold">Món kèo thách thức:</div>
                <div id="sos_challenge_prize" class="text-base font-extrabold text-emerald-400 flex items-center gap-1.5">
                </div>
            </div>
            <div id="sos_challenge_message_box" class="pt-1 border-t border-gray-800/80">
                <div class="text-xs uppercase text-gray-400 font-bold">Lời khiêu chiến:</div>
                <div id="sos_challenge_message" class="text-sm italic text-yellow-100 font-medium"></div>
            </div>
        </div>

        <!-- 2 Big Action Buttons -->
        <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <button type="button" id="sos_btn_accept" onclick="handleAjaxAcceptChallenge()"
                class="flex-1 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white font-black py-3.5 px-4 rounded-xl shadow-lg transition transform hover:scale-[1.03] active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 text-sm sm:text-base border border-emerald-400">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>TIẾP CHIÊU (ĐỒNG Ý CHIẾN)</span>
            </button>

            <button type="button" id="sos_btn_decline" onclick="handleAjaxDeclineChallenge()"
                class="bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold py-3.5 px-5 rounded-xl transition cursor-pointer text-xs sm:text-sm border border-gray-700">
                🏳️ SỢ QUÁ TỪ CHỐI
            </button>
        </div>

    </div>
</div>

<!-- ============================================================== -->
<!-- 2. SEND CHALLENGE MODAL (GỬI LỜI KHIÊU CHIẾN - PURE AJAX) -->
<!-- ============================================================== -->
<div id="ajaxSendChallengeModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/70 backdrop-blur-xs transition-opacity cursor-pointer" onclick="closeSendChallengeModal()"></div>

    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden text-left z-10">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-rose-700 text-white px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xl">⚔️</span>
                <h3 class="text-base sm:text-lg font-black tracking-wide">PHÁT LỆNH KHIÊU CHIẾN</h3>
            </div>
            <button type="button" onclick="closeSendChallengeModal()" class="text-white/80 hover:text-white p-1 rounded-md cursor-pointer text-2xl leading-none">&times;</button>
        </div>

        <!-- Form -->
        <form id="ajaxSendChallengeForm" onsubmit="handleAjaxSendChallengeSubmit(event)" class="p-5 space-y-4 text-sm">
            @csrf

            <!-- Alert Box -->
            <div id="challenge_form_alert" class="hidden p-3 rounded-lg text-xs font-semibold"></div>

            <!-- Chọn đối thủ -->
            <div>
                <label class="block font-bold text-gray-800 mb-1.5 uppercase text-xs tracking-wider">
                    Chọn cơ thủ khiêu chiến <span class="text-red-500">*</span>
                </label>
                <select name="opponent_id" id="challenge_opponent_id" required
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-medium">
                    <option value="">-- Chọn đối thủ muốn thách đấu --</option>
                    @foreach($challengeOpponents as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} (@<span>{{ $u->username }}</span>)</option>
                    @endforeach
                </select>
            </div>

            <!-- Món kèo cược -->
            <div>
                <label class="block font-bold text-gray-800 mb-1.5 uppercase text-xs tracking-wider">
                    Món kèo thách thức <span class="text-red-500">*</span>
                </label>
                <input type="text" name="prize" id="challenge_prize" required
                    value="Kèo cà phê muối"
                    placeholder="Ví dụ: 1 ly cà phê muối, 1 tô phở, bao tiền bàn..."
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-medium">

                <!-- Quick prizes -->
                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach(['Kèo cà phê muối', 'Kèo Rockstar', 'Kèo trà sữa', 'Kèo bữa trưa', 'Kèo tiền bàn', 'Kèo bia hơi'] as $qp)
                        <button type="button" onclick="document.getElementById('challenge_prize').value = '{{ $qp }}'"
                            class="text-xs px-2.5 py-1 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium transition cursor-pointer">
                            {{ $qp }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Lời thách đấu / Taunt -->
            <div>
                <label class="block font-bold text-gray-800 mb-1.5 uppercase text-xs tracking-wider">
                    Lời thách đấu (Taunt)
                </label>
                <textarea name="message" id="challenge_message" rows="2"
                    placeholder="Ví dụ: Ra bàn solo ngay nếu không muốn bị coi là sợ!"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">Ra bàn solo ngay nếu không muốn bị coi là sợ!</textarea>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 leading-relaxed">
                <b>Hiệu ứng SOS:</b> Khi bạn bấm gửi, màn hình đối thủ sẽ lập tức nhấp nháy <b>đèn đỏ SOS báo động</b> và bot Telegram sẽ gửi tối hậu thư bắt buộc đối thủ phải Tiếp Chiêu hoặc Từ Chối!
            </div>

            <!-- Submit Buttons -->
            <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-gray-100">
                <button type="button" onclick="closeSendChallengeModal()"
                    class="px-4 py-2 text-gray-600 hover:text-gray-800 font-semibold text-xs sm:text-sm cursor-pointer">
                    Hủy bỏ
                </button>
                <button type="submit" id="btn_submit_challenge"
                    class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-extrabold px-5 py-2.5 rounded-lg text-xs sm:text-sm shadow-sm transition cursor-pointer flex items-center gap-1.5">
                    <span>⚔️ Phát Lệnh Khiêu Chiến!</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- 3. JAVASCRIPT CONTROLLER FOR PURE AJAX DUEL CHALLENGE -->
<!-- ============================================================== -->
<script>
    let activeIncomingChallengeId = null;
    let sirenAudioInterval = null;

    // Siren Sound synthesizer via Web Audio API (No external sound files required)
    function playEmergencySirenSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(500, ctx.currentTime);
            osc.frequency.linearRampToValueAtTime(950, ctx.currentTime + 0.35);
            osc.frequency.linearRampToValueAtTime(500, ctx.currentTime + 0.7);
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0.01, ctx.currentTime + 0.9);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.9);
        } catch(e) {}
    }

    // Modal Send Challenge controls
    function openSendChallengeModal(opponentId = null) {
        const modal = document.getElementById('ajaxSendChallengeModal');
        if (!modal) return;
        if (opponentId) {
            document.getElementById('challenge_opponent_id').value = opponentId;
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSendChallengeModal() {
        const modal = document.getElementById('ajaxSendChallengeModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        const alertBox = document.getElementById('challenge_form_alert');
        if (alertBox) alertBox.classList.add('hidden');
    }

    // Submit Send Challenge via AJAX
    async function handleAjaxSendChallengeSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btn_submit_challenge');
        const alertBox = document.getElementById('challenge_form_alert');

        btn.disabled = true;
        btn.innerHTML = '<span>Đang phát lệnh...</span>';
        alertBox.classList.add('hidden');

        const formData = new FormData(form);

        try {
            const res = await fetch('{{ route("challenges.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();

            if (res.ok && data.success) {
                closeSendChallengeModal();
                alert(data.message);
            } else {
                alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-red-50 text-red-700 border border-red-200 block';
                alertBox.textContent = data.message || 'Có lỗi xảy ra, vui lòng thử lại!';
            }
        } catch (err) {
            alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-red-50 text-red-700 border border-red-200 block';
            alertBox.textContent = 'Không thể kết nối đến máy chủ!';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>⚔️ Phát Lệnh Khiêu Chiến!</span>';
        }
    }

    // Show SOS Red Emergency Overlay
    function showSosEmergencyAlert(challenge) {
        activeIncomingChallengeId = challenge.id;
        const overlay = document.getElementById('ajaxSosEmergencyOverlay');
        if (!overlay) return;

        document.getElementById('sos_challenger_avatar').src = challenge.challenger_avatar;
        document.getElementById('sos_challenger_name').textContent = challenge.challenger_name;
        document.getElementById('sos_challenger_username').textContent = '@' + challenge.challenger_username;
        document.getElementById('sos_challenge_prize').innerHTML = '<span>' + challenge.prize + '</span>';
        
        const msgBox = document.getElementById('sos_challenge_message_box');
        if (challenge.message) {
            msgBox.classList.remove('hidden');
            document.getElementById('sos_challenge_message').textContent = '"' + challenge.message + '"';
        } else {
            msgBox.classList.add('hidden');
        }

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        // Play siren sound immediately and repeat every 3s
        playEmergencySirenSound();
        if (!sirenAudioInterval) {
            sirenAudioInterval = setInterval(playEmergencySirenSound, 3000);
        }
    }

    function hideSosEmergencyAlert() {
        activeIncomingChallengeId = null;
        const overlay = document.getElementById('ajaxSosEmergencyOverlay');
        if (overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }
        if (sirenAudioInterval) {
            clearInterval(sirenAudioInterval);
            sirenAudioInterval = null;
        }
    }

    // Accept Challenge via AJAX
    async function handleAjaxAcceptChallenge() {
        if (!activeIncomingChallengeId) return;
        const btn = document.getElementById('sos_btn_accept');
        btn.disabled = true;
        btn.textContent = 'ĐANG TIẾP CHIÊU...';

        try {
            const res = await fetch('/challenges/' + activeIncomingChallengeId + '/accept', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                hideSosEmergencyAlert();
                alert(data.message);
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } else {
                alert(data.message || 'Lỗi khi chấp nhận thách đấu!');
                hideSosEmergencyAlert();
            }
        } catch (err) {
            hideSosEmergencyAlert();
        }
    }

    // Decline Challenge via AJAX
    async function handleAjaxDeclineChallenge() {
        if (!activeIncomingChallengeId) return;
        const btn = document.getElementById('sos_btn_decline');
        btn.disabled = true;
        btn.textContent = 'ĐANG TỪ CHỐI...';

        try {
            const res = await fetch('/challenges/' + activeIncomingChallengeId + '/decline', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            hideSosEmergencyAlert();
            alert(data.message || 'Đã từ chối khiêu chiến.');
        } catch (err) {
            hideSosEmergencyAlert();
        }
    }

    // Lightweight Background Polling via AJAX (every 5 seconds)
    async function checkPendingChallenges() {
        try {
            const res = await fetch('{{ route("challenges.check") }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                const data = await res.json();
                if (data.has_challenge && data.challenge) {
                    if (activeIncomingChallengeId !== data.challenge.id) {
                        showSosEmergencyAlert(data.challenge);
                    }
                } else {
                    if (activeIncomingChallengeId) {
                        hideSosEmergencyAlert();
                    }
                }
            }
        } catch (e) {}
    }

    // Start polling when document is ready
    document.addEventListener('DOMContentLoaded', function () {
        checkPendingChallenges();
        setInterval(checkPendingChallenges, 5000);
    });
</script>
@endauth
