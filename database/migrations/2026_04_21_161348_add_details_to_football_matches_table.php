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
        Schema::table('football_matches', function (Blueprint $table) {
            $table->string('referee')->nullable()->after('round');
            $table->string('venue_name')->nullable()->after('referee');
            $table->string('venue_city')->nullable()->after('venue_name');
            $table->integer('attendance')->nullable()->after('venue_city');
        });
    }

    public function down(): void
    {
        Schema::table('football_matches', function (Blueprint $table) {
            $table->dropColumn(['referee', 'venue_name', 'venue_city', 'attendance']);
        });
    }
};
