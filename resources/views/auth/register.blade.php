@extends('layouts.app', ['title' => 'Đăng ký thành viên - Bida Nội Bộ'])

@section('content')
<div class="max-w-md mx-auto my-6 space-y-6">
    <div class="text-center space-y-1">
        <h1 class="text-2xl font-bold text-gray-900">Đăng Ký Thành Viên</h1>
        <p class="text-xs text-gray-500">Tạo tài khoản để tham gia ghi nhận kết quả bida</p>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6 sm:p-8 space-y-4">
        <form method="POST" action="{{ route('register') }}" class="space-y-4 text-sm">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">
                    Họ và Tên hiển thị <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    value="{{ old('name') }}" 
                    required 
                    placeholder="VD: Nguyễn Văn A" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('name') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">
                    Username duy nhất <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="username" 
                    value="{{ old('username') }}" 
                    required 
                    placeholder="VD: vana" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 font-mono"
                >
                <span class="text-[11px] text-gray-400 mt-0.5 block">Dùng để tạo trận nhanh qua Telegram: <code>/vana binh</code></span>
                @error('username') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">
                    Email <span class="text-red-500">*</span>
                </label>
                <input 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    placeholder="vana@company.com" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('email') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="Tối thiểu 6 ký tự" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                >
                @error('password') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 uppercase tracking-wider mb-1">
                    Nhập lại mật khẩu <span class="text-red-500">*</span>
                </label>
                <input 
                    type="password" 
                    name="password_confirmation" 
                    required 
                    placeholder="••••••••" 
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                >
            </div>

            <button 
                type="submit" 
                class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md text-sm shadow-sm transition cursor-pointer"
            >
                Tạo Tài Khoản
            </button>
        </form>

        <div class="text-center pt-3 text-xs text-gray-500 border-t border-gray-200">
            Đã có tài khoản? 
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:underline">Đăng nhập</a>
        </div>
    </div>
</div>
@endsection
