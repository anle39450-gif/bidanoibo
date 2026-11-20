<?php

use App\Models\TelegramBotConfig;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure initial admin user exists if table is empty
        if (! User::where('username', 'admin')->exists()) {
            User::create([
                'name' => 'Quản Trị Viên',
                'username' => 'admin',
                'email' => 'admin@bida.local',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'telegram_link_code' => Str::random(10),
            ]);
        }

        // 2. Ensure initial bot config exists if not created
        if (! TelegramBotConfig::where('id', 1)->exists()) {
            TelegramBotConfig::create([
                'id' => 1,
                'bot_token' => env('TELEGRAM_BOT_TOKEN', '9074081678:AAESu8XM6CgYeUG1Rw8phXe-Tue6Zh7BiqY'),
                'bot_username' => env('TELEGRAM_BOT_USERNAME', 'BidaNoiBo_bot'),
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Protected: Do nothing on rollback
    }
};
