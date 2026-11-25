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
            $table->string('prize_partner_status')->nullable()->default('unpaid')->after('prize_feedback')->index();
            $table->dateTime('prize_partner_paid_at')->nullable()->after('prize_partner_status');
            $table->foreignId('prize_partner_paid_by')->nullable()->after('prize_partner_paid_at')->constrained('users')->nullOnDelete();
            $table->text('prize_partner_notes')->nullable()->after('prize_partner_paid_by');
            $table->dateTime('prize_partner_confirmed_at')->nullable()->after('prize_partner_notes');
            $table->foreignId('prize_partner_confirmed_by')->nullable()->after('prize_partner_confirmed_at')->constrained('users')->nullOnDelete();
            $table->text('prize_partner_feedback')->nullable()->after('prize_partner_confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billiard_matches', function (Blueprint $table) {
            $table->dropForeign(['prize_partner_paid_by']);
            $table->dropForeign(['prize_partner_confirmed_by']);
            $table->dropColumn([
                'prize_partner_status',
                'prize_partner_paid_at',
                'prize_partner_paid_by',
                'prize_partner_notes',
                'prize_partner_confirmed_at',
                'prize_partner_confirmed_by',
                'prize_partner_feedback',
            ]);
        });
    }
};
