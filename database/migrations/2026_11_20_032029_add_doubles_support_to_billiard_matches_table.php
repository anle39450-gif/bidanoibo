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
            $table->string('match_type', 20)->default('singles')->after('creator_id');
            $table->foreignId('player1_partner_id')->nullable()->after('player1_id')->constrained('users')->nullOnDelete();
            $table->foreignId('player2_partner_id')->nullable()->after('player2_id')->constrained('users')->nullOnDelete();
            $table->foreignId('winner_partner_id')->nullable()->after('winner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('loser_partner_id')->nullable()->after('loser_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billiard_matches', function (Blueprint $table) {
            $table->dropForeign(['player1_partner_id']);
            $table->dropForeign(['player2_partner_id']);
            $table->dropForeign(['winner_partner_id']);
            $table->dropForeign(['loser_partner_id']);

            $table->dropColumn([
                'match_type',
                'player1_partner_id',
                'player2_partner_id',
                'winner_partner_id',
                'loser_partner_id',
            ]);
        });
    }
};
