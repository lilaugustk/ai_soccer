<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballPlayerCareerStat extends Model
{
    protected $table = 'player_career_stats';

    protected $fillable = [
        'player_id', 'season_id', 'league_id', 'team_id',
        'matches', 'minutes', 'goals', 'assists', 'avg_rating'
    ];

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }

    public function season()
    {
        return $this->belongsTo(FootballSeason::class, 'season_id');
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
