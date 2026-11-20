@auth
@if(auth()->user()->isAdmin())
<div id="createMemberModal" class="fixed inset-0 z-[100] overflow-y-auto hidden" aria-labelledby="modal-member-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/60 transition-opacity cursor-pointer" onclick="closeCreateMemberModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-200">
            
            <!-- Header -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900" id="modal-member-title">Tạo Mới Cơ Thủ</h3>
                <button type="button" onclick="closeCreateMemberModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-md cursor-pointer">
                    <span class="text-2xl leading-none">&times;</span>
                </button>
            </div>

            <!-- Form Body -->
            <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4 text-sm">
                @csrf

                <!-- Họ và tên -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Họ và Tên cơ thủ <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        required 
                        placeholder="VD: Nguyễn Văn An" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Username duy nhất -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Username duy nhất <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        required 
                        placeholder="VD: an, binh, cuong..." 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <span class="text-[11px] text-gray-400 mt-1 block">Dùng để đăng nhập và tạo kèo nhanh: <code>/an binh</code></span>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Địa chỉ Email <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        required 
                        placeholder="VD: an@bida.local" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>

                <!-- Mật khẩu khởi tạo -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                        Mật khẩu khởi tạo
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Mặc định: password nếu để trống" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <span class="text-[11px] text-gray-400 mt-1 block">Để trống sẽ tự đặt mật khẩu là <code>password</code></span>
                </div>

                <!-- Vai trò & Avatar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Vai trò
                        </label>
                        <select name="role" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                            <option value="member">Thành viên (Cơ thủ)</option>
                            <option value="admin">Quản trị viên (Admin)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 uppercase tracking-wider mb-1.5">
                            Ảnh đại diện (nếu có)
                        </label>
                        <input 
                            type="file" 
                            name="avatar" 
                            accept="image/*"
                            class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer"
                        >
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <button 
                        type="button" 
                        onclick="closeCreateMemberModal()" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer"
                    >
                        Hủy
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm transition cursor-pointer"
                    >
                        Tạo cơ thủ
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
    function openCreateMemberModal() {
        const modal = document.getElementById('createMemberModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeCreateMemberModal() {
        const modal = document.getElementById('createMemberModal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
</script>
@endif
@endauth
