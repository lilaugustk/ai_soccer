<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballVenue extends Model
{
    protected $table = 'venues';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'city', 'country', 'country_code', 'capacity',
        'latitude', 'longitude', 'pitch_length_m', 'pitch_width_m',
        'built_year', 'home_team_id'
    ];

    public function homeTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'home_team_id');
    }

    public function competitions()
    {
        return $this->hasMany(FootballVenueCompetition::class, 'venue_id');
    }
}
