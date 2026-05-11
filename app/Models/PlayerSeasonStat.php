<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerSeasonStat extends Model
{
    protected $table = 'football_player_season_stats';

    protected $fillable = [
        'player_id',
        'season',
        'league_id',
        'team_id',
        'games',
        'games_starts',
        'minutes',
        'goals',
        'assists',
        'cards_yellow',
        'cards_red',
        'detailed_stats'
    ];

    protected $casts = [
        'detailed_stats' => 'array'
    ];

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }


    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }
}
