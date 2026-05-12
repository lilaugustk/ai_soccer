<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballEventMetadata extends Model
{
    protected $table = 'event_metadata';
    protected $primaryKey = 'event_id';
    public $incrementing = false;

    protected $fillable = [
        'event_id', 'jerseys', 'ai_preview_text', 'ai_preview_generated_at'
    ];

    protected $casts = [
        'jerseys' => 'array',
        'ai_preview_generated_at' => 'datetime'
    ];

    public function event()
    {
        return $this->belongsTo(FootballMatch::class, 'event_id');
    }
}
