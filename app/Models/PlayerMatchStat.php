<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerMatchStat extends Model
{
    protected $table = 'football_player_match_stats';

    protected $fillable = [
        'match_id',
        'player_id',
        'team_id',
        'position',
        'age',
        'minutes',
        'detailed_stats'
    ];

    protected $casts = [
        'detailed_stats' => 'array'
    ];

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'match_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class, 'player_id');
    }

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'team_id');
    }
}
