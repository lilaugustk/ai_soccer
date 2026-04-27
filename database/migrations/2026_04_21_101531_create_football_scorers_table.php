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
        Schema::create('football_scorers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->string('player_name');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('league_id');
            $table->integer('season');
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->foreign('league_id')->references('id')->on('football_leagues')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('football_teams')->onDelete('cascade');
            $table->unique(['player_id', 'league_id', 'season']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_scorers');
    }
};
