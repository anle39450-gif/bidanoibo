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
        Schema::table('billiard_matches', function (Blueprint $table) {
            $table->dateTime('prize_confirmed_at')->nullable()->after('prize_notes');
            $table->foreignId('prize_confirmed_by')->nullable()->after('prize_confirmed_at')->constrained('users')->nullOnDelete();
            $table->text('prize_feedback')->nullable()->after('prize_confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billiard_matches', function (Blueprint $table) {
            $table->dropForeign(['prize_confirmed_by']);
            $table->dropColumn(['prize_confirmed_at', 'prize_confirmed_by', 'prize_feedback']);
        });
    }
};
