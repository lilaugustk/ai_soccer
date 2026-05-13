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
        Schema::table('event_stats', function (Blueprint $table) {
            $table->integer('shots_on_goal')->default(0)->after('total_shots');
            $table->integer('shots_off_goal')->default(0)->after('shots_on_goal');
            $table->integer('blocked_shots')->default(0)->after('shots_off_goal');
            $table->integer('corner_kicks')->default(0)->after('dangerous_attack');
            $table->integer('offsides')->default(0)->after('corner_kicks');
            $table->integer('fouls')->default(0)->after('offsides');
            $table->integer('goalkeeper_saves')->default(0)->after('fouls');
            $table->integer('yellow_cards')->default(0)->after('goalkeeper_saves');
            $table->integer('red_cards')->default(0)->after('yellow_cards');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_stats', function (Blueprint $table) {
            $table->dropColumn([
                'shots_on_goal', 'shots_off_goal', 'blocked_shots',
                'corner_kicks', 'offsides', 'fouls',
                'goalkeeper_saves', 'yellow_cards', 'red_cards'
            ]);
        });
    }
};
