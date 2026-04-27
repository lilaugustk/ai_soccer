<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_players', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // Player ID từ API
            $table->string('name');
            $table->string('nationality')->nullable();
            $table->string('position', 50)->nullable();
            $table->integer('birth_year')->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();

            $table->foreign('current_team_id')->references('id')->on('football_teams')->onDelete('set null');
        });

        Schema::create('football_player_season_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('league_id');
            $table->integer('season'); // Ví dụ: 2024
            $table->unsignedBigInteger('team_id')->nullable();
            
            $table->integer('games')->default(0);
            $table->integer('games_starts')->default(0);
            $table->integer('minutes')->default(0);
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->integer('cards_yellow')->default(0);
            $table->integer('cards_red')->default(0);
            $table->json('detailed_stats')->nullable();
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('football_players')->onDelete('cascade');
            $table->foreign('league_id')->references('id')->on('football_leagues')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('football_teams')->onDelete('cascade');
        });

        Schema::create('football_player_match_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('match_id');
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('team_id')->nullable();
            
            $table->string('position', 50)->nullable();
            $table->integer('age')->nullable();
            $table->integer('minutes')->default(0);
            $table->json('detailed_stats')->nullable();
            $table->timestamps();

            $table->foreign('match_id')->references('id')->on('football_matches')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('football_players')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('football_teams')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_player_match_stats');
        Schema::dropIfExists('football_player_season_stats');
        Schema::dropIfExists('football_players');
    }
};
