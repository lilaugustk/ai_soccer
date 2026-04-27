<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballStanding extends Model
{
    protected $fillable = [
        'league_id', 'team_id', 'season', 'rank', 'points', 'played',
        'win', 'draw', 'lose', 'goals_for', 'goals_against',
        'group', 'description', 'form'
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
