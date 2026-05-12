<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballUnavailablePlayer extends Model
{
    protected $table = 'unavailable_players';

    protected $fillable = [
        'lineup_id', 'player_id', 'status', 'reason'
    ];

    public function lineup()
    {
        return $this->belongsTo(FootballLineup::class, 'lineup_id');
    }

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }
}
