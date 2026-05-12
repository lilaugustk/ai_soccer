<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballLineupPlayer extends Model
{
    protected $table = 'lineup_players';

    protected $fillable = [
        'lineup_team_id', 'player_id', 'position', 'grid', 'jersey_number',
        'ai_score', 'is_substitute'
    ];

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }

    public function lineupTeam()
    {
        return $this->belongsTo(FootballLineupTeam::class, 'lineup_team_id');
    }
}
