@auth
@php
    $sideBetMembers = \App\Models\User::where('id', '!=', auth()->id())->orderBy('name')->get();
    $sideBetFieldClass = 'min-h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-primary focus:outline-2 focus:outline-primary';
@endphp

<dialog id="ajaxSideBetModal" aria-labelledby="sidebet_modal_title"
    class="m-auto w-11/12 max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl backdrop:bg-gray-950/55 motion-safe:open:animate-match-dialog motion-safe:backdrop:animate-match-backdrop">
    <form id="ajaxSideBetForm" onsubmit="handleAjaxSubmitSideBet(event)" class="flex max-h-[calc(100dvh-2rem)] flex-col">
        <header class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 sm:px-6">
            <h2 id="sidebet_modal_title" class="text-lg font-bold text-gray-900">Vào kèo ngoài</h2>
            <button type="button" autofocus onclick="closeSideBetModal()" aria-label="Đóng"
                class="grid size-9 cursor-pointer place-items-center rounded-lg text-gray-600 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">
                <svg aria-hidden="true" class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/>
                </svg>
            </button>
        </header>

        <div id="sidebet_fields" class="min-h-0 space-y-4 overflow-y-auto overscroll-contain px-4 py-4 sm:px-6">
            <input type="hidden" id="sidebet_challenge_id" name="challenge_id">

            <fieldset>
                <legend class="mb-2 text-sm font-semibold text-gray-900">Bạn chọn bên nào thắng?</legend>
                <div class="grid grid-cols-2 gap-2">
                    @foreach(['challenger', 'opponent'] as $side)
                        <label class="flex min-h-12 cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-2.5 py-2 text-sm font-semibold text-gray-800 transition-colors hover:border-primary/50 has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary has-focus-visible:outline-2 has-focus-visible:outline-primary sm:px-3">
                            <input type="radio" name="bet_on_team" value="{{ $side }}" class="size-4 shrink-0" required
                                @if($loop->first) checked @endif onchange="updateSideBetExplanation()">
                            <span id="sidebet_choice_team{{ $loop->iteration }}" class="min-w-0 wrap-anywhere"></span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="space-y-1.5">
                <label for="sidebet_target_id" class="block text-sm font-semibold text-gray-900">Người nhận lời mời</label>
                <select id="sidebet_target_id" name="target_id" required onchange="updateSideBetExplanation()" class="{{ $sideBetFieldClass }}">
                    <option value="" disabled selected>Chọn người bắt kèo cùng</option>
                    @foreach($sideBetMembers as $member)
                        <option value="{{ $member->id }}" data-name="{{ $member->name }}">{{ $member->name }} ({{ '@'.$member->username }})</option>
                    @endforeach
                </select>
                <p id="sidebet_explanation_box" class="text-xs leading-relaxed text-gray-600" aria-live="polite"></p>
            </div>

            <div class="space-y-1.5">
                <label for="sidebet_prize" class="block text-sm font-semibold text-gray-900">Món kèo</label>
                <input type="text" id="sidebet_prize" name="prize" required maxlength="255" value="1 cốc cà phê"
                    class="{{ $sideBetFieldClass }}" oninput="updateSideBetPrizeChoices()">
                <div class="flex flex-wrap gap-1.5">
                    @foreach(['1 cốc cà phê', '1 ly trà đào', '1 lon bò húc', '1 bát phở', '1 bữa trưa'] as $prize)
                        <button type="button" data-sidebet-prize="{{ $prize }}" aria-pressed="false" onclick="setSideBetPrize(this.dataset.sidebetPrize)"
                            class="min-h-8 cursor-pointer rounded-md border border-gray-200 px-2 text-xs font-medium text-gray-700 hover:border-primary/40 hover:text-primary focus-visible:outline-2 focus-visible:outline-primary aria-pressed:border-primary/25 aria-pressed:bg-primary-soft aria-pressed:text-primary">
                            {{ $prize }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="space-y-1.5">
                <label for="sidebet_notes" class="block text-sm font-semibold text-gray-900">Lời nhắn <span class="font-normal text-gray-500">(tùy chọn)</span></label>
                <input type="text" id="sidebet_notes" name="notes" maxlength="255" class="{{ $sideBetFieldClass }}">
            </div>

            <p id="sidebet_error" role="alert" hidden class="rounded-lg bg-primary-soft px-3 py-2 text-sm text-primary"></p>
        </div>

        <footer class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 px-4 py-3 sm:px-6">
            <button type="button" onclick="closeSideBetModal()"
                class="min-h-10 cursor-pointer rounded-lg px-4 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-primary">
                Hủy
            </button>
            <button type="submit" id="btn_submit_sidebet"
                class="min-h-10 cursor-pointer rounded-lg bg-primary px-4 text-sm font-semibold text-white hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-wait disabled:opacity-60">
                Gửi lời mời
            </button>
        </footer>
    </form>
</dialog>

<script>
    const sideBetDialog = document.getElementById('ajaxSideBetModal');
    const sideBetForm = document.getElementById('ajaxSideBetForm');
    const sideBetSubmit = document.getElementById('btn_submit_sidebet');
    const sideBetError = document.getElementById('sidebet_error');
    let sideBetLocksScroll = false;

    sideBetDialog.addEventListener('close', () => {
        if (sideBetLocksScroll) document.body.classList.remove('overflow-hidden');
    });

    sideBetDialog.addEventListener('cancel', (event) => {
        if (sideBetSubmit.disabled) event.preventDefault();
    });

    sideBetDialog.addEventListener('click', (event) => {
        if (event.target === sideBetDialog) closeSideBetModal();
    });

    function openSideBetModal(challengeId, team1Name, team2Name) {
        if (sideBetDialog.open) return;
        sideBetForm.reset();
        document.getElementById('sidebet_challenge_id').value = challengeId;
        document.getElementById('sidebet_choice_team1').textContent = team1Name;
        document.getElementById('sidebet_choice_team2').textContent = team2Name;
        sideBetError.hidden = true;
        updateSideBetExplanation();
        updateSideBetPrizeChoices();

        sideBetLocksScroll = !document.body.classList.contains('overflow-hidden');
        sideBetDialog.showModal();
        document.body.classList.add('overflow-hidden');
        document.getElementById('sidebet_fields').scrollTop = 0;
    }

    function closeSideBetModal() {
        if (!sideBetSubmit.disabled) sideBetDialog.close();
    }

    function setSideBetPrize(prize) {
        document.getElementById('sidebet_prize').value = prize;
        updateSideBetPrizeChoices();
    }

    function updateSideBetPrizeChoices() {
        const prize = document.getElementById('sidebet_prize').value.trim();
        sideBetForm.querySelectorAll('[data-sidebet-prize]').forEach((choice) => {
            choice.setAttribute('aria-pressed', String(choice.dataset.sidebetPrize === prize));
        });
    }

    function updateSideBetExplanation() {
        const targetName = sideBetForm.elements.target_id.selectedOptions[0]?.dataset.name ?? 'Người nhận';
        const otherTeamId = sideBetForm.elements.bet_on_team.value === 'challenger' ? 'sidebet_choice_team2' : 'sidebet_choice_team1';
        const otherTeam = document.getElementById(otherTeamId).textContent;
        document.getElementById('sidebet_explanation_box').textContent = targetName + ' sẽ chọn bên ' + otherTeam + ' nếu nhận lời.';
    }

    function showSideBetError(message) {
        sideBetError.hidden = false;
        sideBetError.textContent = message;
        sideBetError.scrollIntoView({ block: 'nearest' });
    }

    async function handleAjaxSubmitSideBet(event) {
        event.preventDefault();
        if (sideBetSubmit.disabled) return;

        const fields = new FormData(sideBetForm);
        const challengeId = fields.get('challenge_id');
        fields.delete('challenge_id');
        fields.set('prize', fields.get('prize').trim());
        fields.set('notes', fields.get('notes').trim());

        if (!fields.get('prize')) {
            showSideBetError('Vui lòng nhập món kèo.');
            document.getElementById('sidebet_prize').focus();
            return;
        }

        sideBetError.hidden = true;
        sideBetSubmit.disabled = true;
        sideBetSubmit.textContent = 'Đang gửi…';

        const retryMessage = 'Chưa gửi được lời mời. Vui lòng thử lại.';
        try {
            const response = await fetch(`/challenges/${challengeId}/side-bets`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(Object.fromEntries(fields))
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                showSideBetError(data.message || retryMessage);
                return;
            }

            sideBetDialog.close();
            alert(data.message || 'Đã gửi lời mời.');
            window.location.reload();
        } catch {
            showSideBetError(retryMessage);
        } finally {
            sideBetSubmit.disabled = false;
            sideBetSubmit.textContent = 'Gửi lời mời';
        }
    }

    async function handleAcceptSideBet(sideBetId) {
        if (!await window.confirmSiteAction({ title: 'Nhận kèo ngoài?', text: 'Bạn sẽ tham gia kèo này.', buttons: ['Quay lại', 'Nhận kèo'] })) return;

        try {
            const res = await fetch(`/side-bets/${sideBetId}/accept`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi nhận kèo!');
            }
        } catch (e) {
            alert('Lỗi kết nối khi nhận kèo!');
        }
    }

    async function handleDeclineSideBet(sideBetId) {
        if (!await window.confirmSiteAction({ title: 'Từ chối kèo ngoài?', text: 'Người mời sẽ được thông báo.', buttons: ['Quay lại', 'Từ chối'] })) return;

        try {
            const res = await fetch(`/side-bets/${sideBetId}/decline`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi từ chối kèo!');
            }
        } catch (e) {
            alert('Lỗi kết nối khi từ chối kèo!');
        }
    }

    async function handleCancelSideBet(sideBetId) {
        if (!await window.confirmSiteAction({ title: 'Hủy lời mời vào kèo?', text: 'Người được mời sẽ không còn nhận được kèo này.', buttons: ['Quay lại', 'Hủy lời mời'] })) return;

        try {
            const res = await fetch(`/side-bets/${sideBetId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi hủy kèo!');
            }
        } catch (e) {
            alert('Lỗi kết nối khi hủy kèo!');
        }
    }

    async function handlePaySideBet(sideBetId) {
        const notes = await window.confirmSiteAction({
            title: 'Cập nhật đã trả kèo?',
            text: 'Người nhận sẽ được thông báo để xác nhận.',
            buttons: ['Quay lại', 'Đã trả kèo'],
            inputPlaceholder: 'Ghi chú trả kèo (tùy chọn)',
        });
        if (notes === null) return;

        try {
            const res = await fetch(`/side-bets/${sideBetId}/pay`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ notes: notes })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi cập nhật trả kèo!');
            }
        } catch (e) {
            alert('Lỗi kết nối khi trả kèo!');
        }
    }

    async function handleConfirmSideBet(sideBetId) {
        if (!await window.confirmSiteAction({ title: 'Xác nhận đã nhận kèo?', text: 'Kèo sẽ được đánh dấu hoàn tất.', buttons: ['Quay lại', 'Đã nhận đủ'] })) return;

        try {
            const res = await fetch(`/side-bets/${sideBetId}/confirm`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ feedback: 'Đã nhận đủ kèo' })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi xác nhận nhận kèo!');
            }
        } catch (e) {
            alert('Lỗi kết nối khi xác nhận nhận kèo!');
        }
    }
</script>
@endauth
