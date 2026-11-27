<div class="flex items-center justify-between gap-2">
    <a href="{{ route('members.show', $user->username) }}"
        class="group flex min-w-0 items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
        @include('partials.member-avatar', ['user' => $user])
        <span class="truncate text-sm font-semibold text-gray-900 transition group-hover:text-primary">
            {{ $user->name }}
        </span>
    </a>

    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
        @csrf
        <button type="button" data-confirm-logout aria-label="Đăng xuất" title="Đăng xuất"
            class="grid size-9 cursor-pointer place-items-center rounded-lg bg-primary-soft text-primary transition hover:bg-primary hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </button>
    </form>
</div>
