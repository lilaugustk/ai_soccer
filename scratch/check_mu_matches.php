<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = 33; // Manchester United
$now = Carbon\Carbon::now();

$upcoming = App\Models\FootballMatch::where(function($query) use ($id) {
    $query->where('home_team_id', $id)
          ->orWhere('away_team_id', $id);
})
->where('match_at', '>=', $now)
->count();

$recent = App\Models\FootballMatch::where(function($query) use ($id) {
    $query->where('home_team_id', $id)
          ->orWhere('away_team_id', $id);
})
->where('match_at', '<', $now)
->count();

echo "MU Upcoming: $upcoming\n";
echo "MU Recent: $recent\n";

if ($upcoming > 0) {
    $next = App\Models\FootballMatch::where(function($query) use ($id) {
        $query->where('home_team_id', $id)
              ->orWhere('away_team_id', $id);
    })
    ->where('match_at', '>=', $now)
    ->orderBy('match_at', 'asc')
    ->first();
    echo "Next match at: " . $next->match_at . "\n";
}
