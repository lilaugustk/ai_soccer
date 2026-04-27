<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $table = 'football_players';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'firstname',
        'lastname',
        'nationality',
        'position',
        'birth_year',
        'height',
        'weight',
        'injured',
        'number',
        'current_team_id',
        'photo',
        'transfers',
        'trophies',
        'sidelined_history',
    ];

    protected $casts = [
        'transfers'         => 'array',
        'trophies'          => 'array',
        'sidelined_history' => 'array',
        'injured'           => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'current_team_id');
    }

    public function matchStats()
    {
        return $this->hasMany(PlayerMatchStat::class);
    }
    
    public function seasonStats()
    {
        return $this->hasMany(PlayerSeasonStat::class);
    }
    
    public function latestSeasonStat()
    {
        return $this->hasOne(PlayerSeasonStat::class)->latestOfMany();
    }
}
