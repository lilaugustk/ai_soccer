<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballMatch extends Model
{
    protected $table = 'events';
    public $incrementing = false;

    protected $fillable = [
        'id', 'league_id', 'season_id', 'home_team_id', 'away_team_id',
        'home_coach_id', 'away_coach_id', 'referee_id', 'venue_id',
        'event_date', 'status', 'round_number', 'period', 'current_minute',
        'home_score', 'away_score', 'home_score_ht', 'away_score_ht',
        'penalty_shootout', 'is_local_derby', 'is_neutral_ground',
        'travel_distance_km', 'weather_code', 'weather_description',
        'weather_wind_speed', 'weather_temperature_c', 'pitch_condition',
        'attendance', 'live_websocket', 'last_updated'
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'last_updated' => 'datetime',
        'penalty_shootout' => 'array',
    ];

    protected $appends = ['match_at'];

    public function getMatchAtAttribute()
    {
        return $this->event_date;
    }

    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function season()
    {
        return $this->belongsTo(FootballSeason::class, 'season_id');
    }

    public function homeTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'away_team_id');
    }

    public function referee()
    {
        return $this->belongsTo(FootballReferee::class, 'referee_id');
    }

    public function homeCoach()
    {
        return $this->belongsTo(FootballManager::class, 'home_coach_id');
    }

    public function awayCoach()
    {
        return $this->belongsTo(FootballManager::class, 'away_coach_id');
    }

    public function venue()
    {
        return $this->belongsTo(FootballVenue::class, 'venue_id');
    }

    public function incidents()
    {
        return $this->hasMany(FootballIncident::class, 'event_id');
    }

    public function shotmap()
    {
        return $this->hasMany(FootballShotmap::class, 'event_id');
    }

    public function momentum()
    {
        return $this->hasMany(FootballMomentum::class, 'event_id');
    }


    public function stats()
    {
        return $this->hasMany(FootballEventStat::class, 'event_id');
    }

    public function lineup()
    {
        return $this->hasOne(FootballLineup::class, 'event_id');
    }
}
