<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballPlayerTransfer extends Model
{
    protected $table = 'player_transfers';

    protected $fillable = [
        'player_id', 'transfer_date', 'from_team_id', 'to_team_id',
        'fee_eur', 'fee_description', 'transfer_type'
    ];

    public function player()
    {
        return $this->belongsTo(FootballPlayer::class, 'player_id');
    }

    public function fromTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'from_team_id');
    }

    public function toTeam()
    {
        return $this->belongsTo(FootballTeam::class, 'to_team_id');
    }
}
