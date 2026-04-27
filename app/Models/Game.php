<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $table = 'football_matches'; // Trỏ về bảng mới
    public $incrementing = false; // Dùng ID từ API

    protected $fillable = [
        'id', 'league_id', 'home_team_id', 'away_team_id', 'match_at',
        'status', 'round', 'referee', 'venue_name', 'venue_city',
        'attendance', 'home_score', 'away_score', 'events', 'lineups',
        'statistics', 'predictions'
    ];

    protected $casts = [
        'events' => 'array',
        'lineups' => 'array',
        'statistics' => 'array',
        'predictions' => 'array',
    ];

    // --- MAPPING ACCESSORS (Giúp các View cũ vẫn chạy được) ---
    public function getMatchDatetimeAttribute() { return $this->match_at; }
    public function getVenueAttribute() { return $this->venue_name . ($this->venue_city ? ", {$this->venue_city}" : ""); }
    public function getMatchEventsJsonAttribute() { return $this->events; }
    public function getTeamStatsAttribute() { return $this->statistics; }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function league()
    {
        return $this->belongsTo(League::class, 'league_id');
    }
}
