<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballEventOddsConsensus extends Model
{
    protected $table = 'event_odds_consensus';
    protected $primaryKey = 'event_id';
    public $incrementing = false;

    protected $fillable = [
        'event_id', 'home_win', 'draw', 'away_win',
        'over_15_goals', 'over_25_goals', 'over_35_goals',
        'under_15_goals', 'under_25_goals', 'under_35_goals',
        'btts_yes', 'btts_no'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }
}
