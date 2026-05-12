<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballManager extends Model
{
    protected $table = 'managers';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'short_name', 'country',
        'tactical_profile', 'preferred_formation',
        'current_team_id', 'matches_total', 'wins', 'draws', 'losses',
        'win_pct', 'avg_goals_scored', 'avg_goals_conceded',
        'avg_possession', 'clean_sheet_pct', 'btts_pct', 'over_25_pct',
        'stats_updated_at'
    ];

    public function currentTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'current_team_id');
    }

    public function getPhotoAttribute()
    {
        return "https://sports.bzzoiro.com/img/manager/{$this->id}/";
    }

    public function careers()
    {
        return $this->hasMany(FootballManagerCareer::class, 'manager_id');
    }
}
