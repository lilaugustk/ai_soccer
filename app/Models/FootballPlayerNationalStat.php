<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballPlayerNationalStat extends Model
{
    protected $table = 'player_national_stats';
    protected $primaryKey = 'player_id';
    public $incrementing = false;

    protected $fillable = [
        'player_id', 'national_team_id', 'caps', 'goals', 'last_appearance'
    ];

    protected $casts = [
        'last_appearance' => 'datetime'
    ];

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }

    public function nationalTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'national_team_id');
    }
}
