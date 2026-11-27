@extends('layouts.app', ['title' => 'Quản trị hệ thống - Bida Nội Bộ'])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Bảng Điều Khiển Quản Trị Viên</h1>
                <p class="text-sm sm:text-base text-gray-500 mt-1">
                    Toàn quyền quản trị hệ thống: Xử lý khiếu nại, hủy trận, ẩn/hiện trận, xóa trận & dọn dẹp thùng rác.
                </p>
            </div>

            <!-- Quick Jump Navigation Pills -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="#disputed-queue" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 shadow-2xs transition">
                    🛡️ Tranh chấp ({{ $disputedMatches->count() }})
                </a>
                <a href="#matches-management" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100 text-indigo-800 shadow-2xs transition">
                    🎱 Quản lý trận ({{ $allMatches->total() }})
                </a>
                <a href="#trash-bin" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-red-200 bg-red-50/70 hover:bg-red-100 text-red-800 shadow-2xs transition">
                    🗑️ Thùng rác ({{ $trashedCount }})
                </a>
                <a href="#members-section" class="text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 shadow-2xs transition">
                    👥 Cơ thủ ({{ $members->count() }})
                </a>
            </div>
        </div>

        <!-- Section 1: Disputed Matches Queue -->
        <div id="disputed-queue" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
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
                        <img src="{{ asset('storage/' . $dm->dispute_evidence_path) }}" alt="Bằng chứng"
                            class="max-h-56 rounded-lg border border-gray-300 shadow-sm">
                    @endif

                    <!-- Form resolve -->
                    <form method="POST" action="{{ route('admin.matches.resolve', $dm->id) }}"
                        class="space-y-3 pt-3 border-t border-red-200">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-sm">
                            <label
                                class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                <input type="radio" name="resolution" value="confirm_initial" checked>
                                <span class="font-medium">Giữ nguyên kết quả</span>
                            </label>
                            <label
                                class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                <input type="radio" name="resolution" value="reverse_winner">
                                <span class="font-medium">Đảo ngược người thắng</span>
                            </label>
                            <label
                                class="p-3 border rounded-lg bg-white cursor-pointer flex items-center gap-2 hover:bg-gray-50">
                                <input type="radio" name="resolution" value="cancel">
                                <span class="font-medium">Hủy trận đấu</span>
                            </label>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <input type="text" name="admin_notes" required placeholder="Nhập lý do xử lý của quản trị viên..."
                                class="flex-1 text-sm border border-gray-300 rounded-lg px-3.5 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-primary">
                            <button type="submit"
                                class="bg-primary hover:bg-primary-hover text-white text-sm font-semibold px-5 py-2 rounded-lg cursor-pointer transition shadow-sm shrink-0">
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

        <!-- Section 2: Omnipotent Match Management -->
        <div id="matches-management" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-200">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <span>Quản Lý Toàn Bộ Trận Đấu</span>
                        <span class="bg-indigo-100 text-indigo-800 text-xs px-2.5 py-0.5 rounded-full font-bold">
                            {{ $allMatches->total() }} trận
                        </span>
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        Admin toàn quyền: Hủy trận đấu, ẩn/bỏ ẩn khỏi bảng xếp hạng, và chuyển trận đấu vào thùng rác.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase font-semibold text-xs tracking-wider">
                        <tr>
                            <th class="py-3 px-3.5">Mã</th>
                            <th class="py-3 px-3.5">Cặp đấu</th>
                            <th class="py-3 px-3.5">Kèo / Thưởng</th>
                            <th class="py-3 px-3.5">Ngày đấu</th>
                            <th class="py-3 px-3.5">Trạng thái</th>
                            <th class="py-3 px-3.5">Hiển thị</th>
                            <th class="py-3 px-3.5 text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($allMatches as $m)
                            <tr class="hover:bg-gray-50/80 transition {{ $m->is_hidden ? 'bg-amber-50/30' : '' }}">
                                <td class="py-3 px-3.5 font-mono text-xs font-bold text-gray-600">
                                    #{{ $m->id }}
                                </td>
                                <td class="py-3 px-3.5">
                                    <div class="font-bold text-gray-900 text-sm">
                                        {{ $m->team1_names }} <span class="text-gray-400 font-normal">vs</span> {{ $m->team2_names }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        Thắng: <b class="text-emerald-700">{{ $m->winner_names }}</b>
                                    </div>
                                </td>
                                <td class="py-3 px-3.5 font-medium text-gray-800">
                                    {{ $m->prize }}
                                </td>
                                <td class="py-3 px-3.5 text-xs text-gray-500 font-mono">
                                    {{ $m->played_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 px-3.5">
                                    @if($m->status === 'confirmed')
                                        <span class="bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5 rounded font-bold">Đã xác nhận</span>
                                    @elseif($m->status === 'pending')
                                        <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">Chờ duyệt</span>
                                    @elseif($m->status === 'disputed')
                                        <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded font-bold">Khiếu nại</span>
                                    @elseif($m->status === 'cancelled')
                                        <span class="bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded font-bold">Đã hủy</span>
                                    @else
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded font-bold">{{ $m->status }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5">
                                    @if($m->is_hidden)
                                        <span class="bg-amber-100 text-amber-800 text-xs px-2 py-0.5 rounded font-bold">🙈 Đang ẩn</span>
                                    @else
                                        <span class="bg-slate-100 text-slate-600 text-xs px-2 py-0.5 rounded font-medium">👁️ Đang hiện</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3.5 text-right space-x-2 shrink-0">
                                    <a href="{{ route('matches.show', $m->id) }}"
                                        class="text-primary hover:text-primary-hover text-xs font-semibold">
                                        Chi tiết
                                    </a>

                                    @if($m->status !== 'cancelled')
                                        <button type="button"
                                            onclick="openAdminCancelMatchModal({{ $m->id }}, '{{ addslashes($m->team1_names) }} vs {{ addslashes($m->team2_names) }}')"
                                            class="text-rose-600 hover:text-rose-800 text-xs font-semibold cursor-pointer">
                                            Hủy
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('admin.matches.toggle-hidden', $m->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-gray-600 hover:text-gray-900 text-xs font-semibold cursor-pointer">
                                            {{ $m->is_hidden ? 'Bỏ ẩn' : 'Ẩn' }}
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.matches.trash', $m->id) }}"
                                        onsubmit="return confirm('Bạn có chắc muốn chuyển trận đấu #{{ $m->id }} vào thùng rác?')"
                                        class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 text-xs font-semibold cursor-pointer">
                                            Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-sm text-gray-500">
                                    Chưa có trận đấu nào trong hệ thống.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($allMatches->hasPages())
                <div class="pt-4 border-t border-gray-200">
                    {{ $allMatches->links() }}
                </div>
            @endif
        </div>

        <!-- Section 3: Trash Bin (Thùng rác) -->
        <div id="trash-bin" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-200">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🗑️</span>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <span>Thùng Rác Trận Đấu</span>
                            <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-bold">
                                {{ $trashedCount }} trận
                            </span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Các trận bị xóa mềm. Bạn có thể khôi phục lại hoặc xóa vĩnh viễn khỏi cơ sở dữ liệu.
                        </p>
                    </div>
                </div>

                @if($trashedCount > 0)
                    <form method="POST" action="{{ route('admin.trash.empty') }}"
                        onsubmit="return confirm('CẢNH BÁO QUAN TRỌNG: Bạn có chắc muốn DỌN SẠCH THÙNG RÁC? Toàn bộ {{ $trashedCount }} trận đấu sẽ bị xóa vĩnh viễn và KHÔNG THỂ KHÔI PHỤC!')">
                        @csrf
                        <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer flex items-center gap-1.5">
                            <span>🧹 Dọn sạch thùng rác</span>
                        </button>
                    </form>
                @endif
            </div>

            @if($trashedMatches->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-red-50/50 border-b border-red-100 text-red-900 uppercase font-semibold text-xs tracking-wider">
                            <tr>
                                <th class="py-3 px-3.5">Mã</th>
                                <th class="py-3 px-3.5">Cặp đấu</th>
                                <th class="py-3 px-3.5">Kèo</th>
                                <th class="py-3 px-3.5">Thời gian diễn ra</th>
                                <th class="py-3 px-3.5">Thời gian xóa</th>
                                <th class="py-3 px-3.5 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($trashedMatches as $tm)
                                <tr class="hover:bg-red-50/30 transition">
                                    <td class="py-3 px-3.5 font-mono text-xs font-bold text-gray-600">
                                        #{{ $tm->id }}
                                    </td>
                                    <td class="py-3 px-3.5 font-medium text-gray-900 text-sm">
                                        {{ $tm->team1_names }} <span class="text-gray-400 font-normal">vs</span> {{ $tm->team2_names }}
                                    </td>
                                    <td class="py-3 px-3.5 font-medium text-gray-800">
                                        {{ $tm->prize }}
                                    </td>
                                    <td class="py-3 px-3.5 text-xs text-gray-500 font-mono">
                                        {{ $tm->played_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="py-3 px-3.5 text-xs text-red-600 font-mono">
                                        {{ $tm->deleted_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="py-3 px-3.5 text-right space-x-2 shrink-0">
                                        <a href="{{ route('matches.show', $tm->id) }}"
                                            class="text-primary hover:text-primary-hover text-xs font-semibold">
                                            Xem
                                        </a>

                                        <form method="POST" action="{{ route('admin.trash.restore', $tm->id) }}" class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 rounded text-xs font-semibold transition cursor-pointer">
                                                ♻️ Khôi phục
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.trash.force-delete', $tm->id) }}"
                                            onsubmit="return confirm('CẢNH BÁO: Xóa vĩnh viễn trận #{{ $tm->id }}? Không thể hoàn tác!')"
                                            class="inline">
                                            @csrf
                                            <button type="submit"
                                                class="bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 px-2.5 py-1 rounded text-xs font-semibold transition cursor-pointer">
                                                💥 Xóa vĩnh viễn
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-8 text-center text-sm text-gray-500 flex flex-col items-center justify-center gap-2">
                    <span class="text-3xl text-gray-400">🗑️</span>
                    <span>Thùng rác trống. Không có trận đấu nào bị xóa.</span>
                </div>
            @endif
        </div>

        <!-- Section 4: Members Management Table -->
        <div id="members-section" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-900">Quản Lý Cơ Thủ ({{ $members->count() }})</h2>
                <button type="button" onclick="openCreateMemberModal()"
                    class="bg-primary hover:bg-primary-hover text-white text-xs sm:text-sm font-semibold px-3.5 py-2 rounded-lg shadow-sm transition cursor-pointer">
                    + Tạo mới cơ thủ
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead
                        class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase font-semibold text-xs tracking-wider">
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
                                    <img src="{{ $mb->avatar_url }}" alt="{{ $mb->name }}"
                                        class="w-8 h-8 rounded-full border border-gray-200 object-cover">
                                    <span>{{ $mb->name }}</span>
                                </td>
                                <td class="py-3.5 px-3.5 text-gray-500 font-mono">@<span>{{ $mb->username }}</span></td>
                                <td class="py-3.5 px-3.5">
                                    <span
                                        class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $mb->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $mb->role }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3.5 text-xs">
                                    @if($mb->telegram_id)
                                        <span class="text-green-700 font-semibold flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>Đã nối (ID: {{ $mb->telegram_id }})</span>
                                        </span>
                                    @else
                                        <div class="space-y-1">
                                            <div class="font-mono text-gray-500">Mã: <span
                                                    class="bg-gray-100 font-bold px-1.5 py-0.5 rounded text-gray-800 select-all">{{ $mb->getTelegramLinkCode() }}</span>
                                            </div>
                                            @if($botConfig->bot_username)
                                                <a href="https://t.me/{{ $botConfig->bot_username }}?start={{ $mb->getTelegramLinkCode() }}"
                                                    target="_blank" class="text-primary hover:underline block font-medium text-[11px]">
                                                    Link kết nối bot
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3.5 px-3.5">
                                    <span
                                        class="px-2.5 py-0.5 rounded text-xs font-semibold {{ $mb->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $mb->is_active ? 'Hoạt động' : 'Đã khóa' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-3.5 text-right space-x-2 font-semibold">
                                    <button type="button"
                                        onclick="openAdminResetModal({{ $mb->id }}, '{{ addslashes($mb->name) }}', '{{ $mb->username }}')"
                                        class="text-amber-600 hover:text-amber-800 hover:underline cursor-pointer">
                                        Đổi MK
                                    </button>
                                    <form method="POST" action="{{ route('admin.users.role', $mb->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-primary hover:text-primary-hover hover:underline cursor-pointer">Đổi
                                            quyền</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.status', $mb->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 hover:underline cursor-pointer">{{ $mb->is_active ? 'Khóa' : 'Mở' }}</button>
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
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Tùy chỉnh logo hiển thị trên màn hình đăng nhập và
                        thanh menu điều hướng.</p>
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
                    <form method="POST" action="{{ route('admin.logo.update') }}" enctype="multipart/form-data"
                        class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                                Tải lên logo mới (PNG, JPG, SVG, WebP - Tối đa 5MB)
                            </label>
                            <input type="file" name="logo" required accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-soft file:text-primary hover:file:bg-primary/10 cursor-pointer">
                            @error('logo')
                                <span class="text-red-600 text-xs mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit"
                                class="bg-primary hover:bg-primary-hover text-white font-semibold px-4 py-2 rounded-lg text-sm shadow-sm transition cursor-pointer">
                                Cập nhật logo
                            </button>
                        </div>
                    </form>

                    @if($hasCustomLogo)
                        <form method="POST" action="{{ route('admin.logo.remove') }}" class="pt-2">
                            @csrf
                            <button type="submit"
                                onclick="return confirm('Bạn có chắc muốn xóa logo tùy chỉnh và quay về logo mặc định?')"
                                class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline cursor-pointer">
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
                    <input type="password" name="bot_token" value="{{ $botConfig->bot_token }}"
                        placeholder="123456:ABC-DEF..."
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Bot Username</label>
                    <input type="text" name="bot_username" value="{{ $botConfig->bot_username }}" placeholder="BidaNoiBoBot"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Group Chat ID (ID Nhóm nhận thông báo)</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" name="group_chat_id" value="{{ $botConfig->group_chat_id }}"
                            placeholder="-1001234567890"
                            class="flex-1 border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary">
                        @if($botConfig->bot_username)
                            <a href="https://t.me/{{ $botConfig->bot_username }}?startgroup=bida&admin=invite_users+manage_chat" target="_blank"
                                class="inline-flex items-center justify-center gap-1.5 bg-sky-600 hover:bg-sky-700 text-white font-semibold text-xs px-3.5 py-2 rounded-lg shadow-xs transition shrink-0 cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                    </path>
                                </svg>
                                <span>Tạo nhóm / Thêm Bot</span>
                            </a>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        <b>Tự động gán nhóm:</b> Nhấn nút <b>"Tạo nhóm / Thêm Bot"</b> ở trên để mở Telegram. Bạn có thể
                        chọn <i>"New Group" (Tạo nhóm mới)</i> hoặc chọn nhóm có sẵn. Ngay khi Bot được thêm vào nhóm, hệ
                        thống sẽ <b>tự động lưu Group Chat ID</b> vào web mà bạn không cần gõ thủ công!
                    </p>
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 mb-1.5">Link mời vào nhóm (Group Invite Link)</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="url" name="group_invite_link" value="{{ $botConfig->group_invite_link }}"
                            placeholder="https://t.me/+AbCdEfGhIjKl hoặc https://t.me/ten_nhom"
                            class="flex-1 border border-gray-300 rounded-lg px-3.5 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary">
                        @if($botConfig->group_invite_link)
                            <a href="{{ $botConfig->group_invite_link }}" target="_blank"
                                class="inline-flex items-center justify-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-xs px-3.5 py-2 rounded-lg border border-gray-300 transition shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                                <span>Mở link</span>
                            </a>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1.5 leading-relaxed">
                        <b>Link gửi cho cơ thủ:</b> Khi bấm nút mời, Bot sẽ tự động tạo link nếu Bot có quyền Quản trị viên (Admin) trong nhóm Telegram. Nếu Bot chỉ là thành viên thường hoặc nhóm riêng tư, bạn chỉ cần mở Telegram &gt; Quản lý nhóm &gt; Link mời (Invite Links) &gt; Copy link dán vào ô này để hệ thống luôn có sẵn link gửi tới các cơ thủ.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <input type="checkbox" name="set_webhook" value="1" id="set_webhook"
                        class="rounded border-gray-300 text-primary focus:ring-primary">
                    <label for="set_webhook" class="text-gray-700 font-medium">Đăng ký Webhook URL với Telegram API khi
                        lưu</label>
                </div>

                <button type="submit"
                    class="bg-primary hover:bg-primary-hover text-white font-semibold px-5 py-2.5 rounded-lg text-sm shadow-sm transition cursor-pointer">
                    Lưu cấu hình Bot
                </button>
            </form>
        </div>

        <!-- Section: Daily Telegram Report -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-3">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Báo Cáo Bida Hằng Ngày (Telegram Report)</h2>
                    <p class="text-xs sm:text-sm text-gray-500 mt-0.5">
                        Tự động tổng kết tình hình thi đấu, số trận chờ duyệt, top cơ thủ và đặc biệt là <b>danh sách các
                            kèo nợ chưa trả</b>.
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Tự động: 08:30 sáng hàng ngày</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1 text-sm">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 space-y-2">
                    <div class="font-semibold text-gray-800">Kênh nhận báo cáo hiện tại:</div>
                    @if($botConfig->group_chat_id)
                        <div class="flex items-center gap-2 text-emerald-700 font-medium">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Nhóm Telegram: <code
                                    class="font-mono bg-white px-2 py-0.5 rounded border border-gray-200 text-gray-900">{{ $botConfig->group_chat_id }}</code></span>
                        </div>
                    @else
                        <div class="text-amber-700 text-xs">
                            Chưa cài đặt Group Chat ID. Báo cáo sẽ được gửi riêng cho các Quản trị viên đã liên kết Telegram.
                        </div>
                    @endif
                    @if($botConfig->group_invite_link)
                        <div class="text-xs text-blue-700 flex items-center gap-1.5 pt-0.5">
                            <span class="font-semibold text-gray-700">Link mời nhóm:</span>
                            <a href="{{ $botConfig->group_invite_link }}" target="_blank"
                                class="underline font-mono truncate max-w-[220px] sm:max-w-xs text-primary hover:text-primary-hover">{{ $botConfig->group_invite_link }}</a>
                        </div>
                    @endif
                    <div class="text-xs text-gray-500 pt-1">
                        Nội dung báo cáo: Trận đấu 24h qua, danh sách cơ thủ còn nợ kèo, Top cơ thủ dẫn đầu tháng và Chiến
                        thần bao kèo.
                    </div>
                </div>

                <div
                    class="flex flex-col justify-center items-start bg-blue-50/50 rounded-lg p-4 border border-blue-100 space-y-3">
                    <div class="text-xs text-gray-700">
                        Admin có thể kích hoạt gửi báo cáo ngay lập tức về nhóm chat hoặc mời tất cả cơ thủ đã liên kết
                        Telegram tham gia nhóm:
                    </div>
                    <div class="flex flex-wrap items-center gap-2.5 pt-1">
                        <form method="POST" action="{{ route('admin.telegram.daily-report') }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-primary hover:bg-primary-hover text-white font-semibold px-3.5 py-2 rounded-lg text-xs sm:text-sm shadow-sm transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                <span>Gửi Báo Cáo Ngay</span>
                            </button>
                        </form>

                        @if($botConfig->group_chat_id)
                            <form method="POST" action="{{ route('admin.telegram.invite-all') }}">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-3.5 py-2 rounded-lg text-xs sm:text-sm shadow-sm transition cursor-pointer"
                                    title="Gửi tin nhắn riêng mời các cơ thủ vào nhóm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                                        </path>
                                    </svg>
                                    <span>Mời cơ thủ vào nhóm</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
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
    <div id="adminResetPasswordModal" class="fixed inset-0 z-50 overflow-y-auto hidden"
        aria-labelledby="admin-reset-modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="closeAdminResetModal()">
        </div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="admin-reset-modal-title">Đặt Lại Mật Khẩu Cơ Thủ
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5" id="adminResetTargetInfo">Cơ thủ: ...</p>
                    </div>
                    <button type="button" onclick="closeAdminResetModal()"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>
                <form id="adminResetPasswordForm" method="POST" action="" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label for="admin_new_password"
                            class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Mật khẩu mới <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="password" id="admin_new_password" required minlength="6"
                            placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2.5 focus:border-primary focus:ring-2 focus:ring-primary/30 outline-none transition font-mono">
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="closeAdminResetModal()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                            Hủy bỏ
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-primary hover:bg-primary-hover text-white rounded-lg text-xs sm:text-sm font-semibold shadow-xs transition cursor-pointer">
                            Lưu mật khẩu mới
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Admin Cancel Match Modal -->
    <div id="adminCancelMatchModal" class="fixed inset-0 z-50 overflow-y-auto hidden"
        aria-labelledby="admin-cancel-match-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="closeAdminCancelMatchModal()">
        </div>
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
                <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="admin-cancel-match-title">
                            Hủy Trận Đấu
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5" id="adminCancelMatchInfo">Trận đấu: ...</p>
                    </div>
                    <button type="button" onclick="closeAdminCancelMatchModal()"
                        class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>
                <form id="adminCancelMatchForm" method="POST" action="" class="p-6 space-y-4">
                    @csrf
                    <p class="text-xs text-gray-600">
                        Khi hủy trận, kết quả này sẽ chuyển sang trạng thái <b>Đã hủy</b> và không còn tính vào bảng xếp hạng hay nợ kèo của các cơ thủ.
                    </p>
                    <div>
                        <label for="admin_cancel_reason"
                            class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Lý do hủy trận (tùy chọn)
                        </label>
                        <textarea name="reason" id="admin_cancel_reason" rows="3"
                            placeholder="Nhập lý do admin hủy trận..."
                            class="w-full text-sm rounded-lg border border-gray-300 p-3 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none transition"></textarea>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="closeAdminCancelMatchModal()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer">
                            Đóng
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs sm:text-sm font-semibold shadow-xs transition cursor-pointer">
                            Xác nhận Hủy trận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAdminCancelMatchModal(matchId, summary) {
            var form = document.getElementById('adminCancelMatchForm');
            var info = document.getElementById('adminCancelMatchInfo');
            var input = document.getElementById('admin_cancel_reason');
            if (form) {
                form.action = '/admin/matches/' + matchId + '/cancel';
            }
            if (info) {
                info.innerText = 'Trận #' + matchId + ': ' + summary;
            }
            if (input) {
                input.value = '';
            }
            var m = document.getElementById('adminCancelMatchModal');
            if (m) {
                m.classList.remove('hidden');
                setTimeout(function () { if (input) input.focus(); }, 100);
            }
        }

        function closeAdminCancelMatchModal() {
            var m = document.getElementById('adminCancelMatchModal');
            if (m) m.classList.add('hidden');
        }

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
                setTimeout(function () { if (input) input.focus(); }, 100);
            }
        }

        function closeAdminResetModal() {
            var m = document.getElementById('adminResetPasswordModal');
            if (m) m.classList.add('hidden');
        }
    </script>
@endsection