<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballEventStat extends Model
{
    protected $table = 'event_stats';

    protected $fillable = [
        'event_id', 'team_id', 'side', 'total_shots', 'ball_possession',
        'crosses_value', 'crosses_total', 'crosses_pct',
        'dribbles_value', 'dribbles_total', 'dribbles_pct',
        'long_balls_value', 'long_balls_total', 'long_balls_pct',
        'attack', 'ball_safe', 'dangerous_attack',
        'pass_accuracy_pct', 'xg_actual'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }
}
