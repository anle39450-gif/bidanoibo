<?php

namespace Database\Seeders;

use App\Models\TelegramBotConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BilliardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Default Telegram Bot Config
        TelegramBotConfig::updateOrCreate(['id' => 1], [
            'bot_token' => env('TELEGRAM_BOT_TOKEN', '9074081678:AAESu8XM6CgYeUG1Rw8phXe-Tue6Zh7BiqY'),
            'bot_username' => env('TELEGRAM_BOT_USERNAME', 'BidaNoiBo_bot'),
            'is_active' => true,
        ]);

        // 2. Create Admin Account ONLY
        User::updateOrCreate(['username' => 'admin'], [
            'name' => 'Quản Trị Viên',
            'username' => 'admin',
            'email' => 'admin@bida.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'telegram_link_code' => Str::random(10),
        ]);
    }
}
