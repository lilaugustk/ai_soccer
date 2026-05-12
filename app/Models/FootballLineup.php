<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballLineup extends Model
{
    protected $table = 'lineups';

    protected $fillable = ['event_id', 'lineup_status', 'beta', 'updated_at'];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function teams()
    {
        return $this->hasMany(FootballLineupTeam::class, 'lineup_id');
    }

    public function unavailablePlayers()
    {
        return $this->hasMany(FootballUnavailablePlayer::class, 'lineup_id');
    }
}
