<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamSeasonStat extends Model
{
    protected $fillable = [
        'team_id',
        'league_id',
        'season_id',
        'games_played',
        'wins',
        'draws',
        'losses',
        'goals_for',
        'goals_against',
        'goal_difference',
        'points',
        'points_per_game',
        'form',
        'top_scorer',
        'top_keeper',
        'xg_for',
        'xg_against',
        'xg_diff',
        'detailed_stats',
    ];

    protected $casts = [
        'detailed_stats' => 'array',
        'xg_for' => 'decimal:2',
        'xg_against' => 'decimal:2',
        'xg_diff' => 'decimal:2',
        'points_per_game' => 'decimal:2',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function league()
    {
        return $this->belongsTo(League::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }
}
