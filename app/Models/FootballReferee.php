<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballReferee extends Model
{
    protected $table = 'referees';
    public $incrementing = false;

    protected $fillable = [
        'id', 'name', 'country', 'nationality_a3', 'birthdate', 'matches',
        'total_yellow_cards', 'total_red_cards',
        'avg_yellow_per_match', 'avg_red_per_match',
        'avg_goals_per_match', 'avg_fouls_per_match',
        'career_games', 'career_yellow_cards', 'career_red_cards'
    ];
}
