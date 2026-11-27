@if($results)
    <span class="sr-only">Kết quả gần nhất từ cũ đến mới</span>
    <ul class="inline-flex flex-wrap justify-center gap-1 align-middle">
        @foreach($results as $result)
            <li class="grid size-5 place-items-center rounded text-xs font-semibold {{ $result === 'win' ? 'bg-green-50 text-green-800' : 'bg-primary-soft text-primary' }}"
                title="{{ $result === 'win' ? 'Thắng' : 'Thua' }}">
                <span aria-hidden="true">{{ $result === 'win' ? 'T' : 'B' }}</span>
                <span class="sr-only">{{ $result === 'win' ? 'Thắng' : 'Thua' }}</span>
            </li>
        @endforeach
    </ul>
@else
    <span class="text-gray-500">—</span>
@endif
