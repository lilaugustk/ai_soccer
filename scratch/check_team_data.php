<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = 33;
$count = App\Models\FootballMatch::where(function($query) use ($id) {
    $query->where('home_team_id', $id)
          ->orWhere('away_team_id', $id);
})->where('season', 2024)->count();

echo "Count for 2024: " . $count . "\n";

$squadCount = App\Models\Player::where('current_team_id', $id)->count();
echo "Squad count: " . $squadCount . "\n";
