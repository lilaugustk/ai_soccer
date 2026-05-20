<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballStanding extends Model
{
    protected $table = 'standings';

    protected $fillable = [
        'league_id', 'season_id', 'team_id', 'position', 'played',
        'won', 'drawn', 'lost', 'gf', 'ga', 'gd', 'pts',
        'xgf', 'xga', 'xgd', 'form', 'is_live', 'description'
    ];

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }

    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function season()
    {
        return $this->belongsTo(FootballSeason::class, 'season_id');
    }
}
