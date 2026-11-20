<!DOCTYPE html>
<html lang="vi" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Đăng nhập' }}</title>

    <!-- Native System Font for 0ms lag -->
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full text-gray-900 antialiased flex flex-col justify-center py-10 px-4 sm:px-6">

    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 text-xs sm:text-sm px-3.5 py-2.5 rounded-lg mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs sm:text-sm px-3.5 py-2.5 rounded-lg mb-4">
                {{ session('error') }}
            </div>
        @endif

        @if(session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 text-xs sm:text-sm px-3.5 py-2.5 rounded-lg mb-4">
                {{ session('info') }}
            </div>
        @endif

        @yield('content')
    </div>

</body>
</html>
