<?php

namespace App\Console\Commands;

use App\Services\BilliardMatchService;
use Illuminate\Console\Command;

class AutoConfirmMatchesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:auto-confirm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically confirm pending billiard matches that have exceeded the 24-hour response window';

    /**
     * Execute the console command.
     */
    public function handle(BilliardMatchService $matchService): int
    {
        $this->info('Checking for expired pending matches...');
        $count = $matchService->autoConfirmExpiredMatches();

        if ($count > 0) {
            $this->info("Successfully auto-confirmed {$count} matches.");
        } else {
            $this->line('No matches required auto-confirmation.');
        }

        return Command::SUCCESS;
    }
}
