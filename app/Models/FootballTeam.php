<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballTeam extends Model
{
    protected $table = 'teams';
    public $incrementing = false;
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = ['id', 'name', 'short_name', 'country', 'country_code', 'venue_id', 'is_women', 'logo_url'];

    // Khai báo các thuộc tính ảo gửi lên Frontend
    protected $appends = ['logo', 'logo_url'];

    /**
     * Thuộc tính ảo: logo_url (Tự động sinh từ ID BSD)
     */
    public function getLogoUrlAttribute()
    {
        return "https://sports.bzzoiro.com/img/team/{$this->id}/";
    }

    public function getLogoAttribute()
    {
        return $this->logo_url;
    }

    public function venue()
    {
        return $this->belongsTo(FootballVenue::class, 'venue_id');
    }

    public function standings()
    {
        return $this->hasMany(FootballStanding::class, 'team_id');
    }

    public function players()
    {
        return $this->hasMany(FootballPlayer::class, 'current_team_id');
    }

    public function managerCareers()
    {
        return $this->hasMany(FootballManagerCareer::class, 'team_id');
    }
}
