<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BilliardMatch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'match_type',
        'player1_id',
        'player1_partner_id',
        'player2_id',
        'player2_partner_id',
        'winner_id',
        'winner_partner_id',
        'loser_id',
        'loser_partner_id',
        'played_at',
        'prize',
        'prize_status',
        'prize_paid_at',
        'prize_paid_by',
        'prize_notes',
        'prize_confirmed_at',
        'prize_confirmed_by',
        'prize_feedback',
        'prize_partner_status',
        'prize_partner_paid_at',
        'prize_partner_paid_by',
        'prize_partner_notes',
        'prize_partner_confirmed_at',
        'prize_partner_confirmed_by',
        'prize_partner_feedback',
        'notes',
        'evidence_path',
        'status',
        'is_hidden',
        'notified_at',
        'auto_confirm_at',
        'confirmed_at',
        'confirmed_by',
        'disputed_at',
        'dispute_reason',
        'dispute_evidence_path',
        'admin_notes',
        'created_from',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'prize_paid_at' => 'datetime',
            'prize_confirmed_at' => 'datetime',
            'prize_partner_paid_at' => 'datetime',
            'prize_partner_confirmed_at' => 'datetime',
            'notified_at' => 'datetime',
            'auto_confirm_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'disputed_at' => 'datetime',
            'is_hidden' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }

    public function player1Partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_partner_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }

    public function player2Partner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_partner_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function winnerPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_partner_id');
    }

    public function loser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_id');
    }

    public function loserPartner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'loser_partner_id');
    }

    public function prizePaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prize_paid_by');
    }

    public function prizeConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prize_confirmed_by');
    }

    public function prizePartnerPaidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prize_partner_paid_by');
    }

    public function prizePartnerConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prize_partner_confirmed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MatchHistory::class, 'billiard_match_id')->orderBy('created_at', 'desc');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'billiard_match_id');
    }

    // Scopes
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->whereIn('status', ['confirmed', 'resolved'])->where('is_hidden', false);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeDisputed(Builder $query): Builder
    {
        return $query->where('status', 'disputed');
    }

    public function scopePrizePaid(Builder $query): Builder
    {
        return $query->whereIn('prize_status', ['paid', 'confirmed']);
    }

    public function scopePrizeUnpaid(Builder $query): Builder
    {
        return $query->whereNotIn('prize_status', ['paid', 'confirmed']);
    }

    public function scopePrizeConfirmed(Builder $query): Builder
    {
        return $query->where('prize_status', 'confirmed');
    }

    public function scopePrizeRejected(Builder $query): Builder
    {
        return $query->where('prize_status', 'rejected');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_hidden', false);
    }

    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('is_hidden', true);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function isHidden(): bool
    {
        return (bool) $this->is_hidden;
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isUserDebtor(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->id, array_filter([
            $this->loser_id,
            $this->loser_partner_id,
        ]));
    }

    public function isUserRecipient(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array($user->id, array_filter([
            $this->winner_id,
            $this->winner_partner_id,
        ]));
    }

    public function isPrizePaid(?string $slot = null): bool
    {
        if ($slot === 'partner') {
            return in_array($this->prize_partner_status, ['paid', 'confirmed']);
        }

        if ($slot === 'primary') {
            return in_array($this->prize_status, ['paid', 'confirmed']);
        }

        if ($this->isDoubles()) {
            return in_array($this->prize_status, ['paid', 'confirmed'])
                && in_array($this->prize_partner_status, ['paid', 'confirmed']);
        }

        return in_array($this->prize_status, ['paid', 'confirmed']);
    }

    public function isPrizeConfirmed(?string $slot = null): bool
    {
        if ($slot === 'partner') {
            return $this->prize_partner_status === 'confirmed';
        }

        if ($slot === 'primary') {
            return $this->prize_status === 'confirmed';
        }

        if ($this->isDoubles()) {
            return $this->prize_status === 'confirmed' && $this->prize_partner_status === 'confirmed';
        }

        return $this->prize_status === 'confirmed';
    }

    public function isPrizeRejected(?string $slot = null): bool
    {
        if ($slot === 'partner') {
            return $this->prize_partner_status === 'rejected';
        }

        if ($slot === 'primary') {
            return $this->prize_status === 'rejected';
        }

        return $this->prize_status === 'rejected';
    }

    public function isPrizePendingConfirmation(?string $slot = null): bool
    {
        if ($slot === 'partner') {
            return $this->prize_partner_status === 'paid';
        }

        if ($slot === 'primary') {
            return $this->prize_status === 'paid';
        }

        return $this->prize_status === 'paid';
    }

    public function getPrizeSlotForUser(User|int|null $user): string
    {
        if (! $user) {
            return 'primary';
        }

        $id = $user instanceof User ? $user->id : $user;

        if ($this->isDoubles() && ($id === $this->loser_partner_id || $id === $this->winner_partner_id)) {
            return 'partner';
        }

        return 'primary';
    }

    public function getCounterpartForUser(User|int|null $user): ?User
    {
        if (! $user) {
            return null;
        }

        $id = $user instanceof User ? $user->id : $user;

        if ($id === $this->loser_id) {
            return $this->winner;
        }

        if ($this->isDoubles() && $id === $this->loser_partner_id) {
            return $this->winnerPartner;
        }

        if ($id === $this->winner_id) {
            return $this->loser;
        }

        if ($this->isDoubles() && $id === $this->winner_partner_id) {
            return $this->loserPartner;
        }

        return null;
    }

    public function getPrizeStatusBySlot(string $slot = 'primary'): string
    {
        if ($slot === 'partner') {
            return $this->prize_partner_status ?? 'unpaid';
        }

        return $this->prize_status ?? 'unpaid';
    }

    public function getPrizeStatusForUser(User|int|null $user): string
    {
        return $this->getPrizeStatusBySlot($this->getPrizeSlotForUser($user));
    }

    public function getPrizePaidAtForUser(User|int|null $user): ?Carbon
    {
        return $this->getPrizeSlotForUser($user) === 'partner'
            ? $this->prize_partner_paid_at
            : $this->prize_paid_at;
    }

    public function getPrizeNotesForUser(User|int|null $user): ?string
    {
        return $this->getPrizeSlotForUser($user) === 'partner'
            ? $this->prize_partner_notes
            : $this->prize_notes;
    }

    public function getPrizeConfirmedAtForUser(User|int|null $user): ?Carbon
    {
        return $this->getPrizeSlotForUser($user) === 'partner'
            ? $this->prize_partner_confirmed_at
            : $this->prize_confirmed_at;
    }

    public function getPrizeFeedbackForUser(User|int|null $user): ?string
    {
        return $this->getPrizeSlotForUser($user) === 'partner'
            ? $this->prize_partner_feedback
            : $this->prize_feedback;
    }

    public function getPrizePaidByForUser(User|int|null $user): ?User
    {
        return $this->getPrizeSlotForUser($user) === 'partner'
            ? $this->prizePartnerPaidBy
            : $this->prizePaidBy;
    }

    public function getPrizeConfirmedByForUser(User|int|null $user): ?User
    {
        return $this->getPrizeSlotForUser($user) === 'partner'
            ? $this->prizePartnerConfirmedBy
            : $this->prizeConfirmedBy;
    }

    public function canUserPayPrize(?User $user, ?string $slot = null): bool
    {
        if (! $user) {
            return false;
        }

        if (! in_array($this->status, ['confirmed', 'resolved'])) {
            return false;
        }

        if ($slot === null) {
            $slot = $this->getPrizeSlotForUser($user);
        }

        // Once confirmed OK for this slot, cannot pay again
        if ($this->isPrizeConfirmed($slot)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($slot === 'partner') {
            return $user->id === $this->loser_partner_id;
        }

        return $user->id === $this->loser_id;
    }

    public function canUserConfirmPrize(?User $user, ?string $slot = null): bool
    {
        if (! $user) {
            return false;
        }

        if (! in_array($this->status, ['confirmed', 'resolved'])) {
            return false;
        }

        if ($slot === null) {
            $slot = $this->getPrizeSlotForUser($user);
        }

        if ($this->isPrizeConfirmed($slot)) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($slot === 'partner') {
            return $user->id === $this->winner_partner_id;
        }

        return $user->id === $this->winner_id;
    }

    public function isDoubles(): bool
    {
        return $this->match_type === 'doubles';
    }

    public function isSingles(): bool
    {
        return $this->match_type !== 'doubles';
    }

    public function isParticipating(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        return in_array($id, array_filter([
            $this->player1_id,
            $this->player1_partner_id,
            $this->player2_id,
            $this->player2_partner_id,
        ]));
    }

    /**
     * @return array<int, User>
     */
    public function getOpponentsOf(User|int $user): array
    {
        $id = $user instanceof User ? $user->id : $user;

        if ($this->player1_id === $id || $this->player1_partner_id === $id) {
            return array_values(array_filter([$this->player2, $this->player2Partner]));
        }

        if ($this->player2_id === $id || $this->player2_partner_id === $id) {
            return array_values(array_filter([$this->player1, $this->player1Partner]));
        }

        return [];
    }

    public function getOpponentOf(User|int $user): ?User
    {
        $opponents = $this->getOpponentsOf($user);

        return $opponents[0] ?? null;
    }

    public function isWaitingForUser(User|int $user): bool
    {
        $id = $user instanceof User ? $user->id : $user;

        if ($this->status !== 'pending') {
            return false;
        }

        $creatorInTeam1 = ($this->creator_id === $this->player1_id || $this->creator_id === $this->player1_partner_id);
        $creatorInTeam2 = ($this->creator_id === $this->player2_id || $this->creator_id === $this->player2_partner_id);

        if ($creatorInTeam1) {
            return $this->player2_id === $id || $this->player2_partner_id === $id;
        }

        if ($creatorInTeam2) {
            return $this->player1_id === $id || $this->player1_partner_id === $id;
        }

        // If created by third party (admin), waiting for any participant
        return $this->isParticipating($id);
    }

    public function getTeam1NamesAttribute(): string
    {
        if ($this->isDoubles() && $this->player1Partner) {
            return "{$this->player1->name} + {$this->player1Partner->name}";
        }

        return $this->player1?->name ?? 'Người chơi 1';
    }

    public function getTeam2NamesAttribute(): string
    {
        if ($this->isDoubles() && $this->player2Partner) {
            return "{$this->player2->name} + {$this->player2Partner->name}";
        }

        return $this->player2?->name ?? 'Người chơi 2';
    }

    public function getWinnerNamesAttribute(): string
    {
        if ($this->isDoubles() && $this->winnerPartner) {
            return "{$this->winner->name} + {$this->winnerPartner->name}";
        }

        return $this->winner?->name ?? 'Người thắng';
    }

    public function getLoserNamesAttribute(): string
    {
        if ($this->isDoubles() && $this->loserPartner) {
            return "{$this->loser->name} + {$this->loserPartner->name}";
        }

        return $this->loser?->name ?? 'Người thua';
    }

    public function isAutoConfirmExpired(): bool
    {
        return $this->status === 'pending'
            && $this->auto_confirm_at
            && Carbon::now()->greaterThanOrEqualTo($this->auto_confirm_at);
    }

    public function getRemainingSecondsForAutoConfirm(): int
    {
        if ($this->status !== 'pending' || ! $this->auto_confirm_at) {
            return 0;
        }

        $diff = Carbon::now()->diffInSeconds($this->auto_confirm_at, false);

        return max(0, (int) $diff);
    }
}
