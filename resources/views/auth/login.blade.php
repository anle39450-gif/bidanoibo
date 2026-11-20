@extends('layouts.auth', ['title' => 'Đăng nhập'])

@section('content')
<div class="space-y-5">
    <!-- Logo & Header (Tối giản, hỗ trợ thay logo) -->
    <div class="text-center">
        <div class="flex justify-center mb-3">
            @include('partials.app-logo', ['size' => 'lg'])
        </div>
        <h1 class="text-xl font-bold text-gray-900 tracking-tight">Đăng nhập</h1>
        <p class="text-xs text-gray-500 mt-1">Hệ thống quản lý lịch sử đấu bida nội bộ</p>
    </div>

    <!-- Login Card tối giản -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 sm:p-6 shadow-xs">
        <form method="POST" action="{{ route('login') }}" class="space-y-4 text-sm">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Tên đăng nhập hoặc Email
                </label>
                <input 
                    type="text" 
                    name="login" 
                    value="{{ old('login') }}" 
                    required 
                    autofocus 
                    placeholder="VD: admin, an, binh..." 
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600 transition"
                >
                @error('login')
                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">
                    Mật khẩu
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="••••••••" 
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-600 focus:border-blue-600 transition"
                >
                @error('password')
                    <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="flex items-center text-xs">
                <label class="flex items-center gap-2 cursor-pointer text-gray-600">
                    <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>Ghi nhớ đăng nhập</span>
                </label>
            </div>

            <button 
                type="submit" 
                class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm shadow-xs transition cursor-pointer"
            >
                Đăng nhập
            </button>
        </form>

        <div class="text-center pt-4 text-xs text-gray-400 border-t border-gray-100 mt-4">
            Tài khoản nội bộ do Quản trị viên cấp
        </div>
    </div>
</div>
@endsection
