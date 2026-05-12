<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballXgPerMinute extends Model
{
    protected $table = 'xg_per_minute';

    protected $fillable = [
        'event_id', 'minute', 'xg_home', 'xg_away', 'cum_home', 'cum_away'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }
}
