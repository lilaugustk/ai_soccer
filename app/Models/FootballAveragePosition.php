<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballAveragePosition extends Model
{
    protected $table = 'average_positions';

    protected $fillable = [
        'event_id', 'player_id', 'x', 'y'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }
}
