<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballShotmap extends Model
{
    protected $table = 'shotmap';

    protected $fillable = [
        'event_id', 'team_id', 'player_id', 'minute', 'x', 'y', 'xg',
        'body_part', 'situation', 'is_goal'
    ];

    protected $casts = [
        'is_goal' => 'boolean'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }
}
