<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Core & Infrastructure
        Schema::create('leagues', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('country')->nullable();
            $table->boolean('is_women')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('seasons', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('league_id');
            $table->string('name');
            $table->integer('year');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
        });

        Schema::create('venues', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('pitch_length_m')->nullable();
            $table->integer('pitch_width_m')->nullable();
            $table->integer('built_year')->nullable();
            $table->unsignedBigInteger('home_team_id')->nullable();
            $table->timestamps();
        });

        Schema::create('venue_competitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('venue_id');
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('season_id');
            $table->string('host_country_code', 2)->nullable();
            $table->boolean('hosts_opening')->default(false);
            $table->boolean('hosts_final')->default(false);
            $table->boolean('hosts_third_place')->default(false);
            $table->json('rounds')->nullable();
            $table->integer('match_count')->default(0);
            $table->string('fifa_role')->nullable();
            $table->timestamps();
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->boolean('is_women')->default(false);
            $table->string('logo_url')->nullable();
            $table->timestamps();
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('set null');
        });

        // 2. Persons (Managers, Referees, Players)
        Schema::create('managers', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('country')->nullable();
            $table->string('tactical_profile')->nullable();
            $table->string('preferred_formation')->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->integer('matches_total')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('losses')->default(0);
            $table->decimal('win_pct', 5, 2)->default(0);
            $table->decimal('avg_goals_scored', 5, 2)->default(0);
            $table->decimal('avg_goals_conceded', 5, 2)->default(0);
            $table->decimal('avg_possession', 5, 2)->default(0);
            $table->decimal('clean_sheet_pct', 5, 2)->default(0);
            $table->decimal('btts_pct', 5, 2)->default(0);
            $table->decimal('over_25_pct', 5, 2)->default(0);
            $table->timestamp('stats_updated_at')->nullable();
            $table->timestamps();
            $table->foreign('current_team_id')->references('id')->on('teams')->onDelete('set null');
        });

        Schema::create('manager_careers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('team_id');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->integer('matches')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('draws')->default(0);
            $table->integer('losses')->default(0);
            $table->decimal('win_pct', 5, 2)->default(0);
            $table->timestamps();
            $table->foreign('manager_id')->references('id')->on('managers')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::create('referees', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('nationality_a3', 3)->nullable();
            $table->date('birthdate')->nullable();
            $table->integer('matches')->default(0);
            $table->integer('total_yellow_cards')->default(0);
            $table->integer('total_red_cards')->default(0);
            $table->decimal('avg_yellow_per_match', 5, 2)->default(0);
            $table->decimal('avg_red_per_match', 5, 2)->default(0);
            $table->decimal('avg_goals_per_match', 5, 2)->default(0);
            $table->decimal('avg_fouls_per_match', 5, 2)->default(0);
            $table->integer('career_games')->default(0);
            $table->integer('career_yellow_cards')->default(0);
            $table->integer('career_red_cards')->default(0);
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('position', 10)->nullable();
            $table->string('specific_position', 10)->nullable();
            $table->integer('jersey_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->integer('height_cm')->nullable();
            $table->integer('weight_kg')->nullable();
            $table->string('preferred_foot')->nullable();
            $table->string('nationality')->nullable();
            $table->string('nationality_code', 2)->nullable();
            $table->unsignedBigInteger('current_team_id')->nullable();
            $table->unsignedBigInteger('national_team_id')->nullable();
            $table->unsignedBigInteger('market_value_eur')->nullable();
            $table->date('contract_until')->nullable();
            $table->string('availability')->default('available');
            $table->timestamps();
            $table->foreign('current_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('national_team_id')->references('id')->on('teams')->onDelete('set null');
        });

        Schema::create('player_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->date('transfer_date');
            $table->unsignedBigInteger('from_team_id')->nullable();
            $table->unsignedBigInteger('to_team_id')->nullable();
            $table->unsignedBigInteger('fee_eur')->nullable();
            $table->string('fee_description')->nullable();
            $table->string('transfer_type')->nullable();
            $table->timestamps();
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('from_team_id')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('to_team_id')->references('id')->on('teams')->onDelete('set null');
        });

        Schema::create('player_career_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('team_id');
            $table->integer('matches')->default(0);
            $table->integer('minutes')->default(0);
            $table->integer('goals')->default(0);
            $table->integer('assists')->default(0);
            $table->decimal('avg_rating', 4, 2)->nullable();
            $table->timestamps();
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::create('player_national_stats', function (Blueprint $table) {
            $table->unsignedBigInteger('player_id')->primary();
            $table->unsignedBigInteger('national_team_id');
            $table->integer('caps')->default(0);
            $table->integer('goals')->default(0);
            $table->timestamp('last_appearance')->nullable();
            $table->timestamps();
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('national_team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        // 3. Events & Match Data
        Schema::create('events', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('home_team_id');
            $table->unsignedBigInteger('away_team_id');
            $table->unsignedBigInteger('home_coach_id')->nullable();
            $table->unsignedBigInteger('away_coach_id')->nullable();
            $table->unsignedBigInteger('referee_id')->nullable();
            $table->unsignedBigInteger('venue_id')->nullable();
            $table->timestamp('event_date');
            $table->string('status');
            $table->integer('round_number')->nullable();
            $table->string('period')->nullable();
            $table->integer('current_minute')->nullable();
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->integer('home_score_ht')->nullable();
            $table->integer('away_score_ht')->nullable();
            $table->json('penalty_shootout')->nullable();
            $table->boolean('is_local_derby')->default(false);
            $table->boolean('is_neutral_ground')->default(false);
            $table->integer('travel_distance_km')->nullable();
            $table->integer('weather_code')->nullable();
            $table->string('weather_description')->nullable();
            $table->decimal('weather_wind_speed')->nullable();
            $table->decimal('weather_temperature_c')->nullable();
            $table->integer('pitch_condition')->nullable();
            $table->integer('attendance')->nullable();
            $table->boolean('live_websocket')->default(false);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            $table->foreign('league_id')->references('id')->on('leagues');
            $table->foreign('season_id')->references('id')->on('seasons');
            $table->foreign('home_team_id')->references('id')->on('teams');
            $table->foreign('away_team_id')->references('id')->on('teams');
            $table->foreign('home_coach_id')->references('id')->on('managers');
            $table->foreign('away_coach_id')->references('id')->on('managers');
            $table->foreign('referee_id')->references('id')->on('referees');
            $table->foreign('venue_id')->references('id')->on('venues');
        });

        Schema::create('event_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('team_id');
            $table->string('side');
            $table->integer('total_shots')->default(0);
            $table->integer('ball_possession')->default(0);
            $table->integer('crosses_value')->default(0);
            $table->integer('crosses_total')->default(0);
            $table->integer('crosses_pct')->default(0);
            $table->integer('dribbles_value')->default(0);
            $table->integer('dribbles_total')->default(0);
            $table->integer('dribbles_pct')->default(0);
            $table->integer('long_balls_value')->default(0);
            $table->integer('long_balls_total')->default(0);
            $table->integer('long_balls_pct')->default(0);
            $table->integer('attack')->default(0);
            $table->integer('ball_safe')->default(0);
            $table->integer('dangerous_attack')->default(0);
            $table->decimal('pass_accuracy_pct', 5, 2)->default(0);
            $table->decimal('xg_actual', 5, 2)->default(0);
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::create('shotmap', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('player_id');
            $table->integer('minute');
            $table->decimal('x', 5, 2);
            $table->decimal('y', 5, 2);
            $table->decimal('xg', 5, 3)->nullable();
            $table->string('body_part')->nullable();
            $table->string('situation')->nullable();
            $table->boolean('is_goal')->default(false);
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        Schema::create('event_momentum', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->integer('minute');
            $table->integer('value');
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::create('average_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('player_id');
            $table->decimal('x', 5, 2);
            $table->decimal('y', 5, 2);
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        Schema::create('xg_per_minute', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->integer('minute');
            $table->decimal('xg_home', 5, 3)->nullable();
            $table->decimal('xg_away', 5, 3)->nullable();
            $table->decimal('cum_home', 5, 3)->nullable();
            $table->decimal('cum_away', 5, 3)->nullable();
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('event_id');
            $table->string('type');
            $table->integer('minute');
            $table->boolean('is_home')->default(true);
            $table->unsignedBigInteger('player_id')->nullable();
            $table->unsignedBigInteger('player_in_id')->nullable();
            $table->unsignedBigInteger('player_out_id')->nullable();
            $table->string('card_type')->nullable();
            $table->string('period_label')->nullable();
            $table->boolean('confirmed')->default(true);
            $table->boolean('is_live')->default(false);
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('set null');
        });

        // 4. Odds & Predictions
        Schema::create('event_odds_consensus', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->primary();
            $table->decimal('home_win', 6, 3)->nullable();
            $table->decimal('draw', 6, 3)->nullable();
            $table->decimal('away_win', 6, 3)->nullable();
            $table->decimal('over_15_goals', 6, 3)->nullable();
            $table->decimal('over_25_goals', 6, 3)->nullable();
            $table->decimal('over_35_goals', 6, 3)->nullable();
            $table->decimal('under_15_goals', 6, 3)->nullable();
            $table->decimal('under_25_goals', 6, 3)->nullable();
            $table->decimal('under_35_goals', 6, 3)->nullable();
            $table->decimal('btts_yes', 6, 3)->nullable();
            $table->decimal('btts_no', 6, 3)->nullable();
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::create('bookmakers', function (Blueprint $table) {
            $table->string('slug')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('odds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('bookmaker_slug');
            $table->string('market');
            $table->string('outcome');
            $table->string('outcome_name')->nullable();
            $table->decimal('decimal_odds', 8, 3);
            $table->decimal('previous_decimal_odds', 8, 3)->nullable();
            $table->decimal('implied_probability', 8, 4)->nullable();
            $table->string('movement')->nullable();
            $table->boolean('is_max_quote')->default(false);
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('bookmaker_slug')->references('slug')->on('bookmakers');
        });

        Schema::create('event_predictions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->decimal('prob_home', 5, 2)->nullable();
            $table->decimal('prob_draw', 5, 2)->nullable();
            $table->decimal('prob_away', 5, 2)->nullable();
            $table->string('predicted_result')->nullable();
            $table->decimal('expected_home_goals', 5, 2)->nullable();
            $table->decimal('expected_away_goals', 5, 2)->nullable();
            $table->decimal('prob_over_15', 5, 2)->nullable();
            $table->decimal('prob_over_25', 5, 2)->nullable();
            $table->decimal('prob_over_35', 5, 2)->nullable();
            $table->decimal('prob_btts_yes', 5, 2)->nullable();
            $table->string('most_likely_score')->nullable();
            $table->string('favorite')->nullable();
            $table->decimal('favorite_prob', 5, 2)->nullable();
            $table->boolean('bet_favorite')->default(false);
            $table->boolean('over_15')->default(false);
            $table->boolean('over_25')->default(false);
            $table->boolean('over_35')->default(false);
            $table->boolean('btts')->default(false);
            $table->boolean('winner')->default(false);
            $table->decimal('confidence', 5, 4)->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        // 5. Lineups
        Schema::create('lineups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('lineup_status');
            $table->boolean('beta')->default(false);
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::create('lineup_teams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lineup_id');
            $table->string('side');
            $table->unsignedBigInteger('team_id');
            $table->string('formation')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamps();
            $table->foreign('lineup_id')->references('id')->on('lineups')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::create('lineup_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lineup_team_id');
            $table->unsignedBigInteger('player_id');
            $table->string('position')->nullable();
            $table->integer('jersey_number')->nullable();
            $table->decimal('ai_score', 5, 4)->nullable();
            $table->boolean('is_substitute')->default(false);
            $table->timestamps();
            $table->foreign('lineup_team_id')->references('id')->on('lineup_teams')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        Schema::create('unavailable_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lineup_id');
            $table->unsignedBigInteger('player_id');
            $table->string('status')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->foreign('lineup_id')->references('id')->on('lineups')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        Schema::create('player_match_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('team_id');
            $table->integer('minutes_played')->default(0);
            $table->decimal('rating', 4, 2)->nullable();
            $table->integer('goals')->default(0);
            $table->integer('goal_assist')->default(0);
            $table->decimal('expected_goals', 5, 2)->default(0);
            $table->decimal('expected_assists', 5, 2)->default(0);
            $table->integer('total_shots')->default(0);
            $table->integer('shots_on_target')->default(0);
            $table->integer('total_pass')->default(0);
            $table->integer('accurate_pass')->default(0);
            $table->integer('key_pass')->default(0);
            $table->integer('total_tackle')->default(0);
            $table->integer('interception')->default(0);
            $table->integer('yellow_card')->default(0);
            $table->integer('red_card')->default(0);
            $table->integer('saves')->default(0);
            $table->timestamps();
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        // 6. Media & Social
        Schema::create('tv_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country_code', 2)->nullable();
            $table->string('link')->nullable();
            $table->timestamps();
        });

        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('country_code', 2)->nullable();
            $table->unsignedBigInteger('channel_id');
            $table->timestamp('scheduled_start_time')->nullable();
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('tv_channels')->onDelete('cascade');
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('type');
            $table->string('url');
            $table->text('text')->nullable();
            $table->string('title')->nullable();
            $table->string('thumbnail')->nullable();
            $table->json('media')->nullable();
            $table->string('account_handle')->nullable();
            $table->string('account_name')->nullable();
            $table->boolean('account_verified')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('social_post_teams', function (Blueprint $table) {
            $table->unsignedBigInteger('social_post_id');
            $table->unsignedBigInteger('team_id');
            $table->foreign('social_post_id')->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::create('social_post_events', function (Blueprint $table) {
            $table->unsignedBigInteger('social_post_id');
            $table->unsignedBigInteger('event_id');
            $table->foreign('social_post_id')->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::create('social_post_players', function (Blueprint $table) {
            $table->unsignedBigInteger('social_post_id');
            $table->unsignedBigInteger('player_id');
            $table->foreign('social_post_id')->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });

        Schema::create('social_post_managers', function (Blueprint $table) {
            $table->unsignedBigInteger('social_post_id');
            $table->unsignedBigInteger('manager_id');
            $table->foreign('social_post_id')->references('id')->on('social_posts')->onDelete('cascade');
            $table->foreign('manager_id')->references('id')->on('managers')->onDelete('cascade');
        });

        Schema::create('event_metadata', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->primary();
            $table->json('jerseys')->nullable();
            $table->text('ai_preview_text')->nullable();
            $table->timestamp('ai_preview_generated_at')->nullable();
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        Schema::create('funfacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->integer('type_id')->nullable();
            $table->text('sentence');
            $table->timestamps();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });
        
        // 7. Standings (Added back as essential)
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('league_id');
            $table->unsignedBigInteger('season_id');
            $table->unsignedBigInteger('team_id');
            $table->integer('position');
            $table->integer('played');
            $table->integer('won');
            $table->integer('drawn');
            $table->integer('lost');
            $table->integer('gf');
            $table->integer('ga');
            $table->integer('gd');
            $table->integer('pts');
            $table->decimal('xgf', 5, 2)->nullable();
            $table->decimal('xga', 5, 2)->nullable();
            $table->decimal('xgd', 5, 2)->nullable();
            $table->string('form', 10)->nullable();
            $table->boolean('is_live')->default(false);
            $table->timestamps();
            $table->foreign('league_id')->references('id')->on('leagues')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        // Drop all tables in reverse order if needed
        Schema::enableForeignKeyConstraints();
    }
};
