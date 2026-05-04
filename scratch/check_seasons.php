<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = 33;
$seasons = App\Models\FootballMatch::where(function($query) use ($id) {
    $query->where('home_team_id', $id)
          ->orWhere('away_team_id', $id);
})->distinct()->pluck('season');

print_r($seasons->toArray());
