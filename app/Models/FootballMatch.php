<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballMatch extends Model
{
    protected $fillable = [
        'id', 'league_id', 'home_team_id', 'away_team_id',
        'match_at', 'status', 'round', 'referee', 'venue_name', 'venue_city', 'attendance',
        'home_score', 'away_score', 'season',
        'events', 'lineups', 'statistics', 'stats_1h', 'stats_2h', 'players', 'predictions'
    ];

    public $incrementing = false; // Fixture ID từ API

    protected $casts = [
        'match_at' => 'datetime',
        'events' => 'array',
        'lineups' => 'array',
        'statistics' => 'array',
        'stats_1h' => 'array',
        'stats_2h' => 'array',
        'predictions' => 'array',
        'players' => 'array',
    ];

    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'away_team_id');
    }
}
