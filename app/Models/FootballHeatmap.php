<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballHeatmap extends Model
{
    protected $table = 'event_heatmaps';
    public $timestamps = false;

    protected $fillable = [
        'event_id', 'team_id', 'player_id', 'x', 'y', 'value'
    ];

    public function match()
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
