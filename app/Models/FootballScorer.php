<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballScorer extends Model
{
    protected $fillable = [
        'player_id', 'player_name', 'team_id', 'league_id', 'season',
        'goals', 'assists', 'photo'
    ];

    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }
}
