<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballFunfact extends Model
{
    protected $table = 'funfacts';

    protected $fillable = ['event_id', 'type_id', 'sentence'];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }
}
