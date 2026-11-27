@php
    $logoUrl = \App\Models\Setting::getLogoUrl();
    $logoSizeClass = match ($size ?? 'md') {
        'lg' => 'h-12 w-auto',
        'sm' => 'h-7 w-auto',
        default => 'h-8 w-auto',
    };
@endphp

<img src="{{ $logoUrl ?: asset('logo-bida-noi-bo.png') }}" alt="Logo Bida Nội Bộ"
    @if(!$logoUrl) width="192" height="192" @endif
    class="shrink-0 object-contain {{ $class ?? $logoSizeClass }}">
