@auth
@php
    $modalUsers = \App\Models\User::where('is_active', true)
        ->where('id', '!=', auth()->id())
        ->orderBy('name')
        ->get();
@endphp

<div id="ajaxCreateMatchModal" class="fixed inset-0 z-100 hidden" aria-labelledby="ajax-create-match-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-950/55 motion-safe:animate-match-backdrop" aria-hidden="true" onclick="closeCreateMatchModal()"></div>

    <div class="pointer-events-none relative flex h-full items-end justify-center p-2 sm:items-center sm:p-6">
        <div class="pointer-events-auto flex max-h-full w-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl motion-safe:animate-match-dialog sm:max-w-lg">
            <!-- Header -->
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-4 py-2.5 sm:px-6">
                <h3 id="ajax-create-match-title" tabindex="-1" class="text-lg font-bold text-gray-900 focus:outline-none">Ghi nhận trận</h3>
                <button type="button" onclick="closeCreateMatchModal()" aria-label="Đóng"
                    class="grid size-9 cursor-pointer place-items-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-primary">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <!-- Form Body -->
            <form id="ajaxCreateMatchForm" onsubmit="handleAjaxCreateMatchSubmit(event)" class="flex min-h-0 flex-1 flex-col text-sm">
                @csrf
                <input type="hidden" name="match_type" id="ajax_match_type" value="singles">
                <input type="hidden" name="winner_choice" id="ajax_winner_choice" value="me">

                <div id="ajax-create-match-fields" class="min-h-0 space-y-3.5 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6">
                    <!-- Alert error box -->
                    <div id="ajax_form_alert" role="alert" class="hidden p-3 rounded-lg bg-red-50 text-red-700 text-sm font-medium border border-red-200"></div>

                    <!-- Chế độ thi đấu -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Thể thức
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                id="btn_mode_singles"
                                onclick="setAjaxMatchType('singles')"
                                class="py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-primary text-white border-primary shadow-sm"
                            >
                                Đánh đơn
                            </button>
                            <button
                                type="button"
                                id="btn_mode_doubles"
                                onclick="setAjaxMatchType('doubles')"
                                class="py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200"
                            >
                                Đánh đôi
                            </button>
                        </div>
                    </div>

                    <!-- PHẦN ĐÁNH ĐƠN (Singles) -->
                    <div id="section_singles" class="space-y-4">
                        <div>
                            <label for="ajax_opponent_id" class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Đối thủ <span class="text-red-500">*</span>
                            </label>
                            <select name="opponent_id" id="ajax_opponent_id" class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Chọn đối thủ</option>
                                @foreach($modalUsers as $op)
                                    <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                @endforeach
                            </select>
                            <span id="err_opponent_id" class="text-red-600 text-xs sm:text-sm mt-1 block font-medium hidden"></span>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Người thắng
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    id="btn_winner_me"
                                    onclick="setAjaxWinnerChoice('me')"
                                    class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary"
                                >
                                    Tôi
                                </button>
                                <button
                                    type="button"
                                    id="btn_winner_opponent"
                                    onclick="setAjaxWinnerChoice('opponent')"
                                    class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
                                >
                                    Đối thủ
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- PHẦN ĐÁNH ĐÔI (Doubles) -->
                    <div id="section_doubles" class="space-y-4 hidden">
                        <!-- Đội của bạn -->
                        <div class="space-y-3 p-3.5 rounded-lg bg-gray-50 border border-gray-200">
                            <label class="block text-sm font-semibold text-gray-800">
                                Đội của bạn
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label for="ajax_current_player" class="block text-xs text-gray-500 mb-1 font-medium">Bạn</label>
                                    <input
                                        type="text"
                                        id="ajax_current_player"
                                        value="{{ auth()->user()->name }}"
                                        disabled
                                        class="w-full rounded-lg border border-gray-300 bg-gray-200/70 px-3 py-2 text-xs sm:text-sm text-gray-700 font-semibold cursor-not-allowed"
                                    >
                                </div>
                                <div>
                                    <label for="ajax_teammate_id" class="block text-xs text-gray-500 mb-1 font-medium">Đồng đội <span class="text-red-500">*</span></label>
                                    <select name="teammate_id" id="ajax_teammate_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Chọn đồng đội</option>
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
                            <label class="block text-sm font-semibold text-gray-800">
                                Đội đối thủ
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div>
                                    <label for="ajax_doubles_opponent_id" class="block text-xs text-gray-500 mb-1 font-medium">Đối thủ 1 <span class="text-red-500">*</span></label>
                                    <select name="opponent_id_doubles" id="ajax_doubles_opponent_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Chọn cơ thủ</option>
                                        @foreach($modalUsers as $op)
                                            <option value="{{ $op->id }}">{{ $op->name }} (@<span>{{ $op->username }}</span>)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="ajax_opponent2_id" class="block text-xs text-gray-500 mb-1 font-medium">Đối thủ 2 <span class="text-red-500">*</span></label>
                                    <select name="opponent2_id" id="ajax_opponent2_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-xs sm:text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                        <option value="">Chọn cơ thủ</option>
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
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                Đội thắng
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    id="btn_winner_team_me"
                                    onclick="setAjaxWinnerChoice('me')"
                                    class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary"
                                >
                                    Đội tôi
                                </button>
                                <button
                                    type="button"
                                    id="btn_winner_team_opponent"
                                    onclick="setAjaxWinnerChoice('opponent')"
                                    class="flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-white border-gray-300 text-gray-700 hover:bg-gray-50"
                                >
                                    Đội đối thủ
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Kèo / Phần thưởng -->
                    <div>
                        <label for="ajax_prize_input" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Kèo <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-1.5 mb-2.5">
                            @foreach([
                                'Cà phê muối' => 'Kèo cà phê muối',
                                'Rockstar' => 'Kèo Rockstar',
                                'Trà sữa' => 'Kèo trà sữa',
                                'Ăn uống' => 'Kèo ăn uống',
                                'Bao thuốc' => 'Kèo bao thuốc',
                                'Không thưởng' => 'Kèo vui không thưởng',
                            ] as $label => $qp)
                                <button
                                    type="button"
                                    onclick="setAjaxPrize('{{ $qp }}')"
                                    class="ajax-prize-btn px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium border transition cursor-pointer {{ $loop->first ? 'bg-primary text-white border-primary font-semibold shadow-sm' : 'bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200' }}"
                                    data-prize="{{ $qp }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        <input
                            type="text"
                            name="prize"
                            id="ajax_prize_input"
                            value="Kèo cà phê muối"
                            placeholder="Nhập kèo"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                        <span id="err_prize" class="text-red-600 text-xs sm:text-sm mt-1 block font-medium hidden"></span>
                    </div>

                    <div>
                        <label for="ajax_played_at" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Ngày thi đấu
                        </label>
                        <input
                            type="date"
                            name="played_at"
                            id="ajax_played_at"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                        >
                    </div>

                    <div>
                        <label for="ajax_notes" class="block text-sm font-semibold text-gray-700 mb-1.5">
                            Ghi chú
                        </label>
                        <textarea
                            name="notes"
                            id="ajax_notes"
                            rows="2"
                            class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"
                        ></textarea>
                    </div>

                </div>

                <!-- Footer -->
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-x-3 gap-y-2 border-t border-gray-100 bg-gray-50 px-4 py-3 sm:px-6">
                    <p class="mr-auto text-xs leading-snug text-gray-600 sm:max-w-55">Tự xác nhận sau <strong>24 giờ</strong> nếu đối thủ không phản hồi.</p>
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
                        class="px-5 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-lg text-sm font-semibold shadow-sm transition cursor-pointer flex items-center gap-2"
                    >
                        <span id="ajax_submit_btn_text">Lưu trận</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    let createMatchReturnFocus = null;
    let createMatchLocksScroll = false;

    function openCreateMatchModal(opponentId = null) {
        const modal = document.getElementById('ajaxCreateMatchModal');
        if (!modal || !modal.classList.contains('hidden')) return;

        // Reset errors
        clearAjaxErrors();

        // Default Played At
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const dateInput = document.getElementById('ajax_played_at');
        if (dateInput) {
            dateInput.value = now.toISOString().slice(0, 10);
        }

        // Set opponent if provided
        if (opponentId) {
            const oppSelect = document.getElementById('ajax_opponent_id');
            if (oppSelect) oppSelect.value = opponentId;
            const doublesOppSelect = document.getElementById('ajax_doubles_opponent_id');
            if (doublesOppSelect) doublesOppSelect.value = opponentId;
        }

        createMatchReturnFocus = document.activeElement;
        createMatchLocksScroll = !document.body.classList.contains('overflow-hidden');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        document.getElementById('ajax-create-match-fields').scrollTop = 0;
        document.getElementById('ajax-create-match-title').focus({ preventScroll: true });
    }

    function closeCreateMatchModal() {
        const modal = document.getElementById('ajaxCreateMatchModal');
        if (!modal || modal.classList.contains('hidden')) return;
        modal.classList.add('hidden');
        if (createMatchLocksScroll) document.body.classList.remove('overflow-hidden');
        if (createMatchReturnFocus?.isConnected) createMatchReturnFocus.focus({ preventScroll: true });
        createMatchReturnFocus = null;
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
            btnDoubles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-primary text-white border-primary shadow-sm';
            btnSingles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200';
            secSingles.classList.add('hidden');
            secDoubles.classList.remove('hidden');
        } else {
            btnSingles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-primary text-white border-primary shadow-sm';
            btnDoubles.className = 'py-2.5 px-3 rounded-lg text-sm font-semibold border transition text-center cursor-pointer bg-gray-100 text-gray-700 border-gray-200 hover:bg-gray-200';
            secDoubles.classList.add('hidden');
            secSingles.classList.remove('hidden');
        }
        clearAjaxErrors();
    }

    function setAjaxWinnerChoice(choice) {
        document.getElementById('ajax_winner_choice').value = choice;
        const activeClass = 'flex items-center justify-center p-3.5 rounded-lg border cursor-pointer transition text-sm sm:text-base bg-primary-soft border-primary text-primary font-bold ring-2 ring-primary';
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
                btn.className = 'ajax-prize-btn px-3 py-1.5 rounded-lg text-xs sm:text-sm font-medium border transition cursor-pointer bg-primary text-white border-primary font-semibold shadow-sm';
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
        submitBtnText.innerText = 'Đang lưu…';

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
                submitBtnText.innerText = 'Lưu trận';

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
            submitBtnText.innerText = 'Lưu trận';
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
