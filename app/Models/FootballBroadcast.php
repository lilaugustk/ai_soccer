<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballBroadcast extends Model
{
    protected $table = 'broadcasts';

    protected $fillable = [
        'event_id', 'country_code', 'channel_id', 'scheduled_start_time'
    ];

    protected $casts = [
        'scheduled_start_time' => 'datetime'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }

    public function channel()
    {
        return $this->belongsTo(FootballTvChannel::class, 'channel_id');
    }
}
