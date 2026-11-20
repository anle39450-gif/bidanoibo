<!-- Change Password Modal -->
<div 
    id="changePasswordModal" 
    class="fixed inset-0 z-50 overflow-y-auto hidden" 
    aria-labelledby="change-password-modal-title" 
    role="dialog" 
    aria-modal="true"
>
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="closeChangePasswordModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">
            
            <!-- Header -->
            <div class="bg-gray-50/80 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900" id="change-password-modal-title">Đổi Mật Khẩu</h3>
                        <p class="text-xs text-gray-500">Cập nhật mật khẩu tài khoản cá nhân của bạn</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    onclick="closeChangePasswordModal()" 
                    class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                    aria-label="Đóng"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('password.change') }}" class="p-6 space-y-4">
                @csrf

                @if(session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 text-xs px-3.5 py-2.5 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-800 text-xs px-3.5 py-2.5 rounded-lg space-y-1">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <!-- Mật khẩu hiện tại -->
                <div>
                    <label for="current_password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Mật khẩu hiện tại <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="current_password" 
                            id="current_password" 
                            required 
                            placeholder="Nhập mật khẩu hiện tại"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition pr-10"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('current_password', this)"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                            aria-label="Ẩn / Hiện mật khẩu"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Mật khẩu mới -->
                <div>
                    <label for="new_password" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Mật khẩu mới <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="new_password" 
                            id="new_password" 
                            required 
                            minlength="6"
                            placeholder="Tối thiểu 6 ký tự"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition pr-10"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('new_password', this)"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                            aria-label="Ẩn / Hiện mật khẩu"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Xác nhận mật khẩu mới -->
                <div>
                    <label for="new_password_confirmation" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Xác nhận mật khẩu mới <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            name="new_password_confirmation" 
                            id="new_password_confirmation" 
                            required 
                            minlength="6"
                            placeholder="Nhập lại mật khẩu mới"
                            class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2.5 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition pr-10"
                        >
                        <button 
                            type="button" 
                            onclick="togglePasswordVisibility('new_password_confirmation', this)"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 cursor-pointer"
                            aria-label="Ẩn / Hiện mật khẩu"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2.5">
                    <button 
                        type="button" 
                        onclick="closeChangePasswordModal()" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs sm:text-sm font-semibold transition cursor-pointer"
                    >
                        Hủy bỏ
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs sm:text-sm font-semibold shadow-xs transition cursor-pointer"
                    >
                        Cập nhật mật khẩu
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    function openChangePasswordModal() {
        var m = document.getElementById('changePasswordModal');
        if (m) {
            m.classList.remove('hidden');
            setTimeout(function() {
                var el = document.getElementById('current_password');
                if (el) el.focus();
            }, 100);
        }
    }

    function closeChangePasswordModal() {
        var m = document.getElementById('changePasswordModal');
        if (m) m.classList.add('hidden');
    }

    function togglePasswordVisibility(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            btn.classList.add('text-blue-600');
        } else {
            input.type = 'password';
            btn.classList.remove('text-blue-600');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeChangePasswordModal();
        }
    });

    @if($errors->has('current_password') || $errors->has('new_password'))
        document.addEventListener('DOMContentLoaded', function() {
            openChangePasswordModal();
        });
    @endif
</script>
