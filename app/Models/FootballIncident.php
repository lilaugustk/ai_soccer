<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballIncident extends Model
{
    protected $table = 'incidents';

    protected $fillable = [
        'id', 'event_id', 'type', 'minute', 'is_home', 
        'player_id', 'player_in_id', 'player_out_id', 
        'card_type', 'period_label', 'confirmed', 'is_live', 'payload'
    ];

    protected $casts = [
        'payload' => 'array'
    ];

    public function match()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }

    public function playerIn()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_in_id');
    }

    public function playerOut()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_out_id');
    }
}
