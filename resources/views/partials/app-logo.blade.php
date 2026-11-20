@php
    $logoUrl = \App\Models\Setting::getLogoUrl();
    $size = $size ?? 'md';
    $customClass = $class ?? '';
@endphp

@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="Logo" class="{{ $customClass ?: ($size === 'lg' ? 'h-12 w-auto max-h-12 object-contain' : ($size === 'sm' ? 'h-6 w-auto max-h-6 object-contain' : 'h-8 w-auto max-h-8 object-contain')) }}">
@else
    @if($size === 'lg')
        <div class="w-12 h-12 rounded-xl bg-blue-600 text-white font-bold flex items-center justify-center text-lg shadow-xs select-none {{ $customClass }}">
            8B
        </div>
    @elseif($size === 'sm')
        <div class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center text-xs shadow-xs select-none {{ $customClass }}">
            8B
        </div>
    @else
        <div class="w-8 h-8 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center text-sm shadow-xs select-none {{ $customClass }}">
            8B
        </div>
    @endif
@endif
