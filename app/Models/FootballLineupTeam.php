<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballLineupTeam extends Model
{
    protected $table = 'lineup_teams';

    protected $fillable = ['lineup_id', 'side', 'team_id', 'formation', 'confidence'];

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }

    public function players()
    {
        return $this->hasMany(FootballLineupPlayer::class, 'lineup_team_id');
    }

    public function lineup()
    {
        return $this->belongsTo(FootballLineup::class, 'lineup_id');
    }
}
