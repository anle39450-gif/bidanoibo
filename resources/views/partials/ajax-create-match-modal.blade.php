@auth
@php
    $modalUsers = \App\Models\User::where('is_active', true)
        ->where('id', '!=', auth()->id())
        ->orderBy('name')
        ->get();
@endphp

<div id="ajaxCreateMatchModal" class="fixed inset-0 z-[100] overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/60 transition-opacity cursor-pointer" onclick="closeCreateMatchModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200">
            
            <!-- Header -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900" id="modal-title">Ghi Nhận Trận Đấu Bida</h3>
                <button type="button" onclick="closeCreateMatchModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-md cursor-pointer">
                    <span class="text-2xl leading-none">&times;</span>
                </button>
            </div>

            <!-- Form Body -->
            <form id="ajaxCreateMatchForm" onsubmit="handleAjaxCreateMatchSubmit(event)" class="p-6 space-y-4 text-sm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="match_type" id="ajax_match_type" value="singles">
                <input type="hidden" name="winner_choice" id="ajax_winner_choice" value="me">

                <!-- Alert error box -->
                <div id="ajax_form_alert" class="hidden p-3 rounded-lg bg-red-50 text-red-700 text-sm font-medium border border-red-200"></div>

                <!-- Chế độ thi đấu -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Hình thức thi đấu <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            type="button" 
                            id="btn_mode_singles"
                            onclick="setAjaxMatchType('singles')" 
                            class="py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-blue-600 text-white border-blue-600 shadow-sm"
                        >
                            Đánh Đơn (1 vs 1)
                        </button>
                        <button 
                            type="button" 
                            id="btn_mode_doubles"
                            onclick="setAjaxMatchType('doubles')" 
                            class="py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200"
                        >
                            Đánh Đôi (2 vs 2)
                        </button>
                    </div>
                </div>

                <!-- PHẦN ĐÁNH ĐƠN (Singles) -->
                <div id="section_singles" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            1. Đối thủ <span class="text-red-500">*</span>
                        </label>
                        <select name="opponent_id" id="ajax_opponent_id" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Chọn thành viên đối thủ --</option>
                            @foreach($modalUsers as $op)
                                <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                            @endforeach
                        </select>
                        <span id="err_opponent_id" class="text-red-600 text-xs sm:text-sm mt-1 block font-medium hidden"></span>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            2. Kết quả: Ai là người thắng? <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                type="button" 
                                id="btn_winner_me"
                                onclick="setAjaxWinnerChoice('me')"
                                class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-blue-50 border-blue-600 text-blue-900 font-bold ring-2 ring-blue-600"
                            >
                                Tôi Thắng
                            </button>
                            <button 
                                type="button" 
                                id="btn_winner_opponent"
                                onclick="setAjaxWinnerChoice('opponent')"
                                class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
                            >
                                Đối Thủ Thắng
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PHẦN ĐÁNH ĐÔI (Doubles) -->
                <div id="section_doubles" class="space-y-4 hidden">
                    <!-- Đội của bạn -->
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
                                <select name="teammate_id" id="ajax_teammate_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Chọn đồng đội --</option>
                                    @foreach($modalUsers as $op)
                                        <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <span id="err_teammate_id" class="text-red-600 text-xs mt-1 block font-medium hidden"></span>
                    </div>

                    <!-- Đội đối thủ -->
                    <div class="space-y-3 p-3.5 rounded-lg bg-gray-50 border border-gray-200">
                        <label class="block text-sm font-semibold text-gray-800 uppercase tracking-wider">
                            2. Đội đối thủ (Team 2) <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1 font-medium">Đối thủ 1</label>
                                <select name="opponent_id_doubles" id="ajax_doubles_opponent_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Chọn đối thủ 1 --</option>
                                    @foreach($modalUsers as $op)
                                        <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1 font-medium">Đối thủ 2</label>
                                <select name="opponent2_id" id="ajax_opponent2_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Chọn đối thủ 2 --</option>
                                    @foreach($modalUsers as $op)
                                        <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <span id="err_opponent2_id" class="text-red-600 text-xs mt-1 block font-medium hidden"></span>
                    </div>

                    <!-- Kết quả đánh đôi -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            3. Kết quả: Đội nào thắng? <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                type="button" 
                                id="btn_winner_team_me"
                                onclick="setAjaxWinnerChoice('me')"
                                class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-blue-50 border-blue-600 text-blue-900 font-bold ring-2 ring-blue-600"
                            >
                                Đội Tôi Thắng
                            </button>
                            <button 
                                type="button" 
                                id="btn_winner_team_opponent"
                                onclick="setAjaxWinnerChoice('opponent')"
                                class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
                            >
                                Đội Đối Thủ Thắng
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Kèo / Phần thưởng -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Kèo / Phần thưởng <span class="text-red-500">*</span>
                    </label>
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        @foreach([
                            'Kèo cà phê muối',
                            'Kèo Rockstar',
                            'Kèo trà sữa',
                            'Kèo ăn uống',
                            'Kèo bao thuốc',
                            'Kèo vui không thưởng'
                        ] as $qp)
                            <button 
                                type="button" 
                                onclick="setAjaxPrize('{{ $qp }}')" 
                                class="ajax-prize-btn px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium border transition cursor-pointer {{ $loop->first ? 'bg-blue-600 text-white border-blue-600 font-semibold shadow-sm' : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' }}"
                                data-prize="{{ $qp }}"
                            >
                                {{ $qp }}
                            </button>
                        @endforeach
                    </div>
                    <input 
                        type="text" 
                        name="prize" 
                        id="ajax_prize_input" 
                        value="Kèo cà phê muối"
                        placeholder="VD: Kèo cà phê muối, Kèo Rockstar..." 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <span id="err_prize" class="text-red-600 text-xs sm:text-sm mt-1 block font-medium hidden"></span>
                </div>

                <!-- Thời gian & Bằng chứng -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Thời gian diễn ra
                        </label>
                        <input 
                            type="datetime-local" 
                            name="played_at" 
                            id="ajax_played_at" 
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Bằng chứng ảnh (nếu có)
                        </label>
                        <input 
                            type="file" 
                            name="evidence" 
                            id="ajax_evidence" 
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
                        name="notes" 
                        id="ajax_notes" 
                        rows="2" 
                        placeholder="VD: Đánh 3 ván thắng 2..." 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                        onclick="closeCreateMatchModal()" 
                        class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Hủy
                    </button>
                    <button 
                        type="submit"
                        id="ajax_submit_btn"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition cursor-pointer flex items-center gap-2"
                    >
                        <span id="ajax_submit_btn_text">Lưu trận đấu</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    function openCreateMatchModal(opponentId = null) {
        const modal = document.getElementById('ajaxCreateMatchModal');
        if (!modal) return;

        // Reset errors
        clearAjaxErrors();

        // Default Played At
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const dateInput = document.getElementById('ajax_played_at');
        if (dateInput) {
            dateInput.value = now.toISOString().slice(0, 16);
        }

        // Set opponent if provided
        if (opponentId) {
            const oppSelect = document.getElementById('ajax_opponent_id');
            if (oppSelect) oppSelect.value = opponentId;
            const doublesOppSelect = document.getElementById('ajax_doubles_opponent_id');
            if (doublesOppSelect) doublesOppSelect.value = opponentId;
        }

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeCreateMatchModal() {
        const modal = document.getElementById('ajaxCreateMatchModal');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Replace old trigger function so all buttons work instantly
    window.triggerCreateMatchModal = function(opponentId = null) {
        openCreateMatchModal(opponentId);
    };

    function setAjaxMatchType(type) {
        document.getElementById('ajax_match_type').value = type;
        const btnSingles = document.getElementById('btn_mode_singles');
        const btnDoubles = document.getElementById('btn_mode_doubles');
        const secSingles = document.getElementById('section_singles');
        const secDoubles = document.getElementById('section_doubles');

        if (type === 'doubles') {
            btnDoubles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-blue-600 text-white border-blue-600 shadow-sm';
            btnSingles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200';
            secSingles.classList.add('hidden');
            secDoubles.classList.remove('hidden');
        } else {
            btnSingles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-blue-600 text-white border-blue-600 shadow-sm';
            btnDoubles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200';
            secDoubles.classList.add('hidden');
            secSingles.classList.remove('hidden');
        }
        clearAjaxErrors();
    }

    function setAjaxWinnerChoice(choice) {
        document.getElementById('ajax_winner_choice').value = choice;
        const activeClass = 'flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-blue-50 border-blue-600 text-blue-900 font-bold ring-2 ring-blue-600';
        const inactiveClass = 'flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-white border-gray-300 text-gray-700 hover:bg-gray-50';

        // Singles buttons
        const btnMe = document.getElementById('btn_winner_me');
        const btnOp = document.getElementById('btn_winner_opponent');
        if (btnMe && btnOp) {
            btnMe.className = choice === 'me' ? activeClass : inactiveClass;
            btnOp.className = choice === 'opponent' ? activeClass : inactiveClass;
        }

        // Doubles buttons
        const btnTeamMe = document.getElementById('btn_winner_team_me');
        const btnTeamOp = document.getElementById('btn_winner_team_opponent');
        if (btnTeamMe && btnTeamOp) {
            btnTeamMe.className = choice === 'me' ? activeClass : inactiveClass;
            btnTeamOp.className = choice === 'opponent' ? activeClass : inactiveClass;
        }
    }

    function setAjaxPrize(prize) {
        const input = document.getElementById('ajax_prize_input');
        if (input) input.value = prize;

        document.querySelectorAll('.ajax-prize-btn').forEach(btn => {
            if (btn.getAttribute('data-prize') === prize) {
                btn.className = 'ajax-prize-btn px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium border transition cursor-pointer bg-blue-600 text-white border-blue-600 font-semibold shadow-sm';
            } else {
                btn.className = 'ajax-prize-btn px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium border transition cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200';
            }
        });
    }

    function clearAjaxErrors() {
        const alertBox = document.getElementById('ajax_form_alert');
        if (alertBox) {
            alertBox.classList.add('hidden');
            alertBox.innerHTML = '';
        }
        document.querySelectorAll('[id^="err_"]').forEach(el => {
            el.classList.add('hidden');
            el.innerText = '';
        });
    }

    async function handleAjaxCreateMatchSubmit(e) {
        e.preventDefault();
        clearAjaxErrors();

        const form = document.getElementById('ajaxCreateMatchForm');
        const submitBtn = document.getElementById('ajax_submit_btn');
        const submitBtnText = document.getElementById('ajax_submit_btn_text');
        const alertBox = document.getElementById('ajax_form_alert');

        const formData = new FormData(form);
        const matchType = document.getElementById('ajax_match_type').value;

        // If in doubles, use doubles opponent select
        if (matchType === 'doubles') {
            formData.set('opponent_id', document.getElementById('ajax_doubles_opponent_id').value);
        }

        // Disable button & show spinner
        submitBtn.disabled = true;
        submitBtnText.innerText = 'Đang lưu trận đấu...';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || document.querySelector('input[name="_token"]')?.value;

            const response = await fetch('{{ route("matches.store") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Success! Redirect to the newly created match immediately
                window.location.href = data.redirect_url;
            } else {
                // Validation error or business logic error
                submitBtn.disabled = false;
                submitBtnText.innerText = 'Lưu trận đấu';

                if (data.errors) {
                    for (const [key, msgs] of Object.entries(data.errors)) {
                        const errSpan = document.getElementById('err_' + key);
                        if (errSpan) {
                            errSpan.innerText = msgs[0];
                            errSpan.classList.remove('hidden');
                        }
                    }
                    if (data.message && !Object.keys(data.errors).length) {
                        alertBox.innerText = data.message;
                        alertBox.classList.remove('hidden');
                    }
                } else if (data.message) {
                    alertBox.innerText = data.message;
                    alertBox.classList.remove('hidden');
                }
            }
        } catch (err) {
            submitBtn.disabled = false;
            submitBtnText.innerText = 'Lưu trận đấu';
            alertBox.innerText = 'Có lỗi xảy ra khi gửi dữ liệu. Vui lòng thử lại!';
            alertBox.classList.remove('hidden');
        }
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateMatchModal();
        }
    });
</script>
@endauth
