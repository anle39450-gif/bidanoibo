<?php

namespace App\Http\Controllers;

use App\Services\BilliardMatchService;
use App\Services\BilliardStatsService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(
        Request $request,
        TelegramBotService $telegramBot,
        BilliardMatchService $matchService,
        BilliardStatsService $statsService
    ): JsonResponse {
        $update = $request->all();

        Log::info('Telegram Webhook received:', $update);

        try {
            $telegramBot->handleWebhook($update, $matchService, $statsService);
        } catch (\Throwable $e) {
            Log::error('Telegram webhook handling failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
