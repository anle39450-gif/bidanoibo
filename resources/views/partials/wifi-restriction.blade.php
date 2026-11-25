@php
    $wifiRestrictionEnabled = config('app.wifi_restriction_enabled', true);
    $allowedWifiIp = config('app.allowed_wifi_ip', '222.252.18.26');
@endphp

@if($wifiRestrictionEnabled)
<!-- WiFi IP Restriction Guard (Check via https://api.ipify.org) -->
<div id="wifiRestrictionOverlay" 
    class="fixed inset-0 z-[999999] bg-gray-950/90 backdrop-blur-md flex items-center justify-center p-4 select-none"
    style="display: none;">
    <div class="relative bg-white border border-gray-200 rounded-2xl p-6 sm:p-8 max-w-md w-full shadow-2xl text-center space-y-5">
        
        <!-- Checking State -->
        <div id="wifiCheckingBox" class="space-y-4">
            <div class="w-16 h-16 mx-auto rounded-full bg-blue-50 border-2 border-blue-200 flex items-center justify-center text-blue-600">
                <svg class="w-8 h-8 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Đang kiểm tra mạng WiFi...</h3>
                <p class="text-xs text-gray-500 mt-1">Đang đối soát địa chỉ IP qua https://api.ipify.org</p>
            </div>
        </div>

        <!-- Blocked State -->
        <div id="wifiBlockedBox" class="space-y-5 hidden">
            <!-- Alert Icon -->
            <div class="w-20 h-20 mx-auto rounded-full bg-rose-50 border-4 border-rose-200 flex items-center justify-center text-rose-600 shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <!-- Title & Subtitle -->
            <div class="space-y-1.5">
                <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-black uppercase tracking-wider rounded-full">
                    🚫 Chưa Đúng Mạng WiFi
                </span>
                <h3 class="text-xl font-black text-gray-900 tracking-tight">
                    Yêu Cầu Kết Nối WiFi Nội Bộ
                </h3>
                <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                    Hệ thống <b class="text-gray-900">Bida Nội Bộ</b> chỉ cho phép các thiết bị kết nối cùng mạng WiFi văn phòng truy cập.
                </p>
            </div>

            <!-- IP Comparison Box -->
            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3.5 space-y-2.5 text-xs text-left">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 font-medium">IP WiFi yêu cầu:</span>
                    <span class="font-mono font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded">
                        {{ $allowedWifiIp }}
                    </span>
                </div>
                <div class="flex items-center justify-between border-t border-gray-200/60 pt-2">
                    <span class="text-gray-500 font-medium">IP hiện tại của bạn:</span>
                    <span id="wifiDetectedIp" class="font-mono font-bold text-rose-700 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded">
                        Đang lấy...
                    </span>
                </div>
                <div class="text-[11px] text-gray-400 italic text-center pt-1">
                    (Kiểm tra tự động qua api.ipify.org)
                </div>
            </div>

            <p class="text-xs text-gray-500">
                👉 Vui lòng kết nối vào mạng WiFi công ty và bấm nút <b>"Kiểm tra lại"</b>.
            </p>

            <!-- Retry button -->
            <button type="button" onclick="performWifiCheck(true)" id="btnRetryWifi"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-xl text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Kiểm tra lại kết nối</span>
            </button>
        </div>

    </div>
</div>

<script>
(function() {
    const ALLOWED_IP = @json($allowedWifiIp);
    const overlay = document.getElementById('wifiRestrictionOverlay');
    const checkingBox = document.getElementById('wifiCheckingBox');
    const blockedBox = document.getElementById('wifiBlockedBox');
    const detectedIpSpan = document.getElementById('wifiDetectedIp');
    const btnRetry = document.getElementById('btnRetryWifi');

    let isChecking = false;

    // Fast check cached IP in sessionStorage
    const cachedIp = sessionStorage.getItem('bida_verified_wifi_ip');
    const cachedTime = parseInt(sessionStorage.getItem('bida_verified_wifi_time') || '0', 10);
    const now = Date.now();

    // If verified within last 60 seconds, don't show overlay immediately
    if (cachedIp === ALLOWED_IP && (now - cachedTime) < 60000) {
        // Still verify silently in background
        performWifiCheck(false);
    } else {
        // Show checking overlay
        showCheckingOverlay();
        performWifiCheck(false);
    }

    function showCheckingOverlay() {
        if (!overlay) return;
        overlay.style.display = 'flex';
        if (checkingBox) checkingBox.classList.remove('hidden');
        if (blockedBox) blockedBox.classList.add('hidden');
    }

    function showBlockedOverlay(currentIp) {
        if (!overlay) return;
        overlay.style.display = 'flex';
        if (checkingBox) checkingBox.classList.add('hidden');
        if (blockedBox) blockedBox.classList.remove('hidden');
        if (detectedIpSpan) {
            detectedIpSpan.textContent = currentIp || 'Không xác định';
        }
        sessionStorage.removeItem('bida_verified_wifi_ip');
        sessionStorage.removeItem('bida_verified_wifi_time');
    }

    function hideOverlay() {
        if (!overlay) return;
        overlay.style.display = 'none';
    }

    async function performWifiCheck(isManualClick = false) {
        if (isChecking) return;
        isChecking = true;

        if (isManualClick && btnRetry) {
            btnRetry.disabled = true;
            btnRetry.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> <span>Đang kiểm tra...</span>';
        }

        try {
            // Fetch public IP via https://api.ipify.org?format=json
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 6000);

            const response = await fetch('https://api.ipify.org?format=json', {
                signal: controller.signal,
                headers: { 'Accept': 'application/json' }
            });
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            const userIp = (data.ip || '').trim();

            if (userIp === ALLOWED_IP) {
                // SUCCESS: IP matches allowed office wifi
                sessionStorage.setItem('bida_verified_wifi_ip', userIp);
                sessionStorage.setItem('bida_verified_wifi_time', Date.now().toString());
                hideOverlay();
            } else {
                // FAILED: Not on office wifi
                showBlockedOverlay(userIp);
            }
        } catch (err) {
            console.warn('Wifi check warning via ipify:', err);
            // If offline or blocked
            showBlockedOverlay('Không thể kết nối (Lỗi mạng)');
        } finally {
            isChecking = false;
            if (btnRetry) {
                btnRetry.disabled = false;
                btnRetry.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> <span>Kiểm tra lại kết nối</span>';
            }
        }
    }

    // Expose for retry button
    window.performWifiCheck = performWifiCheck;

    // Periodic check every 30 seconds
    setInterval(function() {
        performWifiCheck(false);
    }, 30000);

    // Also re-check when user switches back to tab
    window.addEventListener('focus', function() {
        performWifiCheck(false);
    });
})();
</script>
@endif
