<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballManagerCareer extends Model
{
    protected $table = 'manager_careers';

    protected $fillable = [
        'manager_id', 'team_id', 'date_from', 'date_to',
        'matches', 'wins', 'draws', 'losses', 'win_pct'
    ];

    public function manager()
    {
        return $this->belongsTo(FootballManager::class, 'manager_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }
}
