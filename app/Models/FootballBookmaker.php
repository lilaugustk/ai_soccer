<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FootballBookmaker extends Model
{
    protected $table = 'bookmakers';
    protected $primaryKey = 'slug';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['slug', 'name'];
}
