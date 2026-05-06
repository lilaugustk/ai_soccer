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
        Schema::table('football_teams', function (Blueprint $table) {
            $table->json('coach_history')->after('country')->nullable();
            $table->timestamp('coach_history_last_sync')->after('coach_history')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_teams', function (Blueprint $table) {
            $table->dropColumn(['coach_history', 'coach_history_last_sync']);
        });
    }
};
