<!DOCTYPE html>
<html lang="vi" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Quản Lý Lịch Sử Đấu Bida Nội Bộ' }}</title>

    <!-- Native System Font for 0ms lag -->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full text-gray-900 antialiased" x-data="{ mobileMenuOpen: false }">

    <div class="min-h-full">
        
        <!-- Mobile Sidebar Backdrop & Menu -->
        <div x-show="mobileMenuOpen" class="relative z-50 md:hidden" style="display: none;" role="dialog" aria-modal="true">
            <div x-show="mobileMenuOpen" 
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60" 
                 @click="mobileMenuOpen = false"></div>

            <div class="fixed inset-0 flex">
                <div x-show="mobileMenuOpen" 
                     x-transition:enter="transition ease-in-out duration-200 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-16 flex w-full max-w-xs flex-1">
                    
                    <div class="flex flex-col grow gap-y-4 overflow-y-auto bg-white px-5 pb-4">
                        <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200">
                            <a href="{{ route('calendar') }}" class="flex items-center gap-2.5 font-bold text-gray-900 text-base">
                                @include('partials.app-logo', ['size' => 'sm'])
                                <span>Bida Nội Bộ</span>
                            </a>
                            <button type="button" @click="mobileMenuOpen = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-md hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                onclick="triggerCreateMatchModal(); mobileMenuOpen = false;" 
                                class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm shadow-sm transition text-center cursor-pointer"
                            >
                                + Ghi nhận trận đấu
                            </button>
                            <button 
                                type="button" 
                                onclick="openTelegramHelpModal(); mobileMenuOpen = false;" 
                                title="Hướng dẫn sử dụng Telegram Bot"
                                class="w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-600 hover:text-blue-600 flex items-center justify-center font-bold text-sm shadow-xs shrink-0 cursor-pointer"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </button>
                        </div>

                        <nav class="flex flex-1 flex-col">
                            <div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3 mb-2">Menu chính</div>
                            <ul role="list" class="flex flex-1 flex-col gap-y-1">
                                <li>
                                    <a href="{{ route('calendar') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('calendar') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Lịch thi đấu (Calendar)
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('dashboard') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Bảng điều khiển (Dashboard)
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('matches.index') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('matches.index') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Lịch sử các trận đấu
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('leaderboard') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('leaderboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Bảng xếp hạng
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('members.index') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('members.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Danh sách cơ thủ
                                    </a>
                                </li>
                                @if(auth()->user()->isAdmin())
                                    <li class="pt-3 border-t border-gray-100 mt-2">
                                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3 mb-2">Quản lý</div>
                                        <a href="{{ route('admin.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-amber-700 hover:bg-amber-50' }}">
                                            <span>Trang quản trị</span>
                                            @php $disputeCount = \App\Models\BilliardMatch::where('status', 'disputed')->count(); @endphp
                                            @if($disputeCount > 0)
                                                <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $disputeCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endif
                            </ul>

                            <!-- Mobile User Profile & Password / Logout -->
                            <div class="pt-4 border-t border-gray-100 mt-auto">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('members.show', auth()->user()->username) }}" class="flex items-center gap-2.5 overflow-hidden group">
                                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full border border-gray-200 object-cover shrink-0">
                                        <div class="overflow-hidden">
                                            <div class="text-xs font-bold text-gray-900 group-hover:text-blue-600 truncate leading-tight">{{ auth()->user()->name }}</div>
                                            <div class="flex items-center gap-1 mt-0.5 text-[11px]">
                                                <span class="text-green-700 font-bold">{{ auth()->user()->wins_count }}W</span>
                                                <span class="text-gray-300">•</span>
                                                <span class="text-red-600 font-bold">{{ auth()->user()->losses_count }}L</span>
                                                <span class="text-gray-300">•</span>
                                                @if(auth()->user()->unpaid_debts_count > 0)
                                                    <span class="text-amber-700 font-bold" title="Kèo bạn nợ">Nợ {{ auth()->user()->unpaid_debts_count }}</span>
                                                    <span class="text-gray-300">•</span>
                                                @endif
                                                <span class="text-blue-700 font-medium" title="Kèo đối thủ chưa trả bạn">Nhận {{ auth()->user()->unpaid_receivables_count }}</span>
                                            </div>
                                        </div>
                                    </a>

                                    <div class="flex items-center gap-1">
                                        <button 
                                            type="button" 
                                            onclick="openChangePasswordModal(); mobileMenuOpen = false;" 
                                            title="Đổi mật khẩu" 
                                            class="text-gray-400 hover:text-blue-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                                        >
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                        </button>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" title="Đăng xuất" class="text-gray-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop Sidebar Menu (Menu Dọc Cố Định) -->
        <aside class="hidden md:fixed md:inset-y-0 md:z-40 md:flex md:w-64 md:flex-col bg-white border-r border-gray-200">
            <!-- Brand Logo -->
            <div class="flex h-16 shrink-0 items-center px-6 border-b border-gray-200">
                <a href="{{ route('calendar') }}" class="flex items-center gap-3">
                    @include('partials.app-logo', ['size' => 'md'])
                    <div>
                        <div class="font-bold text-gray-900 text-base leading-tight">Bida Nội Bộ</div>
                        <div class="text-xs text-gray-400 mt-0.5">Match History</div>
                    </div>
                </a>
            </div>

            <!-- Fast Action Button -->
            <div class="p-4 border-b border-gray-100">
                <button 
                    type="button" 
                    onclick="triggerCreateMatchModal()" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg text-sm shadow-sm transition text-center flex items-center justify-center gap-1.5 cursor-pointer"
                >
                    <span>+ Ghi nhận trận đấu</span>
                </button>
            </div>

            <!-- Vertical Navigation Links (Không có icon gây thò thụt) -->
            <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto">
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3.5 mb-2">Menu chính</div>

                <a href="{{ route('calendar') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('calendar') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Lịch thi đấu (Calendar)
                </a>

                <a href="{{ route('dashboard') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Bảng điều khiển (Dashboard)
                </a>

                <a href="{{ route('matches.index') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('matches.index') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Lịch sử các trận đấu
                </a>

                <a href="{{ route('leaderboard') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('leaderboard') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Bảng xếp hạng
                </a>

                <a href="{{ route('members.index') }}" class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('members.*') ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Danh sách cơ thủ
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="pt-3 border-t border-gray-100 mt-2">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3.5 mb-2">Quản lý</div>
                        <a href="{{ route('admin.index') }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'bg-amber-50 text-amber-800 font-semibold' : 'text-amber-700 hover:bg-amber-50' }}">
                            <span>Trang quản trị</span>
                            @php $disputeCount = \App\Models\BilliardMatch::where('status', 'disputed')->count(); @endphp
                            @if($disputeCount > 0)
                                <span class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $disputeCount }}</span>
                            @endif
                        </a>
                    </div>
                @endif
            </nav>

            <!-- User Footer in Sidebar -->
            <div class="p-3.5 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <a href="{{ route('members.show', auth()->user()->username) }}" class="flex items-center gap-3 overflow-hidden group">
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="w-9 h-9 rounded-full border border-gray-200 object-cover shrink-0">
                        <div class="overflow-hidden">
                            <div class="text-sm font-bold text-gray-900 group-hover:text-blue-600 truncate leading-tight">{{ auth()->user()->name }}</div>
                            <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 mt-0.5 text-xs">
                                <span class="text-green-700 font-bold">{{ auth()->user()->wins_count }}W</span>
                                <span class="text-gray-300">•</span>
                                <span class="text-red-600 font-bold">{{ auth()->user()->losses_count }}L</span>
                                <span class="text-gray-300">•</span>
                                @if(auth()->user()->unpaid_debts_count > 0)
                                    <span class="text-amber-700 font-bold" title="Kèo bạn nợ">Nợ {{ auth()->user()->unpaid_debts_count }}</span>
                                    <span class="text-gray-300">•</span>
                                @endif
                                <span class="text-blue-700 font-medium" title="Kèo đối thủ chưa trả bạn">Nhận {{ auth()->user()->unpaid_receivables_count }}</span>
                            </div>
                        </div>
                    </a>

                    <div class="flex items-center gap-1">
                        <button 
                            type="button" 
                            onclick="openChangePasswordModal()" 
                            title="Đổi mật khẩu" 
                            class="text-gray-400 hover:text-blue-600 p-2 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                        >
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Đăng xuất" class="text-gray-400 hover:text-red-600 p-2 rounded-lg hover:bg-gray-100 transition cursor-pointer">
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Right Side: Header + Content -->
        <div class="md:pl-64 flex flex-col flex-1 min-h-screen">
            
            <!-- Top Bar -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Mobile toggle button & brand -->
                <div class="flex items-center gap-3">
                    <button type="button" @click="mobileMenuOpen = true" class="md:hidden text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100 cursor-pointer" aria-label="Mở menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <div class="md:hidden flex items-center gap-2">
                        @include('partials.app-logo', ['size' => 'sm'])
                        <span class="font-bold text-base text-gray-900">Bida Nội Bộ</span>
                    </div>
                </div>

                <div class="hidden md:flex items-center text-sm font-medium text-gray-500 gap-3">
                    <span class="hidden 2xl:inline text-xs text-gray-400">Quản lý bida nội bộ</span>
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-semibold transition shadow-2xs" title="Xem chi tiết tại Dashboard">
                        <div class="flex items-center gap-1.5">
                            <span class="text-green-700 font-bold">{{ auth()->user()->wins_count }} Thắng</span>
                            <span class="text-gray-300">/</span>
                            <span class="text-red-600 font-bold">{{ auth()->user()->losses_count }} Thua</span>
                            <span class="text-gray-400 font-normal">({{ auth()->user()->win_rate }}%)</span>
                        </div>
                        <span class="text-gray-300">|</span>
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->unpaid_debts_count > 0)
                                <span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded font-bold" title="Kèo bạn cần trả cho đối thủ">
                                    Nợ: {{ auth()->user()->unpaid_debts_count }}
                                </span>
                            @else
                                <span class="text-gray-400 font-normal" title="Bạn không nợ kèo nào">0 nợ</span>
                            @endif

                            <span class="text-gray-300">•</span>

                            @if(auth()->user()->unpaid_receivables_count > 0)
                                <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded font-bold" title="Kèo đối thủ chưa trả cho bạn">
                                    Chờ nhận: {{ auth()->user()->unpaid_receivables_count }}
                                </span>
                            @else
                                <span class="text-gray-400 font-normal" title="Không có kèo chờ nhận">0 chờ nhận</span>
                            @endif

                            <span class="text-gray-300">•</span>
                            <span class="text-gray-500 font-medium" title="Kèo bạn đã thanh toán xong">Đã trả: <b class="text-gray-800">{{ auth()->user()->paid_debts_count }}</b></span>
                        </div>
                    </a>
                </div>

                <!-- Right items: Realtime Notification Bell & Quick links -->
                <div class="flex items-center gap-2.5 ml-auto">
                    <button 
                        type="button" 
                        onclick="triggerCreateMatchModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-3.5 rounded-lg text-sm shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                    >
                        <span>+ Ghi nhận trận</span>
                    </button>
                    <!-- Icon hỏi chấm hướng dẫn sử dụng Telegram Bot -->
                    <button 
                        type="button" 
                        onclick="openTelegramHelpModal()" 
                        title="Hướng dẫn sử dụng Telegram Bot ghi trận"
                        class="w-9 h-9 rounded-lg border border-gray-300 hover:border-gray-400 bg-white hover:bg-gray-50 text-gray-600 hover:text-blue-600 flex items-center justify-center font-bold text-sm shadow-xs transition cursor-pointer shrink-0"
                    >
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    <livewire:realtime-notification-bell />
                </div>
            </header>

            <!-- Flash alerts -->
            <div class="px-4 sm:px-6 lg:px-8 pt-4 w-full max-w-7xl">
                @if(session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 p-3.5 mb-3 rounded-r-lg text-sm text-green-800 flex items-center justify-between shadow-sm">
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-50 border-l-4 border-red-500 p-3.5 mb-3 rounded-r-lg text-sm text-red-800 flex items-center justify-between shadow-sm">
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <!-- Page Main Content Slot -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            <!-- Minimal Footer -->
            <footer class="bg-white border-t border-gray-200 py-4 px-6 text-sm text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>Bida Nội Bộ • Tự động chốt kết quả sau 24h</div>
                <div class="text-gray-600">Telegram Bot: <code class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-800 font-mono text-xs">/an binh</code></div>
            </footer>
        </div>

    </div>

    <!-- Fast Pure AJAX Create Match Modal -->
    @include('partials.ajax-create-match-modal')

    <!-- Create Member Modal (Admin Only) -->
    @include('partials.create-member-modal')

    <!-- Telegram Help Modal -->
    @include('partials.telegram-help-modal')

    <!-- Change Password Modal -->
    @include('partials.change-password-modal')

    @livewireScripts
</body>
</html>
