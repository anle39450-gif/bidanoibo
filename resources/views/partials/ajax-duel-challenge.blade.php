@auth
@php
    $challengeOpponents = \App\Models\User::where('is_active', true)
        ->where('id', '!=', auth()->id())
        ->orderBy('name')
        ->get();
@endphp

<dialog id="incoming-challenge-dialog" aria-labelledby="incoming-challenge-title"
    class="m-auto w-[calc(100%-1.5rem)] max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-gray-950/55 motion-safe:open:animate-match-dialog motion-safe:backdrop:animate-match-backdrop">
    <div class="flex max-h-[min(40rem,calc(100dvh-1.5rem))] flex-col">
        <header class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 sm:px-5">
            <h2 id="incoming-challenge-title" class="text-lg font-bold text-gray-900">Lời khiêu chiến mới</h2>
            <button type="button" onclick="deferIncomingChallenge()" autofocus aria-label="Xem sau"
                class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">
                <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" d="m6 6 12 12M18 6l-12 12" />
                </svg>
            </button>
        </header>

        <div class="min-h-0 space-y-3.5 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5">
            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-gray-600">
                <span id="incoming-challenge-format" class="rounded-full bg-primary-soft px-2.5 py-1 font-semibold text-primary"></span>
                <span id="incoming-challenge-deadline" class="tabular-nums"></span>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="min-w-0 rounded-xl bg-gray-50 px-3 py-2.5">
                    <p class="text-xs text-gray-600">Bên mời</p>
                    <p id="incoming-challenger-team" class="mt-1 text-sm font-semibold text-gray-900 wrap-anywhere"></p>
                </div>
                <div class="min-w-0 rounded-xl bg-gray-50 px-3 py-2.5">
                    <p class="text-xs text-gray-600">Bên bạn</p>
                    <p id="incoming-opponent-team" class="mt-1 text-sm font-semibold text-gray-900 wrap-anywhere"></p>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 px-3 py-2.5">
                <p class="text-xs text-gray-600">Món kèo</p>
                <p id="incoming-challenge-prize" class="mt-1 font-semibold text-gray-900 wrap-anywhere"></p>
            </div>
            <div id="incoming-challenge-message" hidden>
                <p class="text-xs font-semibold text-gray-600">Lời nhắn</p>
                <p id="incoming-challenge-message-text" class="mt-1 text-sm whitespace-pre-wrap text-gray-700 wrap-anywhere"></p>
            </div>
            <p id="incoming-challenge-error" role="alert" hidden class="rounded-lg bg-primary-soft px-3 py-2 text-sm text-primary"></p>
        </div>

        <footer class="grid shrink-0 grid-cols-2 gap-2 border-t border-gray-100 px-4 py-3 sm:px-5">
            <button type="button" id="incoming-challenge-decline" onclick="respondToIncomingChallenge(false)"
                class="min-h-10 cursor-pointer rounded-lg border border-gray-200 px-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-primary disabled:opacity-50">Từ chối</button>
            <button type="button" id="incoming-challenge-accept" onclick="respondToIncomingChallenge(true)"
                class="min-h-10 cursor-pointer rounded-lg bg-primary px-3 text-sm font-semibold text-white hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50">Đồng ý</button>
        </footer>
    </div>
</dialog>

<button type="button" id="incoming-challenge-reminder" hidden onclick="openIncomingChallenge()"
    class="fixed right-4 bottom-4 z-40 inline-flex min-h-10 cursor-pointer items-center rounded-full border border-primary/20 bg-white px-4 text-sm font-semibold text-primary shadow-lg hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary">
    Lời khiêu chiến
</button>
<p id="incoming-challenge-feedback" role="status" hidden
    class="fixed right-4 bottom-17 z-40 max-w-[calc(100%-2rem)] rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-800 shadow-lg"></p>

<div id="ajaxSendChallengeModal" class="fixed inset-0 z-100 hidden" role="dialog" aria-modal="true"
    aria-labelledby="ajax-send-challenge-title"
    onkeydown="if (event.key === 'Escape') { event.stopPropagation(); closeSendChallengeModal(); }">
    <div class="absolute inset-0 bg-gray-950/55 motion-safe:animate-match-backdrop" aria-hidden="true" onclick="closeSendChallengeModal()"></div>

    <div class="pointer-events-none relative flex h-full items-end justify-center p-2 sm:items-center sm:p-6">
        <div class="pointer-events-auto flex max-h-full w-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl motion-safe:animate-match-dialog sm:max-w-md">
            <div class="flex shrink-0 items-center justify-between border-b border-gray-100 px-4 py-2.5 sm:px-5">
                <h3 id="ajax-send-challenge-title" tabindex="-1" class="text-lg font-bold text-gray-900 focus:outline-none">Khiêu chiến</h3>
                <button type="button" onclick="closeSendChallengeModal()" aria-label="Đóng"
                    class="grid size-9 cursor-pointer place-items-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-primary">
                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <form id="ajaxSendChallengeForm" onsubmit="handleAjaxSendChallengeSubmit(event)" class="flex min-h-0 flex-1 flex-col text-sm">
                @csrf
                <input type="hidden" name="challenge_type" id="challenge_type" value="singles">

                <div id="ajax-send-challenge-fields" class="min-h-0 space-y-3.5 overflow-y-auto overscroll-contain px-4 py-4 sm:px-5">
                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-gray-100 p-1" role="group" aria-label="Thể thức">
                        <button type="button" id="tab_challenge_singles" onclick="switchChallengeMode('singles')" aria-pressed="true"
                            class="min-h-9 cursor-pointer rounded-lg px-3 font-semibold text-gray-600 transition hover:text-primary focus-visible:outline-2 focus-visible:outline-primary aria-pressed:bg-white aria-pressed:text-primary aria-pressed:shadow-xs">
                            Đánh đơn
                        </button>
                        <button type="button" id="tab_challenge_doubles" onclick="switchChallengeMode('doubles')" aria-pressed="false"
                            class="min-h-9 cursor-pointer rounded-lg px-3 font-semibold text-gray-600 transition hover:text-primary focus-visible:outline-2 focus-visible:outline-primary aria-pressed:bg-white aria-pressed:text-primary aria-pressed:shadow-xs">
                            Đánh đôi
                        </button>
                    </div>

                    <div id="challenge_singles_container">
                        <label for="challenge_opponent_id" class="mb-1.5 block font-semibold text-gray-700">Đối thủ <span class="text-primary">*</span></label>
                        <select name="opponent_id" id="challenge_opponent_id" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Chọn đối thủ</option>
                            @foreach($challengeOpponents as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ '@'.$u->username }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="challenge_doubles_container" class="hidden space-y-3">
                        <div>
                            <label for="challenge_challenger_partner_id" class="mb-1.5 block font-semibold text-gray-700">Đồng đội <span class="text-primary">*</span></label>
                            <select name="challenger_partner_id" id="challenge_challenger_partner_id"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                <option value="">Chọn đồng đội</option>
                                @foreach($challengeOpponents as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ '@'.$u->username }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="min-w-0">
                                <label for="challenge_opponent_id_doubles" class="mb-1.5 block font-semibold text-gray-700">Đối thủ 1 <span class="text-primary">*</span></label>
                                <select id="challenge_opponent_id_doubles"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">Chọn cơ thủ</option>
                                    @foreach($challengeOpponents as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ '@'.$u->username }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-0">
                                <label for="challenge_opponent_partner_id" class="mb-1.5 block font-semibold text-gray-700">Đối thủ 2 <span class="text-primary">*</span></label>
                                <select name="opponent_partner_id" id="challenge_opponent_partner_id"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">
                                    <option value="">Chọn cơ thủ</option>
                                    @foreach($challengeOpponents as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }} ({{ '@'.$u->username }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <p class="rounded-lg bg-gray-50 px-3 py-2 text-xs leading-relaxed text-gray-600">Khi trả kèo, bạn ghép với đối thủ 1, đồng đội ghép với đối thủ 2.</p>
                    </div>

                    <div>
                        <label for="challenge_prize" class="mb-1.5 block font-semibold text-gray-700">Kèo <span class="text-primary">*</span></label>
                        <input type="text" name="prize" id="challenge_prize" required maxlength="255" value="Kèo cà phê muối" placeholder="Nhập kèo"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary">

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach([
                                'Cà phê muối' => 'Kèo cà phê muối',
                                'Rockstar' => 'Kèo Rockstar',
                                'Trà sữa' => 'Kèo trà sữa',
                                'Bữa trưa' => 'Kèo bữa trưa',
                                'Tiền bàn' => 'Kèo tiền bàn',
                                'Bia hơi' => 'Kèo bia hơi',
                            ] as $label => $qp)
                                <button type="button" onclick="document.getElementById('challenge_prize').value = '{{ $qp }}'"
                                    class="min-h-8 cursor-pointer rounded-lg bg-gray-100 px-2.5 text-xs font-medium text-gray-700 transition hover:bg-primary-soft hover:text-primary focus-visible:outline-2 focus-visible:outline-primary">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="challenge_message" class="mb-1.5 block font-semibold text-gray-700">Lời nhắn <span class="text-xs font-normal text-gray-500">(tùy chọn)</span></label>
                        <textarea name="message" id="challenge_message" rows="2" maxlength="500" placeholder="Để trống để dùng lời mời mặc định"
                            class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
                    </div>
                </div>

                <div class="shrink-0 space-y-2 border-t border-gray-100 bg-gray-50 px-4 py-3 sm:px-5">
                    <div id="challenge_form_alert" role="alert" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700"></div>
                    <div class="flex items-center justify-end gap-2.5">
                        <button type="button" onclick="closeSendChallengeModal()"
                            class="min-h-10 cursor-pointer rounded-lg border border-gray-300 bg-white px-4 font-medium text-gray-700 transition hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">
                            Hủy
                        </button>
                        <button type="submit" id="btn_submit_challenge"
                            class="min-h-10 cursor-pointer rounded-lg bg-primary px-4 font-semibold text-white transition hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-wait disabled:opacity-70">
                            Gửi khiêu chiến
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 3. JAVASCRIPT CONTROLLER FOR PURE AJAX DUEL CHALLENGE -->
<!-- ============================================================== -->
<script>
    let currentChallengeMode = 'singles';
    let sendChallengeReturnFocus = null;
    let sendChallengeLocksScroll = false;

    // Switch Singles / Doubles challenge mode
    function switchChallengeMode(mode) {
        currentChallengeMode = mode;
        const isDoubles = mode === 'doubles';

        document.getElementById('challenge_type').value = mode;
        document.getElementById('tab_challenge_singles').setAttribute('aria-pressed', String(!isDoubles));
        document.getElementById('tab_challenge_doubles').setAttribute('aria-pressed', String(isDoubles));
        document.getElementById('challenge_singles_container').classList.toggle('hidden', isDoubles);
        document.getElementById('challenge_doubles_container').classList.toggle('hidden', !isDoubles);
        document.getElementById('challenge_opponent_id').required = !isDoubles;
        document.getElementById('challenge_challenger_partner_id').required = isDoubles;
        document.getElementById('challenge_opponent_id_doubles').required = isDoubles;
        document.getElementById('challenge_opponent_partner_id').required = isDoubles;
        document.getElementById('challenge_form_alert').classList.add('hidden');
    }

    // Modal Send Challenge controls
    function openSendChallengeModal(opponentId = null) {
        const modal = document.getElementById('ajaxSendChallengeModal');
        if (!modal || !modal.classList.contains('hidden')) return;
        if (opponentId) {
            document.getElementById('challenge_opponent_id').value = opponentId;
            switchChallengeMode('singles');
        }
        sendChallengeReturnFocus = document.activeElement;
        sendChallengeLocksScroll = !document.body.classList.contains('overflow-hidden');
        document.body.classList.add('overflow-hidden');
        modal.classList.remove('hidden');
        document.getElementById('ajax-send-challenge-fields').scrollTop = 0;
        document.getElementById('ajax-send-challenge-title').focus({ preventScroll: true });
    }

    function closeSendChallengeModal() {
        const modal = document.getElementById('ajaxSendChallengeModal');
        if (!modal || modal.classList.contains('hidden')) return;
        modal.classList.add('hidden');
        if (sendChallengeLocksScroll) document.body.classList.remove('overflow-hidden');
        if (sendChallengeReturnFocus?.isConnected) sendChallengeReturnFocus.focus({ preventScroll: true });
        sendChallengeReturnFocus = null;
        document.getElementById('challenge_form_alert').classList.add('hidden');
    }

    // Submit Send Challenge via AJAX
    async function handleAjaxSendChallengeSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const btn = document.getElementById('btn_submit_challenge');
        const alertBox = document.getElementById('challenge_form_alert');

        if (currentChallengeMode === 'doubles') {
            const opp1 = document.getElementById('challenge_opponent_id_doubles').value;
            if (!opp1) {
                alertBox.textContent = 'Vui lòng chọn đối thủ 1.';
                alertBox.classList.remove('hidden');
                return;
            }
            document.getElementById('challenge_opponent_id').value = opp1;
        }

        btn.disabled = true;
        btn.textContent = 'Đang gửi…';
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
                if (window.location.href.includes('tab=upcoming')) {
                    window.location.reload();
                }
            } else {
                alertBox.textContent = data.message || 'Chưa gửi được lời khiêu chiến. Vui lòng thử lại.';
                alertBox.classList.remove('hidden');
            }
        } catch (err) {
            alertBox.textContent = 'Không thể kết nối đến máy chủ.';
            alertBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Gửi khiêu chiến';
        }
    }


    const incomingChallengeDialog = document.getElementById('incoming-challenge-dialog');
    const incomingChallengeReminder = document.getElementById('incoming-challenge-reminder');
    const incomingChallengeError = document.getElementById('incoming-challenge-error');
    const incomingChallengeButtons = incomingChallengeDialog.querySelectorAll('footer button');
    let incomingChallenge = null;
    let deferredChallengeId = null;
    let checkingChallenges = false;
    let respondingToChallenge = false;
    let challengeLocksScroll = false;
    let challengeFeedbackTimer;

    function openIncomingChallenge() {
        if (!incomingChallenge || respondingToChallenge || incomingChallengeDialog.open) return;
        if ([...document.querySelectorAll('dialog[open], [aria-modal=true]:not(.swal-modal), .swal-overlay--show-modal')]
            .some((modal) => modal.getClientRects().length)) return;

        const challenge = incomingChallenge;
        document.getElementById('incoming-challenge-format').textContent = challenge.is_doubles ? 'Đánh đôi' : 'Đánh đơn';
        document.getElementById('incoming-challenge-deadline').textContent = challenge.reply_deadline ? 'Phản hồi trước ' + challenge.reply_deadline : '';
        document.getElementById('incoming-challenger-team').textContent = challenge.challenger_team_names;
        document.getElementById('incoming-opponent-team').textContent = challenge.opponent_team_names;
        document.getElementById('incoming-challenge-prize').textContent = challenge.prize;
        document.getElementById('incoming-challenge-message').hidden = !challenge.message;
        document.getElementById('incoming-challenge-message-text').textContent = challenge.message || '';
        incomingChallengeReminder.hidden = true;
        challengeLocksScroll ||= !document.body.classList.contains('overflow-hidden');
        document.body.classList.add('overflow-hidden');
        incomingChallengeDialog.showModal();
    }

    function deferIncomingChallenge() {
        if (respondingToChallenge) return;
        deferredChallengeId = incomingChallenge?.id;
        incomingChallengeReminder.hidden = !incomingChallenge;
        incomingChallengeDialog.close();
        if (incomingChallenge) incomingChallengeReminder.focus({ preventScroll: true });
    }

    incomingChallengeDialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        deferIncomingChallenge();
    });
    incomingChallengeDialog.addEventListener('close', () => {
        if (incomingChallengeDialog.open) return;
        if (challengeLocksScroll) document.body.classList.remove('overflow-hidden');
        challengeLocksScroll = false;
    });

    function showChallengeFeedback(message) {
        const feedback = document.getElementById('incoming-challenge-feedback');
        feedback.hidden = false;
        feedback.textContent = message;
        clearTimeout(challengeFeedbackTimer);
        challengeFeedbackTimer = setTimeout(() => { feedback.hidden = true; }, 5000);
    }

    async function respondToIncomingChallenge(accept) {
        if (!incomingChallenge || respondingToChallenge) return;

        const challenge = incomingChallenge;
        const actionButton = document.getElementById(accept ? 'incoming-challenge-accept' : 'incoming-challenge-decline');
        let reopen = true;
        respondingToChallenge = true;
        incomingChallengeButtons.forEach((button) => { button.disabled = true; });
        incomingChallengeError.hidden = true;
        incomingChallengeDialog.close();

        try {
            const agreed = await window.confirmSiteAction({
                title: accept ? 'Đồng ý khiêu chiến?' : 'Từ chối khiêu chiến?',
                text: accept && challenge.is_doubles
                    ? 'Bạn sẽ đồng ý thay mặt đội ' + challenge.opponent_team_names + '. Món kèo: ' + challenge.prize + '.'
                    : 'Lời mời từ ' + challenge.challenger_team_names + '. Món kèo: ' + challenge.prize + '.',
                buttons: ['Quay lại', accept ? 'Đồng ý' : 'Từ chối'],
            });
            if (!agreed) return;

            const response = await fetch(accept ? challenge.accept_url : challenge.decline_url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                if ([403, 404, 409].includes(response.status)) {
                    incomingChallenge = null;
                    reopen = false;
                    showChallengeFeedback(data.message || 'Lời khiêu chiến không còn khả dụng.');
                } else {
                    incomingChallengeError.textContent = data.message || 'Chưa gửi được phản hồi. Vui lòng thử lại.';
                    incomingChallengeError.hidden = false;
                }
                return;
            }

            incomingChallenge = null;
            reopen = false;
            if (accept && data.redirect_url) {
                window.location.assign(data.redirect_url);
            } else {
                showChallengeFeedback(data.message || 'Đã từ chối lời khiêu chiến.');
            }
        } catch {
            incomingChallengeError.textContent = 'Chưa gửi được phản hồi. Vui lòng thử lại.';
            incomingChallengeError.hidden = false;
        } finally {
            respondingToChallenge = false;
            incomingChallengeButtons.forEach((button) => { button.disabled = false; });
            if (reopen) {
                incomingChallengeReminder.hidden = false;
                openIncomingChallenge();
                if (incomingChallengeDialog.open) actionButton.focus({ preventScroll: true });
            } else {
                incomingChallengeReminder.hidden = true;
                if (!accept) checkPendingChallenges();
            }
        }
    }

    async function checkPendingChallenges() {
        if (checkingChallenges || respondingToChallenge || document.hidden) return;
        checkingChallenges = true;

        try {
            const response = await fetch(@js(route('challenges.check')), { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) return;
            const data = await response.json();
            if (respondingToChallenge) return;

            const pending = data.has_challenge ? data.challenge : null;
            if (incomingChallenge?.id !== pending?.id) {
                incomingChallengeError.hidden = true;
                if (incomingChallengeDialog.open) incomingChallengeDialog.close();
            }
            incomingChallenge = pending;

            if (!pending) {
                incomingChallengeReminder.hidden = true;
                return;
            }
            if (!incomingChallengeDialog.open) {
                incomingChallengeReminder.hidden = false;
                if (deferredChallengeId !== pending.id) openIncomingChallenge();
            }
        } catch {
            // Keep the current invitation visible until the next successful check.
        } finally {
            checkingChallenges = false;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        checkPendingChallenges();
        setInterval(checkPendingChallenges, 5000);
    });
    document.addEventListener('visibilitychange', checkPendingChallenges);
</script>
@endauth
