<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballLeague extends Model
{
    protected $fillable = [
        'id', 'name', 'type', 'logo', 'country_name', 'country_code'
    ];

    public $incrementing = false; // Dùng ID từ API

    public function matches()
    {
        return $this->hasMany(FootballMatch::class, 'league_id');
    }

    public function standings()
    {
        return $this->hasMany(FootballStanding::class, 'league_id');
    }
}

