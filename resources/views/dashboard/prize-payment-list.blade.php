<ul class="divide-y divide-gray-100 border-t border-gray-100">
    @foreach($paymentMatches as $debtMatch)
        @php
            $userSlot = $debtMatch->getPrizeSlotForUser($dashboardUser);
            $userStatus = $debtMatch->getPrizeStatusForUser($dashboardUser);
            $counterpart = $debtMatch->getCounterpartForUser($dashboardUser);
            $userNotes = $debtMatch->getPrizeNotesForUser($dashboardUser);
            $userFeedback = $debtMatch->getPrizeFeedbackForUser($dashboardUser);
            $isWaiting = $userStatus === 'paid';
            $recipient = $debtMatch->isDoubles() ? $counterpart?->name : null;
            $paymentLabel = match ($userStatus) {
                'paid' => 'Cập nhật trả kèo',
                'rejected' => $recipient ? 'Trả kèo lại cho '.$recipient : 'Trả kèo lại',
                default => $recipient ? 'Trả kèo cho '.$recipient : 'Trả kèo ngay',
            };
            $confirmation = $isWaiting
                ? "Xác nhận cập nhật kèo '{$debtMatch->prize}' đã trả cho ".($recipient ?? $debtMatch->winner_names).'?'
                : "Xác nhận bạn đã trả kèo '{$debtMatch->prize}'".($recipient ? ' cho '.$recipient : '').'?';
        @endphp
        <li class="flex flex-col gap-2.5 px-3 py-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0 space-y-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p class="text-sm font-semibold text-gray-900 wrap-anywhere">
                        @if($debtMatch->isDoubles())
                            Bạn nợ riêng: {{ $counterpart?->name }}
                        @else
                            Trả cho {{ $debtMatch->winner_names }}
                        @endif
                    </p>
                    @if($userStatus === 'rejected')
                        <span class="rounded-md bg-red-50 px-1.5 py-0.5 text-xs font-medium text-red-800">Người nhận không đồng ý</span>
                    @elseif(!$isWaiting)
                        <span class="rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-900">Chưa trả</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-gray-600">
                    <span class="font-medium text-gray-800 wrap-anywhere">{{ $debtMatch->prize }}</span>
                    <time datetime="{{ $debtMatch->played_at->toDateString() }}" class="tabular-nums">{{ $debtMatch->played_at->format('d/m/Y') }}</time>
                </div>
                @if($debtMatch->isDoubles())
                    <p class="text-xs text-gray-600 wrap-anywhere">Kèo đôi tách chéo · {{ $debtMatch->team1_names }} vs {{ $debtMatch->team2_names }}</p>
                @endif
                @if($userNotes)
                    <p class="text-xs text-gray-600 wrap-anywhere">{{ $userNotes }}</p>
                @endif
                @if($userStatus === 'rejected' && $userFeedback)
                    <p class="text-xs text-red-800 wrap-anywhere">Lý do không đồng ý: {{ $userFeedback }}</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:max-w-[45%] sm:justify-end">
                <form method="POST" action="{{ route('matches.pay-prize', $debtMatch->id) }}"
                    @unless($isWaiting) onsubmit="return confirm(@js($confirmation))" @endunless>
                    @csrf
                    <input type="hidden" name="pair_slot" value="{{ $userSlot }}">
                    <button type="{{ $isWaiting ? 'button' : 'submit' }}"
                        @if($isWaiting) data-confirm-payment="{{ $confirmation }}" @endif
                        class="min-h-9 max-w-full cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold wrap-anywhere transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary {{ $isWaiting ? 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50' : 'bg-primary text-white hover:bg-primary-hover' }}">
                        {{ $paymentLabel }}
                    </button>
                </form>
                <a href="{{ route('matches.show', $debtMatch->id) }}" class="inline-flex min-h-9 items-center rounded-lg px-2 text-xs font-medium text-primary hover:bg-primary-soft focus-visible:outline-2 focus-visible:outline-primary">Chi tiết</a>
            </div>
        </li>
    @endforeach
</ul>
