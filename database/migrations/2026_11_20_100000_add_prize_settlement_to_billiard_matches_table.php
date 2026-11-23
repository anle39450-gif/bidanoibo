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
            $table->string('prize_status')->default('unpaid')->after('prize')->index();
            $table->dateTime('prize_paid_at')->nullable()->after('prize_status');
            $table->foreignId('prize_paid_by')->nullable()->after('prize_paid_at')->constrained('users')->nullOnDelete();
            $table->text('prize_notes')->nullable()->after('prize_paid_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billiard_matches', function (Blueprint $table) {
            $table->dropForeign(['prize_paid_by']);
            $table->dropColumn(['prize_status', 'prize_paid_at', 'prize_paid_by', 'prize_notes']);
        });
    }
};
