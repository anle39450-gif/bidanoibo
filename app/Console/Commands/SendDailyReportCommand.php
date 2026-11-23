<?php

namespace App\Console\Commands;

use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SendDailyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billiard:daily-report {--chat_id= : Chat ID cụ thể để gửi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gửi báo cáo tổng hợp tình hình thi đấu và nợ kèo bida hàng ngày qua Telegram';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramBot, BilliardStatsService $statsService): int
    {
        $this->info('Đang chuẩn bị và gửi báo cáo bida hàng ngày qua Telegram...');

        $targetChatId = $this->option('chat_id');
        $result = $telegramBot->sendDailyReport($statsService, $targetChatId);

        if ($result['success']) {
            $this->info('✅ '.$result['message']);

            return self::SUCCESS;
        }

        $this->error('❌ '.$result['message']);

        return self::FAILURE;
    }
}
