<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballSeason extends Model
{
    protected $fillable = [
        'league_id', 
        'fbref_id',
        'name',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function games()
    {
        return $this->hasMany(FootballMatch::class);
    }
}
