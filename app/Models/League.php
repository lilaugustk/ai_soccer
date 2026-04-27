<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $table = 'football_leagues'; // Trỏ về bảng mới
    public $incrementing = false; // Dùng ID từ API (39, 140...)

    protected $fillable = [
        'id', 'name', 'type', 'logo', 'country_name', 'country_code', 'is_featured'
    ];

    public function games()
    {
        return $this->hasMany(FootballMatch::class, 'league_id');
    }

    public function standings()
    {
        return $this->hasMany(FootballStanding::class, 'league_id');
    }
}
