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
        Schema::create('billiard_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('player2_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('winner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('loser_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('played_at');
            $table->string('prize'); // e.g., "Kèo cà phê muối", "Kèo trà sữa"
            $table->text('notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('status')->default('pending')->index(); // 'pending', 'confirmed', 'disputed', 'resolved', 'cancelled'
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('auto_confirm_at')->nullable()->index();
            $table->dateTime('confirmed_at')->nullable();
            $table->string('confirmed_by')->nullable(); // 'opponent', 'auto_24h', 'admin'
            $table->dateTime('disputed_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->string('dispute_evidence_path')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('created_from')->default('web'); // 'web', 'telegram'
            $table->timestamps();

            $table->index(['player1_id', 'player2_id']);
            $table->index('played_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billiard_matches');
    }
};
