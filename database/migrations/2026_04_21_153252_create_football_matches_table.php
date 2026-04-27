<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_matches', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // Fixture ID từ API
            
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            
            $table->dateTime('match_at');
            $table->string('status', 20)->default('NS'); // NS, LIVE, FT...
            $table->string('round')->nullable();
            
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            
            // Dữ liệu chi tiết lưu dưới dạng JSON để render nhanh
            $table->json('events')->nullable();
            $table->json('lineups')->nullable();
            $table->json('statistics')->nullable();
            $table->json('predictions')->nullable();
            
            $table->foreign('league_id')->references('id')->on('football_leagues')->onDelete('cascade');
            $table->foreign('home_team_id')->references('id')->on('football_teams')->onDelete('cascade');
            $table->foreign('away_team_id')->references('id')->on('football_teams')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_matches');
    }
};
