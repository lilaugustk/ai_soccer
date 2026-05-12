<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballVenueCompetition extends Model
{
    protected $table = 'venue_competitions';

    protected $fillable = [
        'venue_id', 'league_id', 'season_id', 'host_country_code',
        'hosts_opening', 'hosts_final', 'hosts_third_place',
        'rounds', 'match_count', 'fifa_role'
    ];

    protected $casts = [
        'rounds' => 'array',
        'hosts_opening' => 'boolean',
        'hosts_final' => 'boolean',
        'hosts_third_place' => 'boolean',
    ];

    public function venue()
    {
        return $this->belongsTo(FootballVenue::class, 'venue_id');
    }

    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function season()
    {
        return $this->belongsTo(FootballSeason::class, 'season_id');
    }
}
