<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballEventPrediction extends Model
{
    protected $table = 'event_predictions';

    protected $fillable = [
        'event_id', 'prob_home', 'prob_draw', 'prob_away',
        'predicted_result', 'expected_home_goals', 'expected_away_goals',
        'prob_over_15', 'prob_over_25', 'prob_over_35',
        'prob_btts_yes', 'most_likely_score', 'favorite',
        'favorite_prob', 'bet_favorite', 'over_15', 'over_25',
        'over_35', 'btts', 'winner', 'confidence', 'model_version'
    ];

    protected $casts = [
        'bet_favorite' => 'boolean',
        'over_15' => 'boolean',
        'over_25' => 'boolean',
        'over_35' => 'boolean',
        'btts' => 'boolean',
        'winner' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }
}
