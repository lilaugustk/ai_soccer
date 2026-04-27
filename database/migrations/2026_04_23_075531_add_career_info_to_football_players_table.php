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
        Schema::table('football_players', function (Blueprint $table) {
            $table->json('transfers')->nullable()->after('photo');
            $table->json('trophies')->nullable()->after('transfers');
            $table->json('sidelined_history')->nullable()->after('trophies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_players', function (Blueprint $table) {
            $table->dropColumn(['transfers', 'trophies', 'sidelined_history']);
        });
    }
};
