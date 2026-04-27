<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $table = 'football_teams'; // Trỏ về bảng mới
    public $incrementing = false; // Dùng ID từ API

    protected $fillable = ['id', 'name', 'logo'];

    public function homeGames()
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayGames()
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    public function standings()
    {
        return $this->hasMany(FootballStanding::class, 'team_id');
    }
}
