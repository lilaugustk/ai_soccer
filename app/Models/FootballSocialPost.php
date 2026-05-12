<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballSocialPost extends Model
{
    protected $table = 'social_posts';
    public $incrementing = false;

    protected $fillable = [
        'id', 'type', 'url', 'text', 'title', 'thumbnail', 'media',
        'account_handle', 'account_name', 'account_verified', 'published_at'
    ];

    protected $casts = [
        'media' => 'array',
        'account_verified' => 'boolean',
        'published_at' => 'datetime'
    ];

    public function teams()
    {
        return $this->belongsToMany(FootballTeam::class, 'social_post_teams', 'social_post_id', 'team_id');
    }

    public function events()
    {
        return $this->belongsToMany(FootballMatch::class, 'social_post_events', 'social_post_id', 'event_id');
    }

    public function players()
    {
        return $this->belongsToMany(FootballPlayer::class, 'social_post_players', 'social_post_id', 'player_id');
    }

    public function managers()
    {
        return $this->belongsToMany(FootballManager::class, 'social_post_managers', 'social_post_id', 'manager_id');
    }
}
