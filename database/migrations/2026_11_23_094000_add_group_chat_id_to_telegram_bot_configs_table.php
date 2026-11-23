<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('telegram_bot_configs', function (Blueprint $table) {
            $table->string('group_chat_id')->nullable()->after('bot_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_bot_configs', function (Blueprint $table) {
            $table->dropColumn('group_chat_id');
        });
    }
};
