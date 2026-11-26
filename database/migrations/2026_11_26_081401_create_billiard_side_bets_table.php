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
        Schema::create('billiard_side_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billiard_challenge_id')->constrained('billiard_challenges')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->string('bet_on_team'); // 'challenger' or 'opponent'
            $table->string('prize')->default('1 cốc cà phê');
            $table->string('notes')->nullable();
            $table->string('status')->default('pending'); // 'pending', 'accepted', 'declined', 'cancelled', 'resolved'
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('loser_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('prize_status')->default('unpaid'); // 'unpaid', 'paid', 'confirmed', 'rejected'
            $table->timestamp('prize_paid_at')->nullable();
            $table->timestamp('prize_confirmed_at')->nullable();
            $table->string('prize_notes')->nullable();
            $table->string('prize_feedback')->nullable();
            $table->timestamps();

            $table->index(['billiard_challenge_id', 'status']);
            $table->index(['creator_id', 'status']);
            $table->index(['target_id', 'status']);
            $table->index(['winner_id', 'prize_status']);
            $table->index(['loser_id', 'prize_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billiard_side_bets');
    }
};
