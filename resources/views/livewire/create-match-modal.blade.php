<div>
    @if($isOpen)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true"
            @keydown.escape.window="$wire.closeModal()">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 transition-opacity cursor-pointer" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200">
                    
                    <!-- Header -->
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900" id="modal-title">Ghi Nhận Trận Đấu Bida</h3>
                        <button type="button" wire:click="closeModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-md cursor-pointer">
                            <span class="text-2xl leading-none">&times;</span>
                        </button>
                    </div>

                    <!-- Form Body -->
                    <form wire:submit="save" class="p-6 space-y-4 text-sm">
                        @if($errors->has('submit'))
                            <div class="p-3 rounded-lg bg-red-50 text-red-700 text-sm font-medium border border-red-200">
                                {{ $errors->first('submit') }}
                            </div>
                        @endif

                        <!-- Chế độ chơi: Đơn / Đôi -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Hình thức thi đấu <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button 
                                    type="button" 
                                    wire:click="setMatchType('singles')" 
                                    class="py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer {{ $matchType === 'singles' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' }}"
                                >
                                    Đánh Đơn (1 vs 1)
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="setMatchType('doubles')" 
                                    class="py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer {{ $matchType === 'doubles' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' }}"
                                >
                                    Đánh Đôi (2 vs 2)
                                </button>
                            </div>
                        </div>

                        @if($matchType === 'singles')
                            <!-- Step 1: Chọn đối thủ đơn -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                    1. Đối thủ <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="opponentId" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">-- Chọn thành viên đối thủ --</option>
                                    @foreach($opponents as $op)
                                        <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                    @endforeach
                                </select>
                                @error('opponentId') <span class="text-red-600 text-xs sm:text-sm mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Step 2: Ai thắng? -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                    2. Kết quả: Ai là người thắng? <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base {{ $winnerChoice === 'me' ? 'bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                        <input type="radio" wire:model="winnerChoice" value="me" class="sr-only">
                                        <span>Tôi Thắng</span>
                                    </label>
                                    <label class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base {{ $winnerChoice === 'opponent' ? 'bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                        <input type="radio" wire:model="winnerChoice" value="opponent" class="sr-only">
                                        <span>Đối Thủ Thắng</span>
                                    </label>
                                </div>
                            </div>
                        @else
                            <!-- Đánh Đôi: Đội của bạn -->
                            <div class="space-y-3 p-3.5 rounded-lg bg-gray-50 border border-gray-200">
                                <label class="block text-sm font-semibold text-gray-800 uppercase tracking-wider">
                                    1. Đội của bạn (Team 1) <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1 font-medium">Người 1 (Bạn)</label>
                                        <input 
                                            type="text" 
                                            value="{{ auth()->user()->name }} (Tôi)" 
                                            disabled 
                                            class="w-full rounded-lg border border-gray-300 bg-gray-200/70 px-3 py-2 text-xs sm:text-sm text-gray-700 font-semibold cursor-not-allowed"
                                        >
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1 font-medium">Người 2 (Đồng đội)</label>
                                        <select wire:model.live="teammateId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="">-- Chọn đồng đội --</option>
                                            @foreach($opponents as $op)
                                                <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @error('teammateId') <span class="text-red-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Đánh Đôi: Đội đối thủ -->
                            <div class="space-y-3 p-3.5 rounded-lg bg-gray-50 border border-gray-200">
                                <label class="block text-sm font-semibold text-gray-800 uppercase tracking-wider">
                                    2. Đội đối thủ (Team 2) <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1 font-medium">Đối thủ 1</label>
                                        <select wire:model.live="opponentId" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="">-- Chọn đối thủ 1 --</option>
                                            @foreach($opponents as $op)
                                                <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1 font-medium">Đối thủ 2</label>
                                        <select wire:model.live="opponent2Id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                            <option value="">-- Chọn đối thủ 2 --</option>
                                            @foreach($opponents as $op)
                                                <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                @error('opponentId') <span class="text-red-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                                @error('opponent2Id') <span class="text-red-600 text-xs mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Đánh Đôi: Kết quả -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                    3. Kết quả: Đội nào thắng? <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base {{ $winnerChoice === 'me' ? 'bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                        <input type="radio" wire:model="winnerChoice" value="me" class="sr-only">
                                        <span>Đội Tôi Thắng</span>
                                    </label>
                                    <label class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base {{ $winnerChoice === 'opponent' ? 'bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50' }}">
                                        <input type="radio" wire:model="winnerChoice" value="opponent" class="sr-only">
                                        <span>Đội Đối Thủ Thắng</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <!-- Kèo / Phần thưởng -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Kèo / Phần thưởng <span class="text-red-500">*</span>
                            </label>
                            <!-- Quick Chips (No emojis) -->
                            <div class="flex flex-wrap gap-1.5 mb-2.5">
                                @foreach($quickPrizes as $qp)
                                    <button 
                                        type="button" 
                                        wire:click="selectPrize('{{ $qp }}')" 
                                        class="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium border transition cursor-pointer {{ $prize === $qp ? 'bg-primary text-white border-primary font-semibold shadow-sm' : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' }}"
                                    >
                                        {{ $qp }}
                                    </button>
                                @endforeach
                            </div>
                            <input 
                                type="text" 
                                wire:model="prize" 
                                placeholder="VD: Kèo cà phê muối, Kèo Rockstar..." 
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                            >
                            @error('prize') <span class="text-red-600 text-xs sm:text-sm mt-1 block font-medium">{{ $message }}</span> @enderror
                        </div>

                        <!-- Ghi chú & Ngày giờ -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Thời gian diễn ra
                                </label>
                                <input 
                                    type="datetime-local" 
                                    wire:model="playedAtDate" 
                                    class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                    Bằng chứng ảnh (nếu có)
                                </label>
                                <input 
                                    type="file" 
                                    wire:model="evidence" 
                                    accept="image/*"
                                    class="w-full text-xs sm:text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs sm:file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Ghi chú
                            </label>
                            <textarea 
                                wire:model="notes" 
                                rows="2" 
                                placeholder="VD: Đánh 3 ván thắng 2..." 
                                class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                            ></textarea>
                        </div>

                        <!-- Reminder -->
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3.5 rounded-r-lg text-sm text-blue-900">
                            Sau khi tạo, đối thủ có <b>24 giờ</b> để xác nhận hoặc khiếu nại. Quá hạn hệ thống sẽ <b>tự động xác nhận</b>.
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                            <button 
                                type="button" 
                                wire:click="closeModal" 
                                class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                            >
                                Hủy
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-sm font-semibold shadow-sm transition cursor-pointer"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove>Lưu trận đấu</span>
                                <span wire:loading>Đang lưu...</span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endif
</div>
