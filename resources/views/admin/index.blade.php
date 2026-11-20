@extends('layouts.app', ['title' => 'Quản trị hệ thống - Bida Nội Bộ'])

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Bảng Điều Khiển Quản Trị Viên</h1>
            <p class="text-sm sm:text-base text-gray-500 mt-1">Xử lý khiếu nại tranh chấp, quản lý tài khoản và cấu hình Telegram Bot.</p>
        </div>

        <!-- Section 1: Disputed Matches Queue -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <span>Tranh Chấp Cần Xử Lý</span>
                    @if($disputedMatches->isNotEmpty())
                        <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-bold">
                            {{ $disputedMatches->count() }}
                        </span>
                    @endif
                </h2>
            </div>

            @forelse($disputedMatches as $dm)
                <div class="border border-red-200 bg-red-50/40 rounded-xl p-5 space-y-3.5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between text-sm gap-2">
                        <span class="font-bold text-red-900 text-base">
                            Trận #{{ $dm->id }}: {{ $dm->player1->name }} vs {{ $dm->player2->name }} (Kèo: {{ $dm->prize }})
                        </span>
                        <span class="text-gray-500 font-medium">{{ $dm->disputed_at?->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="bg-white p-3.5 rounded-lg border border-red-200 text-sm text-gray-800">
                        <b>Lý do khiếu nại:</b> "{{ $dm->dispute_reason }}"
                    </div>

                    @if($dm->dispute_evidence_path)
                        <img src="{{ asset('storage/' . $dm->dispute_evidence_path) }}" alt="Bằng chứng" class="max-h-56 rounded-lg border border-gray-300 shadow-sm">
                    @endif

                    <!-- Form resolve -->
                    <form method="POST" action="{{ route('admin.matches.resolve', $dm->id) }}" class="space-y-3 pt-3 border-t border-red-200">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-sm">
                            <label class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                <input type="radio" name="resolution" value="confirm_initial" checked>
                                <span class="font-medium">Giữ nguyên kết quả</span>
                            </label>
                            <label class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                <input type="radio" name="resolution" value="reverse_winner">
                                <span class="font-medium">Đảo ngược người thắng</span>
                            </label>
                            <label class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                <input type="radio" name="resolution" value="cancel">
                                <span class="font-medium">Hủy trận đấu</span>
                            </label>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <input type="text" name="admin_notes" required placeholder="Nhập lý do xử lý của quản trị viên..." class="flex-1 text-sm border border-gray-300 rounded-lg px-3.5 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg cursor-pointer transition shadow-sm shrink-0">
                                Lưu quyết định
                            </button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="py-8 text-center text-sm text-gray-500">
                    Không có trận đấu nào đang xảy ra tranh chấp.
                </div>
            @endforelse
        </div>

        <!-- Section 2: Members Management Table -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Quản Lý Cơ Thủ ({{ $members->count() }})</h2>
                <button 
                    type="button" 
                    onclick="openCreateMemberModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer"
                >
                    + Tạo mới cơ thủ
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase font-semibold text-xs tracking-wider">
                        <tr>
                            <th class="py-3 px-3.5">Thành viên</th>
                            <th class="py-3 px-3.5">Username</th>
                            <th class="py-3 px-3.5">Vai trò</th>
                            <th class="py-3 px-3.5">Telegram ID</th>
                            <th class="py-3 px-3.5">Trạng thái</th>
                            <th class="py-3 px-3.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($members as $mb)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3.5 px-3.5 font-bold text-gray-900 flex items-center gap-2.5">
                                    <img src="{{ $mb->avatar_url }}" alt="{{ $mb->name }}" class="w-8 h-8 rounded-full border border-gray-200 object-cover">
                                    <span>{{ $mb->name }}</span>
                                </td>
                                <td class="py-3.5 px-3.5 text-gray-500 font-mono">@<span>{{ $mb->username }}</span></td>
                                <td class="py-3.5 px-3.5">
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $mb->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $mb->role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3.5 text-xs">
                                    @if($mb->telegram_id)
                                        <span class="text-green-700 font-semibold flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <span>Đã nối (ID: {{ $mb->telegram_id }})</span>
                                        </span>
                                    @else
                                        <div class="space-y-1">
                                            <div class="font-mono text-gray-500">Mã: <span class="bg-gray-100 font-bold px-1.5 py-0.5 rounded text-gray-800 select-all">{{ $mb->getTelegramLinkCode() }}</span></div>
                                            @if($botConfig->bot_username)
                                                <a href="https://t.me/{{ $botConfig->bot_username }}?start={{ $mb->getTelegramLinkCode() }}" target="_blank" class="text-blue-600 hover:underline block font-medium text-[11px]">
                                                    Link kết nối bot
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3.5">
                                    <span class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $mb->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $mb->is_active ? 'Hoạt động' : 'Đã khóa' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3.5 text-right space-x-2 font-semibold">
                                    <button 
                                        type="button" 
                                        onclick="openAdminResetModal({{ $mb->id }}, '{{ addslashes($mb->name) }}', '{{ $mb->username }}')" 
                                        class="text-amber-600 hover:text-amber-800 hover:underline cursor-pointer"
                                    >
                                        Đổi MK
                                    </button>
                                    <form method="POST" action="{{ route('admin.users.role', $mb->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer">Đổi quyền</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.status', $mb->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800 hover:underline cursor-pointer">{{ $mb->is_active ? 'Khóa' : 'Mở' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section: Logo & System Branding -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Logo Hệ Thống</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Tùy chỉnh logo hiển thị trên màn hình đăng nhập và thanh menu điều hướng.</p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pt-2">
                <!-- Logo Preview Box -->
                <div class="flex flex-col items-center gap-2 p-4 bg-gray-50 border border-gray-200 rounded-xl shrink-0">
                    <div class="h-16 w-32 flex items-center justify-center">
                        @include('partials.app-logo', ['size' => 'lg', 'class' => 'max-h-14 max-w-full object-contain'])
                    </div>
                    <span class="text-xs text-gray-400 font-medium">
                        {{ $hasCustomLogo ? 'Logo tùy chỉnh' : 'Logo mặc định' }}
                    </span>
                </div>

                <!-- Upload & Action Form -->
                <div class="flex-1 space-y-3 w-full">
                    <form method="POST" action="{{ route('admin.logo.update') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Tải lên logo mới (PNG, JPG, SVG, WebP - Tối đa 5MB)
                            </label>
                            <input 
                                type="file" 
                                name="logo" 
                                required 
                                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                            >
                            @error('logo')
                                <span class="text-red-600 text-xs mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition cursor-pointer">
                                Cập nhật logo
                            </button>
                        </div>
                    </form>

                    @if($hasCustomLogo)
                        <form method="POST" action="{{ route('admin.logo.remove') }}" class="pt-2">
                            @csrf
                            <button 
                                type="submit" 
                                onclick="return confirm('Bạn có chắc muốn xóa logo tùy chỉnh và quay về logo mặc định?')" 
                                class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline cursor-pointer"
                            >
                                Xóa logo tùy chỉnh (Dùng lại logo mặc định)
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <!-- Section 3: Telegram Bot Config -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <h2 class="text-lg font-bold text-gray-900">Cấu Hình Telegram Bot</h2>

            <form method="POST" action="{{ route('admin.bot.config') }}" class="space-y-4 max-w-lg text-sm">
                @csrf
                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Telegram Bot Token</label>
                    <input type="password" name="bot_token" value="{{ $botConfig->bot_token }}" placeholder="123456:ABC-DEF..." class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Bot Username</label>
                    <input type="text" name="bot_username" value="{{ $botConfig->bot_username }}" placeholder="BidaNoiBoBot" class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center gap-2.5">
                    <input type="checkbox" name="set_webhook" value="1" id="set_webhook" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <label for="set_webhook" class="text-gray-700 font-medium">Đăng ký Webhook URL với Telegram API khi lưu</label>
                </div>

                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-lg text-sm shadow-sm transition cursor-pointer">
                    Lưu cấu hình Bot
                </button>
            </form>
        </div>

        <!-- Section 4: Audit Logs -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-3">
            <h2 class="text-lg font-bold text-gray-900">Nhật Ký Thao Tác Hệ Thống</h2>

            <div class="divide-y divide-gray-100 text-sm">
                @foreach($recentHistories as $rh)
                    <div class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1 sm:gap-4">
                        <div>
                            <span class="font-bold text-gray-900">{{ $rh->user ? $rh->user->name : 'Hệ thống tự động' }}</span>:
                            <span class="text-gray-700">{{ $rh->note }}</span>
                        </div>
                        <span class="text-gray-400 shrink-0 font-mono text-xs">{{ $rh->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Admin Reset Member Password Modal -->
    <div id="adminResetPasswordModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="admin-reset-modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="closeAdminResetModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="admin-reset-modal-title">Đặt Lại Mật Khẩu Cơ Thủ</h3>
                        <p class="text-xs text-gray-500 mt-0.5" id="adminResetTargetInfo">Cơ thủ: ...</p>
                    </div>
                    <button type="button" onclick="closeAdminResetModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form id="adminResetPasswordForm" method="POST" action="" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label for="admin_new_password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Mật khẩu mới <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="password" 
                            id="admin_new_password" 
                            required 
                            minlength="6"
                            placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition font-mono"
                        >
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="closeAdminResetModal()" class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                            Hủy bỏ
                        </button>
                        <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs sm:text-sm font-semibold shadow-xs transition cursor-pointer">
                            Lưu mật khẩu mới
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAdminResetModal(userId, name, username) {
            var form = document.getElementById('adminResetPasswordForm');
            var info = document.getElementById('adminResetTargetInfo');
            var input = document.getElementById('admin_new_password');
            if (form) {
                form.action = '/admin/users/' + userId + '/password';
            }
            if (info) {
                info.innerText = 'Cơ thủ: ' + name + ' (@' + username + ')';
            }
            if (input) {
                input.value = '';
            }
            var m = document.getElementById('adminResetPasswordModal');
            if (m) {
                m.classList.remove('hidden');
                setTimeout(function() { if (input) input.focus(); }, 100);
            }
        }

        function closeAdminResetModal() {
            var m = document.getElementById('adminResetPasswordModal');
            if (m) m.classList.add('hidden');
        }
    </script>
@endsection
