<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballLeague extends Model
{
    protected $table = 'leagues';
    public $incrementing = false;
    public $timestamps = true;
    const UPDATED_AT = null;

    // Chỉ giữ lại các cột thực tế trong DB
    protected $fillable = ['id', 'name', 'country', 'is_women', 'is_active'];

    protected $casts = [
        'is_women' => 'boolean',
    ];

    // Khai báo các thuộc tính ảo muốn gửi lên Frontend
    protected $appends = ['logo', 'logo_url', 'country_code'];

    /**
     * Thuộc tính ảo: logo_url (Tự động sinh từ ID BSD)
     */
    public function getLogoUrlAttribute()
    {
        return "https://sports.bzzoiro.com/img/league/{$this->id}/";
    }

    public function getLogoAttribute()
    {
        return $this->logo_url;
    }

    /**
     * Thuộc tính ảo: country_code (Ánh xạ từ tên quốc gia để hiển thị cờ)
     */
    public function getCountryCodeAttribute()
    {
        $map = [
            'England' => 'gb-eng', 'Spain' => 'es', 'Italy' => 'it', 'Germany' => 'de',
            'France' => 'fr', 'Portugal' => 'pt', 'Brazil' => 'br', 'Netherlands' => 'nl',
            'Turkey' => 'tr', 'Scotland' => 'gb-sct', 'Belgium' => 'be', 'Switzerland' => 'ch',
            'Saudi Arabia' => 'sa', 'USA' => 'us', 'Mexico' => 'mx', 'Vietnam' => 'vn',
            'Poland' => 'pl', 'Sweden' => 'se', 'Norway' => 'no', 'Finland' => 'fi',
            'Nigeria' => 'ng', 'International' => 'un', 'World' => 'un', 'Europe' => 'eu',
            'Bulgaria' => 'bg', 'Romania' => 'ro', 'Greece' => 'gr', 'Africa' => 'un',
            'South America' => 'un', 'Tunisia' => 'tn', 'Japan' => 'jp', 'South Korea' => 'kr',
            'China' => 'cn', 'Morocco' => 'ma', 'Argentina' => 'ar'
        ];

        return $map[$this->country] ?? 'un';
    }

    public function seasons()
    {
        return $this->hasMany(FootballSeason::class, 'league_id');
    }

    public function standings()
    {
        return $this->hasMany(FootballStanding::class, 'league_id');
    }
}
