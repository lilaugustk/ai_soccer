<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballTvChannel extends Model
{
    protected $table = 'tv_channels';

    protected $fillable = ['name', 'country_code', 'link'];
}
