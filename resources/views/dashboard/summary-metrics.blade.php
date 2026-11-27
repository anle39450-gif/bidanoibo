<dl class="grid grid-cols-2 gap-px bg-gray-100 tabular-nums {{ $columns ?? '' }}">
    @foreach($metrics as [$label, $value, $iconColor, $valueColor, $icon])
        <div class="group relative min-w-0 bg-white py-3.5 pr-2 pl-14 transition-colors hover:bg-gray-50 sm:py-4 sm:pl-16">
            <dt class="text-xs leading-tight text-gray-600">
                <span class="absolute top-1/2 left-3 grid size-8 -translate-y-1/2 place-items-center rounded-lg motion-safe:transition-transform motion-safe:group-hover:-translate-y-[55%] motion-safe:group-hover:scale-105 sm:left-4 sm:size-9 {{ $iconColor }}">
                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                    </svg>
                </span>
                {{ $label }}
            </dt>
            <dd class="mt-1 text-lg leading-tight font-bold sm:text-xl {{ $valueColor }}">{{ $value }}</dd>
        </div>
    @endforeach
</dl>
