<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballSeason extends Model
{
    protected $table = 'seasons';
    public $incrementing = false;

    protected $fillable = ['id', 'league_id', 'name', 'year', 'start_date', 'end_date', 'is_current'];

    public function league()
    {
        return $this->belongsTo(FootballLeague::class, 'league_id');
    }

    public function standings()
    {
        return $this->hasMany(FootballStanding::class, 'season_id');
    }

    public function matches()
    {
        return $this->hasMany(FootballMatch::class, 'season_id');
    }
}
