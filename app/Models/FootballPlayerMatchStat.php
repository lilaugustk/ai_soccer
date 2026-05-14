<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballPlayerMatchStat extends Model
{
    protected $table = 'player_match_stats';

    protected $fillable = [
        'player_id', 'event_id', 'team_id', 'minutes_played', 'rating',
        'goals', 'goal_assist', 'expected_goals', 'expected_assists',
        'total_shots', 'shots_on_target', 'total_pass', 'accurate_pass',
        'key_pass', 'total_tackle', 'interception', 'yellow_card', 'red_card',
        'saves'
    ];

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }
}
