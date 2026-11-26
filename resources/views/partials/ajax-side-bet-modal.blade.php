@auth
@php
    $sideBetMembers = \App\Models\User::where('id', '!=', auth()->id())->orderBy('name')->get();
@endphp

<!-- Side Bet Modal (Pure AJAX, 0ms lag) -->
<div id="ajaxSideBetModal" 
    class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 hidden"
    role="dialog" aria-modal="true" aria-labelledby="sidebet_modal_title">
    
    <div class="relative bg-white rounded-2xl max-w-lg w-full p-5 sm:p-6 shadow-2xl border border-gray-200 transform transition-all space-y-4">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-9 h-9 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg font-black">
                    🎲
                </div>
                <div>
                    <h3 id="sidebet_modal_title" class="text-base sm:text-lg font-black text-gray-900 leading-tight">
                        Vào Kèo Ngoài Bên Lề
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Bắt kèo đối trọng với đồng nghiệp cho trận đấu này
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeSideBetModal()"
                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="ajaxSideBetForm" onsubmit="handleAjaxSubmitSideBet(event)" class="space-y-4">
            <input type="hidden" id="sidebet_challenge_id" name="challenge_id" value="">

            <!-- Matchup Indicator -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 text-center space-y-1">
                <div class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Trận đấu đang diễn ra:</div>
                <div class="text-sm font-black text-gray-900 flex items-center justify-center gap-2 flex-wrap">
                    <span id="sidebet_display_team1" class="text-rose-700">Đội 1</span>
                    <span class="text-gray-400 font-normal">vs</span>
                    <span id="sidebet_display_team2" class="text-emerald-700">Đội 2</span>
                </div>
            </div>

            <!-- Choose team to bet on -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    1. Bạn tin phe nào sẽ chiến thắng? <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-2 gap-2.5">
                    <label class="relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition select-none has-checked:border-purple-600 has-checked:bg-purple-50/70 border-gray-200 hover:bg-gray-50 text-center">
                        <input type="radio" name="bet_on_team" value="challenger" class="sr-only" checked onchange="updateSideBetExplanation()">
                        <span class="text-xs text-gray-500 font-semibold mb-0.5">Đặt niềm tin vào:</span>
                        <span id="sidebet_choice_team1" class="text-sm font-black text-gray-900 leading-tight">Đội 1</span>
                    </label>

                    <label class="relative flex flex-col items-center justify-center p-3 rounded-xl border-2 cursor-pointer transition select-none has-checked:border-purple-600 has-checked:bg-purple-50/70 border-gray-200 hover:bg-gray-50 text-center">
                        <input type="radio" name="bet_on_team" value="opponent" class="sr-only" onchange="updateSideBetExplanation()">
                        <span class="text-xs text-gray-500 font-semibold mb-0.5">Đặt niềm tin vào:</span>
                        <span id="sidebet_choice_team2" class="text-sm font-black text-gray-900 leading-tight">Đội 2</span>
                    </label>
                </div>
            </div>

            <!-- Choose opponent to bet against -->
            <div class="space-y-1.5">
                <label for="sidebet_target_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    2. Chọn người bạn muốn bắt kèo cùng: <span class="text-red-500">*</span>
                </label>
                <select id="sidebet_target_id" name="target_id" required onchange="updateSideBetExplanation()"
                    class="w-full bg-white border border-gray-300 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 shadow-2xs">
                    <option value="" disabled selected>-- Chọn cơ thủ để bắt kèo --</option>
                    @foreach($sideBetMembers as $m)
                        <option value="{{ $m->id }}" data-name="{{ $m->name }}">
                            {{ $m->name }} (@<span>{{ $m->username }}</span>)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Dynamic Rule Explanation Box -->
            <div id="sidebet_explanation_box" class="bg-purple-50/80 border border-purple-200 rounded-xl p-3 text-xs text-purple-900 leading-relaxed">
                💡 <b>Luật phân định:</b> Bạn bắt một phe, người được chọn sẽ tự động nhận phe đối nghịch. Sau trận đấu, phe nào thua sẽ phải trả kèo cho bên thắng!
            </div>

            <!-- Prize Input & Quick Chips -->
            <div class="space-y-1.5">
                <label for="sidebet_prize" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    3. Món kèo thách thức: <span class="text-red-500">*</span>
                </label>
                <input type="text" id="sidebet_prize" name="prize" required
                    value="1 cốc cà phê"
                    placeholder="VD: 1 cốc cà phê muối, 1 ly trà đào..."
                    class="w-full bg-white border border-gray-300 rounded-xl px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 shadow-2xs">
                
                <!-- Quick Chips -->
                <div class="flex flex-wrap gap-1.5 pt-1">
                    @foreach(['1 cốc cà phê', '1 ly trà đào', '1 lon bò húc', '1 bát phở', '1 bữa trưa'] as $chip)
                        <button type="button" onclick="setSideBetPrize('{{ $chip }}')"
                            class="bg-gray-100 hover:bg-purple-100 text-gray-700 hover:text-purple-800 text-[11px] font-semibold px-2.5 py-1 rounded-lg transition cursor-pointer border border-gray-200">
                            {{ $chip }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Optional Note -->
            <div class="space-y-1.5">
                <label for="sidebet_notes" class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                    4. Lời nhắn / Cà khịa (tự nguyện):
                </label>
                <input type="text" id="sidebet_notes" name="notes" maxlength="255"
                    placeholder="VD: Ai thua mời cà phê ngay sau trận nhé!"
                    class="w-full bg-white border border-gray-300 rounded-xl px-3.5 py-2 text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-purple-600 focus:border-purple-600 shadow-2xs">
            </div>

            <!-- Submit buttons -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                <button type="button" onclick="closeSideBetModal()"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-gray-700 hover:bg-gray-100 transition cursor-pointer">
                    Hủy
                </button>
                <button type="submit" id="btn_submit_sidebet"
                    class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-xs sm:text-sm px-5 py-2.5 rounded-xl shadow-md hover:shadow-lg transition flex items-center gap-1.5 cursor-pointer">
                    <span>🎲 Phát Lệnh Vào Kèo</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentSideBetTeam1 = '';
    let currentSideBetTeam2 = '';

    function openSideBetModal(challengeId, team1Name, team2Name, isDoubles) {
        currentSideBetTeam1 = team1Name;
        currentSideBetTeam2 = team2Name;

        document.getElementById('sidebet_challenge_id').value = challengeId;
        document.getElementById('sidebet_display_team1').textContent = team1Name;
        document.getElementById('sidebet_display_team2').textContent = team2Name;
        document.getElementById('sidebet_choice_team1').textContent = team1Name;
        document.getElementById('sidebet_choice_team2').textContent = team2Name;

        // Reset inputs
        const targetSelect = document.getElementById('sidebet_target_id');
        targetSelect.selectedIndex = 0;
        document.getElementById('sidebet_prize').value = '1 cốc cà phê';
        document.getElementById('sidebet_notes').value = '';

        // Reset radio
        document.querySelector('input[name="bet_on_team"][value="challenger"]').checked = true;

        updateSideBetExplanation();

        const modal = document.getElementById('ajaxSideBetModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeSideBetModal() {
        const modal = document.getElementById('ajaxSideBetModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function setSideBetPrize(prize) {
        document.getElementById('sidebet_prize').value = prize;
    }

    function updateSideBetExplanation() {
        const selectedTeam = document.querySelector('input[name="bet_on_team"]:checked')?.value || 'challenger';
        const targetSelect = document.getElementById('sidebet_target_id');
        const selectedTargetOption = targetSelect.options[targetSelect.selectedIndex];
        const targetName = selectedTargetOption && targetSelect.value ? selectedTargetOption.getAttribute('data-name') : 'Đối thủ';

        const myTeam = selectedTeam === 'challenger' ? currentSideBetTeam1 : currentSideBetTeam2;
        const otherTeam = selectedTeam === 'challenger' ? currentSideBetTeam2 : currentSideBetTeam1;

        const box = document.getElementById('sidebet_explanation_box');
        if (box) {
            box.innerHTML = `💡 <b>Luật bắt kèo:</b> Bạn bắt <b class="text-purple-950">${myTeam}</b>. <b>${targetName}</b> sẽ tự động ở phe <b class="text-purple-950">${otherTeam}</b>. Đội nào thua sẽ phải trả kèo cho bên thắng!`;
        }
    }

    async function handleAjaxSubmitSideBet(e) {
        e.preventDefault();
        const challengeId = document.getElementById('sidebet_challenge_id').value;
        const betOnTeam = document.querySelector('input[name="bet_on_team"]:checked')?.value;
        const targetId = document.getElementById('sidebet_target_id').value;
        const prize = document.getElementById('sidebet_prize').value.trim();
        const notes = document.getElementById('sidebet_notes').value.trim();

        if (!challengeId || !betOnTeam || !targetId || !prize) {
            alert('Vui lòng điền đầy đủ các thông tin bắt kèo!');
            return;
        }

        const btn = document.getElementById('btn_submit_sidebet');
        btn.disabled = true;
        const origText = btn.innerHTML;
        btn.innerHTML = '<span>Đang gửi...</span>';

        try {
            const res = await fetch(`/challenges/${challengeId}/side-bets`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    bet_on_team: betOnTeam,
                    target_id: targetId,
                    prize: prize,
                    notes: notes
                })
            });

            const data = await res.json();
            if (data.success) {
                closeSideBetModal();
                alert(data.message || 'Đã gửi lời rủ bắt kèo thành công!');
                // Reload page or refresh tab
                window.location.reload();
            } else {
                alert(data.message || 'Lỗi khi tạo kèo ngoài!');
            }
        } catch (err) {
            alert('Lỗi kết nối mạng khi tạo kèo ngoài!');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    async function handleAcceptSideBet(sideBetId) {
        if (!confirm('Xác nhận bạn ĐỒNG Ý nhận kèo ngoài này?')) return;

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
        if (!confirm('Bạn chắc chắn muốn TỪ CHỐI kèo ngoài này?')) return;

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
        if (!confirm('Bạn chắc chắn muốn HỦY lời rủ kèo ngoài này?')) return;

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
        const notes = prompt('Ghi chú trả kèo (ví dụ: Đã mời cà phê / Đã chuyển khoản):', 'Đã trả kèo');
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
        if (!confirm('Xác nhận bạn ĐÃ NHẬN ĐỦ kèo ngoài này từ đối thủ?')) return;

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
