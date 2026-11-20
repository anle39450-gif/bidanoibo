<?php

namespace App\Console\Commands;

use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class TelegramListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:listen {--timeout=20 : Polling timeout in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for real-time Telegram Bot updates via long polling (for local development)';

    /**
     * Execute the console command.
     */
    public function handle(
        TelegramBotService $telegramBot,
        BilliardMatchService $matchService,
        BilliardStatsService $statsService
    ): int {
        if (! $telegramBot->isConfigured()) {
            $this->error('Telegram Bot is not configured! Please configure bot_token in Admin Settings.');

            return Command::FAILURE;
        }

        $this->info('Starting Telegram Bot Listener (Long Polling)...');
        $this->line('Clearing existing webhook if any...');
        $telegramBot->deleteWebhook();

        $this->info('Telegram Bot is now ACTIVELY listening for messages and commands!');
        $this->comment('Send /start or /help to your bot on Telegram to test.');

        $offset = 0;
        $timeout = (int) $this->option('timeout') ?: 20;

        while (true) {
            try {
                $updates = $telegramBot->getUpdates($offset, $timeout);

                foreach ($updates as $update) {
                    $offset = $update['update_id'] + 1;

                    if (isset($update['message'])) {
                        $from = $update['message']['from']['username'] ?? $update['message']['from']['first_name'] ?? 'Unknown';
                        $text = $update['message']['text'] ?? '[Media/Attachment]';
                        $this->line("<fg=cyan>[Message]</> From @{$from}: <fg=yellow>{$text}</>");
                    } elseif (isset($update['callback_query'])) {
                        $from = $update['callback_query']['from']['username'] ?? $update['callback_query']['from']['first_name'] ?? 'Unknown';
                        $data = $update['callback_query']['data'] ?? '';
                        $this->line("<fg=green>[Button Click]</> From @{$from}: <fg=yellow>{$data}</>");
                    }

                    $telegramBot->handleWebhook($update, $matchService, $statsService);
                }
            } catch (\Throwable $e) {
                $this->error('Error during polling: '.$e->getMessage());
                sleep(2);
            }
        }

        return Command::SUCCESS;
    }
}
