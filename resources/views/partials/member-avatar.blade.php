@php($name = str($user->name)->squish())

<span data-member-avatar aria-hidden="true" class="grid size-9 shrink-0 place-items-center rounded-full bg-primary text-xs font-bold text-white">
    {{ $name->contains(' ') ? $name->initials(true)->substr(0, 2) : $name->substr(0, 2)->upper() }}
</span>
