<!DOCTYPE html>
<html lang="vi" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Quản Lý Lịch Sử Đấu Bida Nội Bộ' }}</title>
    @include('partials.favicon')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full text-gray-900 antialiased" x-data="{ mobileMenuOpen: false }">
    @php
        $matchesMenuActive = request()->routeIs('calendar', 'matches.*') || request()->is('calendar');
    @endphp

    <div class="min-h-full">

        <!-- Mobile Sidebar Backdrop & Menu -->
        <div x-show="mobileMenuOpen" class="relative z-50 md:hidden" style="display: none;" role="dialog"
            aria-modal="true">
            <div x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-linear duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/60" @click="mobileMenuOpen = false">
            </div>

            <div class="fixed inset-0 flex">
                <div x-show="mobileMenuOpen" x-transition:enter="transition ease-in-out duration-200 transform"
                    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-200 transform"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                    class="relative mr-16 flex w-full max-w-xs flex-1">

                    <div class="flex flex-col grow gap-y-4 overflow-y-auto bg-white px-5 pb-4">
                        <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200">
                            <a href="{{ route('calendar') }}"
                                class="flex items-center gap-2.5 font-bold text-gray-900 text-base">
                                @include('partials.app-logo', ['size' => 'sm'])
                                <span>Bida Nội Bộ</span>
                            </a>
                            <button type="button" @click="mobileMenuOpen = false"
                                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-md hover:bg-gray-100">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            @auth
                                <button type="button" onclick="openSendChallengeModal(); mobileMenuOpen = false;"
                                    class="bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 text-white font-extrabold py-2.5 px-3 rounded-lg text-xs shadow-sm transition text-center cursor-pointer shrink-0">
                                    ⚔️ Khiêu chiến
                                </button>
                            @endauth
                            <button type="button" onclick="openTelegramHelpModal(); mobileMenuOpen = false;"
                                title="Hướng dẫn sử dụng Telegram Bot"
                                class="w-10 h-10 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 text-gray-600 hover:text-primary flex items-center justify-center font-bold text-sm shadow-xs shrink-0 cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg>
                            </button>
                        </div>

                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-1">
                                <li>
                                    <a href="{{ route('dashboard') }}"
                                        class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Bảng điều khiển
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('calendar') }}"
                                        class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ $matchesMenuActive ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Trận đấu
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('leaderboard') }}"
                                        class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('leaderboard') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Bảng xếp hạng
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('members.index') }}"
                                        class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('members.*') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100' }}">
                                        Cơ thủ
                                    </a>
                                </li>
                                @if(auth()->user()->isAdmin())
                                    <li class="pt-3 border-t border-gray-100 mt-2">
                                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3 mb-2">Quản
                                            lý</div>
                                        <a href="{{ route('admin.index') }}"
                                            class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-primary' }}">
                                            <span>Trang quản trị</span>
                                            @php $disputeCount = \App\Models\BilliardMatch::where('status', 'disputed')->count(); @endphp
                                            @if($disputeCount > 0)
                                                <span
                                                    class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $disputeCount }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endif
                            </ul>

                            <!-- Mobile User Profile -->
                            <div class="mt-auto border-t border-gray-100 pt-4">
                                @include('partials.sidebar-account', ['user' => auth()->user()])
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop Sidebar Menu (Menu Dọc Cố Định) -->
        <aside
            class="hidden md:fixed md:inset-y-0 md:z-40 md:flex md:w-64 md:flex-col bg-white border-r border-gray-200">
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

            <!-- Vertical Navigation Links (Không có icon gây thò thụt) -->
            <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}"
                    class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Bảng điều khiển
                </a>

                <a href="{{ route('calendar') }}"
                    class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ $matchesMenuActive ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Trận đấu
                </a>

                <a href="{{ route('leaderboard') }}"
                    class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('leaderboard') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Bảng xếp hạng
                </a>

                <a href="{{ route('members.index') }}"
                    class="block px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('members.*') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                    Cơ thủ
                </a>

                @if(auth()->user()->isAdmin())
                    <div class="pt-3 border-t border-gray-100 mt-2">
                        <div class="text-xs font-bold text-gray-400 uppercase tracking-wider px-3.5 mb-2">Quản lý</div>
                        <a href="{{ route('admin.index') }}"
                            class="flex items-center justify-between px-3.5 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('admin.*') ? 'bg-primary-soft text-primary font-semibold' : 'text-gray-700 hover:bg-gray-100 hover:text-primary' }}">
                            <span>Trang quản trị</span>
                            @php $disputeCount = \App\Models\BilliardMatch::where('status', 'disputed')->count(); @endphp
                            @if($disputeCount > 0)
                                <span
                                    class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $disputeCount }}</span>
                            @endif
                        </a>
                    </div>
                @endif
            </nav>

            <!-- User Footer in Sidebar -->
            <div class="border-t border-gray-200 p-3.5">
                @include('partials.sidebar-account', ['user' => auth()->user()])
            </div>
        </aside>

        <!-- Right Side: Header + Content -->
        <div class="md:pl-64 flex flex-col flex-1 min-h-screen">

            <!-- Top Bar -->
            <header
                class="sticky top-0 z-30 flex h-16 items-center gap-1.5 border-b border-gray-200 bg-white px-3 sm:gap-3 sm:px-6 lg:px-8">
                <!-- Mobile toggle button & brand -->
                <div class="flex min-w-0 items-center gap-1 md:hidden">
                    <button type="button" @click="mobileMenuOpen = true"
                        class="grid size-10 shrink-0 cursor-pointer place-items-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-primary"
                        aria-label="Mở menu">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div class="flex min-w-0 items-center gap-2">
                        @include('partials.app-logo', ['size' => 'sm'])
                        <span class="hidden truncate text-sm font-bold text-gray-900 min-[375px]:inline">Bida Nội Bộ</span>
                    </div>
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-1.5 sm:gap-2 md:ml-0">
                    <button type="button" onclick="triggerCreateMatchModal()" aria-label="Ghi nhận trận" title="Ghi nhận trận"
                        class="flex h-9 min-w-9 cursor-pointer items-center justify-center gap-1.5 rounded-lg bg-primary text-sm font-semibold text-white shadow-xs transition hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:px-3.5">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                        </svg>
                        <span class="hidden sm:inline">Ghi nhận trận</span>
                    </button>
                    <button type="button" onclick="openSendChallengeModal()" aria-label="Khiêu chiến" title="Khiêu chiến"
                        class="flex h-9 min-w-9 cursor-pointer items-center justify-center gap-1.5 rounded-lg border border-primary/20 bg-white text-sm font-semibold text-primary transition hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:px-3.5">
                        <span aria-hidden="true">⚔️</span>
                        <span class="hidden sm:inline">Khiêu chiến</span>
                    </button>
                </div>

                <div class="flex shrink-0 items-center gap-1.5 sm:gap-2 md:ml-auto">
                    @php
                        $unpaidDebtsCount = auth()->user()->unpaid_debts_count;
                        $unpaidReceivablesCount = auth()->user()->unpaid_receivables_count;
                    @endphp
                    @if($unpaidDebtsCount > 0 || $unpaidReceivablesCount > 0)
                        <a href="{{ route('dashboard') }}#tong-quan-keo"
                            class="mr-1 hidden h-9 items-center gap-2 rounded-lg border border-gray-200 px-2.5 text-xs font-semibold whitespace-nowrap transition hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary lg:flex"
                            title="Xem tình trạng kèo trong Bảng điều khiển">
                            @if($unpaidDebtsCount > 0)
                                <span class="text-amber-800">{{ $unpaidDebtsCount }} kèo cần trả</span>
                            @endif
                            @if($unpaidDebtsCount > 0 && $unpaidReceivablesCount > 0)
                                <span class="h-3.5 w-px bg-gray-200" aria-hidden="true"></span>
                            @endif
                            @if($unpaidReceivablesCount > 0)
                                <span class="text-blue-800">{{ $unpaidReceivablesCount }} kèo chờ nhận</span>
                            @endif
                        </a>
                    @endif

                    <button type="button" onclick="openTelegramHelpModal()"
                        aria-label="Hướng dẫn ghi trận qua Telegram" title="Hướng dẫn ghi trận qua Telegram"
                        class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </button>
                    @include('partials.ajax-notification-bell')
                </div>
            </header>

            <!-- Flash alerts -->
            @if(!($compactTop ?? false) || session('success') || session('error'))
                <div class="px-4 sm:px-6 lg:px-8 pt-4 w-full {{ ($fullWidth ?? false) || request()->routeIs('dashboard') ? '' : 'max-w-7xl' }}">
                    @if(session('success'))
                        <div
                            class="bg-green-50 border-l-4 border-green-500 p-3.5 mb-3 rounded-r-lg text-sm text-green-800 flex items-center justify-between shadow-sm">
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div
                            class="bg-red-50 border-l-4 border-red-500 p-3.5 mb-3 rounded-r-lg text-sm text-red-800 flex items-center justify-between shadow-sm">
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Page Main Content Slot -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 w-full {{ ($fullWidth ?? false) || request()->routeIs('dashboard') ? '' : 'max-w-7xl' }} {{ ($compactTop ?? false) ? 'pt-3 sm:pt-4 lg:pt-4' : '' }}">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

            <!-- Minimal Footer -->
            <footer
                class="bg-white border-t border-gray-200 py-4 px-6 text-sm text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-2">
                <div>Bida Nội Bộ • Tự động chốt kết quả sau 24h</div>
                <div class="text-gray-600">Telegram Bot: <code
                        class="bg-gray-100 px-1.5 py-0.5 rounded text-gray-800 font-mono text-xs">/an binh</code></div>
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

    @auth
        @include('matches.partials.match-detail-dialog')
        @include('partials.ajax-duel-challenge')
        @include('partials.ajax-side-bet-modal')
    @endauth

    @include('partials.wifi-restriction')

    @livewireScripts
</body>

</html>
