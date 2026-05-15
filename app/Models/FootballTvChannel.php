<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballTvChannel extends Model
{
    protected $table = 'tv_channels';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'country_code', 'link'];
}
