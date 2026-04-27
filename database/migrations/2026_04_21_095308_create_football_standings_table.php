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
        Schema::create('football_standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('team_id');
            $table->integer('season');
            $table->integer('rank');
            $table->integer('points');
            $table->integer('played');
            $table->integer('win')->default(0);
            $table->integer('draw')->default(0);
            $table->integer('lose')->default(0);
            $table->integer('goals_for')->default(0);
            $table->integer('goals_against')->default(0);
            $table->string('group')->nullable();
            $table->string('description')->nullable();
            $table->string('form')->nullable();
            $table->timestamps();

            $table->foreign('league_id')->references('id')->on('football_leagues')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('football_teams')->onDelete('cascade');
            $table->unique(['league_id', 'team_id', 'season']); // Đảm bảo mỗi đội chỉ có 1 rank/mùa
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('football_standings');
    }
};
