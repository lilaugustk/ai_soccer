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
        Schema::create('wc2026_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('flag')->nullable();
            $table->string('group_name')->nullable();
            $table->timestamps();
        });

        Schema::create('wc2026_stadiums', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('wc2026_matches', function (Blueprint $table) {
            $table->id();
            $table->integer('match_number')->nullable();
            $table->string('round')->nullable();
            $table->string('group_name')->nullable();
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->unsignedBigInteger('away_team_id')->nullable();
            $table->string('home_team_name')->nullable();
            $table->string('away_team_name')->nullable();
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->unsignedBigInteger('stadium_id')->nullable();
            $table->dateTime('kickoff_at')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wc2026_matches');
        Schema::dropIfExists('wc2026_stadiums');
        Schema::dropIfExists('wc2026_teams');
    }
};
