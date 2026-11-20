<?php

namespace App\Livewire;

use App\Models\BilliardMatch;
use Carbon\Carbon;
use Livewire\Component;

class MatchCalendar extends Component
{
    public int $year;

    public int $month;

    public string $selectedDate;

    public function mount(?string $initialDate = null): void
    {
        $today = Carbon::today();
        $this->year = $today->year;
        $this->month = $today->month;
        $this->selectedDate = $initialDate ?? $today->format('Y-m-d');
    }

    public function goToPrevMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function goToNextMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->year = $date->year;
        $this->month = $date->month;
    }

    public function goToToday(): void
    {
        $today = Carbon::today();
        $this->year = $today->year;
        $this->month = $today->month;
        $this->selectedDate = $today->format('Y-m-d');
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function render()
    {
        $currentMonthDate = Carbon::createFromDate($this->year, $this->month, 1);
        $monthTitle = 'Tháng '.$currentMonthDate->format('m/Y');

        $startOfMonth = $currentMonthDate->copy()->startOfMonth();
        $endOfMonth = $currentMonthDate->copy()->endOfMonth();

        // Calendar starts on Monday (ISO-8601 standard)
        $startOfCalendar = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $endOfCalendar = $endOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        // Fetch matches in this calendar frame
        $matchesInPeriod = BilliardMatch::with(['creator', 'player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'loser', 'winnerPartner', 'loserPartner'])
            ->whereBetween('played_at', [$startOfCalendar->copy()->startOfDay(), $endOfCalendar->copy()->endOfDay()])
            ->get();

        $matchesByDate = $matchesInPeriod->groupBy(fn ($m) => $m->played_at->format('Y-m-d'));

        // Build days grid
        $days = [];
        $cursor = $startOfCalendar->copy();
        $todayStr = Carbon::today()->format('Y-m-d');

        while ($cursor->lte($endOfCalendar)) {
            $dateStr = $cursor->format('Y-m-d');
            $dayMatches = $matchesByDate->get($dateStr, collect());

            $days[] = [
                'dayNumber' => $cursor->day,
                'dateString' => $dateStr,
                'isCurrentMonth' => $cursor->month === $this->month,
                'isToday' => $dateStr === $todayStr,
                'isSelected' => $dateStr === $this->selectedDate,
                'matchCount' => $dayMatches->count(),
                'hasPending' => $dayMatches->contains('status', 'pending'),
                'hasDisputed' => $dayMatches->contains('status', 'disputed'),
                'hasConfirmed' => $dayMatches->contains(fn ($m) => in_array($m->status, ['confirmed', 'resolved'])),
            ];

            $cursor->addDay();
        }

        // Matches on the currently selected date
        $selectedDateObj = Carbon::parse($this->selectedDate);
        $selectedMatches = BilliardMatch::with(['creator', 'player1', 'player2', 'player1Partner', 'player2Partner', 'winner', 'loser', 'winnerPartner', 'loserPartner'])
            ->whereDate('played_at', $this->selectedDate)
            ->orderBy('played_at', 'desc')
            ->get();

        return view('livewire.match-calendar', [
            'monthTitle' => $monthTitle,
            'days' => $days,
            'selectedDateObj' => $selectedDateObj,
            'selectedMatches' => $selectedMatches,
            'totalMonthMatches' => $matchesInPeriod->where('played_at', '>=', $startOfMonth)->where('played_at', '<=', $endOfMonth)->count(),
        ]);
    }
}
