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
        Schema::table('billiard_challenges', function (Blueprint $table) {
            $table->string('challenge_type')->default('singles')->after('opponent_id');
            $table->foreignId('challenger_partner_id')->nullable()->after('challenge_type')->constrained('users')->nullOnDelete();
            $table->foreignId('opponent_partner_id')->nullable()->after('challenger_partner_id')->constrained('users')->nullOnDelete();

            $table->index(['opponent_partner_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billiard_challenges', function (Blueprint $table) {
            $table->dropIndex(['opponent_partner_id', 'status']);
            $table->dropConstrainedForeignId('challenger_partner_id');
            $table->dropConstrainedForeignId('opponent_partner_id');
            $table->dropColumn('challenge_type');
        });
    }
};
