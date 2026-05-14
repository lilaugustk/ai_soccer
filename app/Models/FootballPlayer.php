<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballPlayer extends Model
{
    protected $table = 'players';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'short_name', 'position', 'specific_position',
        'jersey_number', 'date_of_birth', 'height_cm', 'weight_kg',
        'preferred_foot', 'nationality', 'nationality_code',
        'current_team_id', 'national_team_id', 'market_value_eur',
        'contract_until', 'availability'
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute()
    {
        return "https://sports.bzzoiro.com/img/player/{$this->id}/";
    }

    public function currentTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'current_team_id');
    }

    public function team()
    {
        return $this->currentTeam();
    }

    public function latestSeasonStat()
    {
        return $this->hasOne(FootballPlayerCareerStat::class, 'player_id')->latestOfMany();
    }

    public function careerStats()
    {
        return $this->hasMany(FootballPlayerCareerStat::class, 'player_id');
    }

    public function seasonStats()
    {
        return $this->careerStats();
    }
}
