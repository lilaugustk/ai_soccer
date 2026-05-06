<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballTeam extends Model
{
    protected $fillable = [
        'id', 'name', 'code', 'logo', 'country', 'coach_history', 'coach_history_last_sync'
    ];

    protected $casts = [
        'coach_history' => 'array',
        'coach_history_last_sync' => 'datetime',
    ];

    public $incrementing = false; // Dùng ID từ API

    public function homeMatches()
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }
}
