@extends('layouts.app', ['title' => 'Danh sách cơ thủ - Bida Nội Bộ', 'fullWidth' => true, 'compactTop' => true])

@section('content')
    <div class="space-y-4" x-data="{
        profile: { badges: [] },
        profileUrl: '',
        openProfile(event, profile) {
            const dialog = this.$refs.profileDialog;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || typeof dialog.showModal !== 'function') return;

            event.preventDefault();
            const link = event.currentTarget;
            const avatar = link.closest('tr').querySelector('[data-member-avatar]');
            this.profile = profile;
            this.profileUrl = link.href;
            this.$refs.profileAvatar.replaceChildren(avatar.cloneNode(true));

            this.$nextTick(() => {
                if (!dialog.open) dialog.showModal();
                dialog.scrollTop = 0;
            });
        }
    }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 id="members-heading" class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Danh sách cơ thủ</h1>

            @auth
                @if(auth()->user()->isAdmin())
                    <button type="button" onclick="openCreateMemberModal()"
                        class="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg bg-primary px-3.5 text-sm font-semibold text-white transition hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="M12 5v14M5 12h14" />
                        </svg>
                        Thêm cơ thủ
                    </button>
                @endif
            @endauth
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs">
            <table aria-labelledby="members-heading" class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-600">
                    <tr>
                        <th scope="col" class="px-3 py-3 sm:px-4">Cơ thủ</th>
                        <th scope="col" class="hidden px-3 py-3 text-right lg:table-cell">Trận</th>
                        <th scope="col" class="hidden px-3 py-3 text-right lg:table-cell">Thắng</th>
                        <th scope="col" class="hidden px-3 py-3 text-right lg:table-cell">Thua</th>
                        <th scope="col" class="px-1.5 py-3 text-right whitespace-nowrap sm:px-3">Tỷ lệ<span class="hidden sm:inline"> thắng</span></th>
                        <th scope="col" class="hidden px-3 py-3 xl:table-cell">Danh hiệu</th>
                        <th scope="col" class="px-3 py-3 text-right whitespace-nowrap sm:px-4">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($membersWithStats as $item)
                        @php
                            $member = $item['user'];
                            $stats = $item['stats'];
                            $profileUrl = route('members.show', $member->username);
                            $profilePreview = [
                                'name' => $member->name,
                                'username' => '@'.$member->username,
                                'joined' => $member->created_at?->format('d/m/Y'),
                                'totalMatches' => $stats['total_matches'],
                                'wins' => $stats['wins'],
                                'losses' => $stats['losses'],
                                'winRate' => $stats['win_rate'].'%',
                                'badges' => array_values(array_unique(array_column($stats['badges'], 'name'))),
                            ];
                        @endphp
                        <tr class="transition-colors hover:bg-gray-50/75">
                            <th scope="row" class="px-3 py-3 font-normal sm:px-4">
                                <div class="flex items-center gap-2 sm:gap-3">
                                    @include('partials.member-avatar', ['user' => $member])
                                    <div class="min-w-0">
                                        <a href="{{ $profileUrl }}"
                                            class="rounded-sm font-semibold text-gray-900 wrap-anywhere hover:text-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                                            {{ $member->name }}
                                        </a>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                            <span class="wrap-anywhere">{{ '@'.$member->username }}</span>
                                            @if($member->isAdmin())
                                                <span class="rounded-md bg-amber-50 px-1.5 py-0.5 font-medium text-amber-800">Admin</span>
                                            @endif
                                            @if($member->telegram_id)
                                                <span class="rounded-md bg-blue-50 px-1.5 py-0.5 font-medium text-blue-800">Telegram</span>
                                            @endif
                                        </div>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-gray-600 tabular-nums lg:hidden">
                                            <span>{{ $stats['total_matches'] }} trận</span>
                                            <span class="text-green-700">{{ $stats['wins'] }} thắng</span>
                                            <span class="text-red-700">{{ $stats['losses'] }} thua</span>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            <td class="hidden px-3 py-3 text-right font-medium text-gray-700 tabular-nums lg:table-cell">{{ $stats['total_matches'] }}</td>
                            <td class="hidden px-3 py-3 text-right font-semibold text-green-700 tabular-nums lg:table-cell">{{ $stats['wins'] }}</td>
                            <td class="hidden px-3 py-3 text-right font-semibold text-red-700 tabular-nums lg:table-cell">{{ $stats['losses'] }}</td>
                            <td class="px-1.5 py-3 text-right text-xs font-semibold text-gray-900 tabular-nums sm:px-3 sm:text-sm">{{ $stats['win_rate'] }}%</td>
                            <td class="hidden px-3 py-3 xl:table-cell">
                                <div class="flex max-w-56 flex-wrap gap-1.5">
                                    @forelse(array_slice($stats['badges'], 0, 3) as $badge)
                                        <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">{{ $badge['name'] }}</span>
                                    @empty
                                        <span class="text-gray-400" aria-label="Chưa có danh hiệu">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right sm:px-4">
                                <a href="{{ $profileUrl }}" aria-label="Xem hồ sơ của {{ $member->name }}"
                                    aria-haspopup="dialog" aria-controls="member-profile-dialog" @click="openProfile($event, @js($profilePreview))"
                                    class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-primary-soft px-2 text-xs font-semibold whitespace-nowrap text-primary transition hover:bg-primary hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:px-3">
                                    Hồ sơ
                                    <svg class="hidden size-3.5 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">Chưa có cơ thủ nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <dialog id="member-profile-dialog" x-ref="profileDialog" aria-labelledby="member-profile-name" @click.self="$el.close()"
            class="fixed inset-x-0 top-auto bottom-2 mx-auto h-fit max-h-[calc(100dvh-1rem)] w-[calc(100%-1rem)] max-w-md overflow-y-auto overscroll-contain rounded-2xl border border-gray-200 bg-white p-0 shadow-2xl backdrop:bg-gray-950/55 motion-safe:open:animate-match-dialog motion-safe:backdrop:animate-match-backdrop sm:inset-0 sm:m-auto sm:max-h-[calc(100dvh-3rem)]">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-4 sm:px-5">
                <div class="flex min-w-0 items-center gap-3">
                    <div x-ref="profileAvatar" class="shrink-0"></div>
                    <div class="min-w-0">
                        <h2 id="member-profile-name" class="text-lg font-bold text-gray-900 wrap-anywhere" x-text="profile.name"></h2>
                        <p class="text-sm text-gray-500 wrap-anywhere" x-text="profile.username"></p>
                    </div>
                </div>
                <form method="dialog">
                    <button type="submit" aria-label="Đóng hồ sơ" autofocus
                        class="grid size-9 cursor-pointer place-items-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-primary">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" d="m6 6 12 12M18 6 6 18" />
                        </svg>
                    </button>
                </form>
            </div>

            <div class="space-y-4 px-4 py-4 sm:px-5">
                <dl class="grid grid-cols-4 divide-x divide-gray-200 rounded-xl border border-gray-200 bg-gray-50 py-3 text-center tabular-nums">
                    <div class="px-1">
                        <dt class="text-xs text-gray-600">Trận</dt>
                        <dd class="mt-0.5 text-base font-bold text-gray-900" x-text="profile.totalMatches"></dd>
                    </div>
                    <div class="px-1">
                        <dt class="text-xs text-gray-600">Thắng</dt>
                        <dd class="mt-0.5 text-base font-bold text-green-700" x-text="profile.wins"></dd>
                    </div>
                    <div class="px-1">
                        <dt class="text-xs text-gray-600">Thua</dt>
                        <dd class="mt-0.5 text-base font-bold text-red-700" x-text="profile.losses"></dd>
                    </div>
                    <div class="px-1">
                        <dt class="text-xs text-gray-600">Tỷ lệ thắng</dt>
                        <dd class="mt-0.5 text-base font-bold text-gray-900" x-text="profile.winRate"></dd>
                    </div>
                </dl>

                <div x-show="profile.badges.length">
                    <h3 class="mb-2 text-sm font-semibold text-gray-800">Danh hiệu</h3>
                    <ul class="flex flex-wrap gap-1.5">
                        <template x-for="badge in profile.badges" :key="badge">
                            <li class="rounded-md bg-primary-soft px-2 py-1 text-xs font-medium text-primary" x-text="badge"></li>
                        </template>
                    </ul>
                </div>
                <p x-show="profile.joined" class="text-xs text-gray-600">Gia nhập <span x-text="profile.joined"></span></p>
            </div>

            <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-4 py-3 sm:px-5">
                <a :href="profileUrl"
                    class="inline-flex min-h-9 items-center rounded-lg bg-primary px-3 text-sm font-semibold text-white transition hover:bg-primary-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                    Xem hồ sơ đầy đủ
                </a>
            </div>
        </dialog>
    </div>
@endsection
