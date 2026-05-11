<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $nationality
 * @property string|null $position
 * @property int|null $birth_year
 * @property string|null $birth_date
 * @property string|null $birth_place
 * @property string|null $birth_country
 * @property string|null $height
 * @property string|null $weight
 * @property bool $injured
 * @property int|null $number
 * @property int|null $current_team_id
 * @property string|null $photo
 * @property array|null $transfers
 * @property array|null $trophies
 * @property array|null $sidelined_history
 * @property array|null $available_seasons
 * @property-read FootballTeam|null $team
 */
class FootballPlayer extends Model
{
    protected $table = 'football_players';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'firstname',
        'lastname',
        'nationality',
        'position',
        'birth_year',
        'birth_date',
        'birth_place',
        'birth_country',
        'height',
        'weight',
        'injured',
        'number',
        'current_team_id',
        'photo',
        'transfers',
        'trophies',
        'sidelined_history',
        'available_seasons',
    ];

    protected $casts = [
        'transfers'         => 'array',
        'trophies'          => 'array',
        'sidelined_history' => 'array',
        'available_seasons' => 'array',
        'injured'           => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(FootballTeam::class, 'current_team_id');
    }

    public function matchStats()
    {
        return $this->hasMany(PlayerMatchStat::class, 'player_id');
    }
    
    public function seasonStats()
    {
        return $this->hasMany(PlayerSeasonStat::class, 'player_id');
    }
    
    public function latestSeasonStat()
    {
        return $this->hasOne(PlayerSeasonStat::class, 'player_id')->latestOfMany();
    }
}
