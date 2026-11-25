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
            $table->boolean('is_hidden')->default(false)->after('status')->index();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billiard_matches', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
            $table->dropSoftDeletes();
        });
    }
};
