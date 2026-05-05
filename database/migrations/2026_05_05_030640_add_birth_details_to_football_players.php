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
            $table->date('birth_date')->nullable()->after('birth_year');
            $table->string('birth_place')->nullable()->after('birth_date');
            $table->string('birth_country')->nullable()->after('birth_place');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('football_players', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'birth_place', 'birth_country']);
        });
    }
};
