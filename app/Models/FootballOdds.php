<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballOdds extends Model
{
    protected $table = 'odds';

    protected $fillable = [
        'event_id', 'bookmaker_slug', 'market', 'outcome', 'outcome_name',
        'decimal_odds', 'previous_decimal_odds', 'implied_probability',
        'movement', 'is_max_quote'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function bookmaker()
    {
        return $this->belongsTo(FootballBookmaker::class, 'bookmaker_slug', 'slug');
    }
}
