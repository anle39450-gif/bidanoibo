<div wire:poll.5s>
    <!-- ========================================== -->
    <!-- 1. FULLSCREEN SOS RED FLASHING EMERGENCY MODAL -->
    <!-- ========================================== -->
    @if($incomingChallenge)
        <div id="sosEmergencyOverlay" class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            style="animation: sos-background-pulse 1s infinite alternate;"
            x-data="{
                playSiren() {
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
            }"
            x-init="playSiren(); setInterval(() => playSiren(), 3000)">

            <!-- Style sheet for the emergency SOS flashing effect -->
            <style>
                @keyframes sos-background-pulse {
                    0% {
                        background-color: rgba(185, 28, 28, 0.94);
                        box-shadow: inset 0 0 120px rgba(255, 0, 0, 0.9);
                    }
                    100% {
                        background-color: rgba(80, 7, 7, 0.97);
                        box-shadow: inset 0 0 40px rgba(180, 0, 0, 0.6);
                    }
                }

                @keyframes sos-border-flash {
                    0%, 100% {
                        border-color: #ef4444;
                        box-shadow: 0 0 50px rgba(239, 68, 68, 0.9), 0 0 100px rgba(220, 38, 38, 0.6);
                    }
                    50% {
                        border-color: #facc15;
                        box-shadow: 0 0 60px rgba(250, 204, 21, 0.9), 0 0 120px rgba(234, 179, 8, 0.6);
                    }
                }

                @keyframes sos-text-strobe {
                    0%, 100% { color: #fef08a; transform: scale(1); }
                    50% { color: #ffffff; transform: scale(1.03); }
                }

                .animate-sos-border {
                    animation: sos-border-flash 0.8s infinite alternate;
                }

                .animate-sos-text {
                    animation: sos-text-strobe 0.8s infinite alternate;
                }
            </style>

            <!-- Card Box with flashing border -->
            <div class="relative w-full max-w-lg bg-gray-950 text-white rounded-2xl border-4 animate-sos-border p-6 sm:p-8 shadow-2xl text-center space-y-5 overflow-hidden">
                
                <!-- Red Alert Siren Header -->
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-600/40 border border-red-500 text-xs sm:text-sm font-extrabold tracking-widest uppercase animate-sos-text">
                        <span>🚨 BÁO ĐỘNG SOS: LỜI KHIÊU CHIẾN KHẨN CẤP! 🚨</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black text-white tracking-wide uppercase">
                        BẠN ĐANG BỊ THÁCH ĐẤU!
                    </h2>
                </div>

                <!-- Challenger Profile Highlight -->
                <div class="bg-red-950/70 border border-red-800/80 rounded-xl p-4 flex flex-col sm:flex-row items-center gap-4 text-left">
                    <div class="relative shrink-0">
                        <img src="{{ $incomingChallenge->challenger->avatar_url }}" alt="{{ $incomingChallenge->challenger->name }}"
                            class="w-16 h-16 rounded-full border-2 border-yellow-400 object-cover shadow-lg">
                        <span class="absolute -bottom-1 -right-1 bg-red-600 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full uppercase border border-white">
                            VS
                        </span>
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <div class="text-xs uppercase text-red-300 font-bold tracking-wider">Kẻ thách đấu:</div>
                        <div class="text-lg font-black text-yellow-300">{{ $incomingChallenge->challenger->name }}</div>
                        <div class="text-xs text-gray-300 font-mono">@<span>{{ $incomingChallenge->challenger->username }}</span></div>
                    </div>
                </div>

                <!-- Challenge Prize & Taunt Message -->
                <div class="bg-black/80 rounded-xl p-4 border border-gray-800 space-y-2.5 text-left">
                    <div>
                        <div class="text-xs uppercase text-gray-400 font-bold">Món kèo thách thức:</div>
                        <div class="text-base font-extrabold text-emerald-400 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                            </svg>
                            <span>{{ $incomingChallenge->prize }}</span>
                        </div>
                    </div>
                    @if($incomingChallenge->message)
                        <div class="pt-1 border-t border-gray-800/80">
                            <div class="text-xs uppercase text-gray-400 font-bold">Lời khiêu chiến:</div>
                            <div class="text-sm italic text-yellow-100 font-medium">"{{ $incomingChallenge->message }}"</div>
                        </div>
                    @endif
                </div>

                <!-- 2 Big Action Buttons -->
                <div class="pt-2 flex flex-col sm:flex-row gap-3">
                    <button type="button" wire:click="acceptChallenge({{ $incomingChallenge->id }})"
                        class="flex-1 bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-500 hover:to-green-500 text-white font-black py-3.5 px-4 rounded-xl shadow-lg transition transform hover:scale-[1.03] active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 text-sm sm:text-base border border-emerald-400">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>TIẾP CHIÊU (ĐỒNG Ý CHIẾN)</span>
                    </button>

                    <button type="button" wire:click="declineChallenge({{ $incomingChallenge->id }})"
                        class="bg-gray-800 hover:bg-gray-700 text-gray-300 font-bold py-3.5 px-5 rounded-xl transition cursor-pointer text-xs sm:text-sm border border-gray-700">
                        🏳️ SỢ QUÁ TỪ CHỐI
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- ========================================== -->
    <!-- 2. SEND CHALLENGE MODAL (GỬI LỜI KHIÊU CHIẾN) -->
    <!-- ========================================== -->
    @if($showSendModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/70 backdrop-blur-xs transition-opacity cursor-pointer" wire:click="closeSendModal"></div>

            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden text-left z-10">
                <!-- Header -->
                <div class="bg-gradient-to-r from-red-600 to-rose-700 text-white px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⚔️</span>
                        <h3 class="text-base sm:text-lg font-black tracking-wide">PHÁT LỆNH KHIÊU CHIẾN</h3>
                    </div>
                    <button type="button" wire:click="closeSendModal" class="text-white/80 hover:text-white p-1 rounded-md cursor-pointer text-2xl leading-none">&times;</button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="sendChallenge" class="p-5 space-y-4 text-sm">
                    <!-- Chọn đối thủ -->
                    <div>
                        <label class="block font-bold text-gray-800 mb-1.5 uppercase text-xs tracking-wider">
                            Chọn cơ thủ khiêu chiến <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.defer="selectedOpponentId"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-medium">
                            <option value="">-- Chọn đối thủ muốn thách đấu --</option>
                            @foreach($usersList as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} (@<span>{{ $u->username }}</span>)</option>
                            @endforeach
                        </select>
                        @error('selectedOpponentId')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Món kèo cược -->
                    <div>
                        <label class="block font-bold text-gray-800 mb-1.5 uppercase text-xs tracking-wider">
                            Món kèo thách thức <span class="text-red-500">*</span>
                        </label>
                        <input type="text" wire:model.defer="challengePrize"
                            placeholder="Ví dụ: 1 ly cà phê muối, 1 tô phở, bao tiền bàn..."
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 font-medium">
                        @error('challengePrize')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror

                        <!-- Quick prizes -->
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @foreach($quickPrizes as $qp)
                                <button type="button" wire:click="$set('challengePrize', '{{ $qp }}')"
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
                        <textarea wire:model.defer="challengeMessage" rows="2"
                            placeholder="Ví dụ: Ra bàn solo ngay nếu không muốn bị coi là sợ!"
                            class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"></textarea>
                        @error('challengeMessage')
                            <p class="text-xs text-red-600 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-800 leading-relaxed">
                        <b>Hiệu ứng SOS:</b> Ngay khi bạn bấm gửi, màn hình của đối thủ sẽ nhấp nháy <b>đèn đỏ SOS báo động</b> và bot Telegram sẽ gửi tối hậu thư bắt đối thủ phải chọn Tiếp Chiêu hoặc Từ Chối!
                    </div>

                    <!-- Submit Buttons -->
                    <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-gray-100">
                        <button type="button" wire:click="closeSendModal"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 font-semibold text-xs sm:text-sm cursor-pointer">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white font-extrabold px-5 py-2.5 rounded-lg text-xs sm:text-sm shadow-sm transition cursor-pointer flex items-center gap-1.5">
                            <span>⚔️ Phát Lệnh Khiêu Chiến!</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
