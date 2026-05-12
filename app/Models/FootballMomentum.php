<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballMomentum extends Model
{
    protected $table = 'event_momentum';

    protected $fillable = ['event_id', 'minute', 'value'];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }
}
