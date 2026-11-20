<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TelegramBotConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_token',
        'bot_username',
        'webhook_url',
        'is_active',
        'last_webhook_ping_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_webhook_ping_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        try {
            if (Schema::hasTable('telegram_bot_configs')) {
                return static::firstOrCreate([], [
                    'bot_token' => config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN')),
                    'bot_username' => config('services.telegram.bot_username', env('TELEGRAM_BOT_USERNAME', 'BidaNoiBoBot')),
                    'is_active' => true,
                ]);
            }
        } catch (\Throwable $e) {
            // Table not yet migrated or connection in flux
        }

        return new static([
            'bot_token' => config('services.telegram.bot_token', env('TELEGRAM_BOT_TOKEN')),
            'bot_username' => config('services.telegram.bot_username', env('TELEGRAM_BOT_USERNAME', 'BidaNoiBoBot')),
            'is_active' => true,
        ]);
    }
}
