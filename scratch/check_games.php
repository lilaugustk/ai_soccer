<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teamId = 33; // Manchester United
$games = App\Models\FootballMatch::where('home_team_id', $teamId)
    ->orWhere('away_team_id', $teamId)
    ->with(['homeTeam', 'awayTeam'])
    ->orderBy('match_at', 'desc')
    ->limit(5)
    ->get();

echo "--- 5 TRẬN GẦN NHẤT CỦA MAN UTD TRONG DB ---\n";
foreach ($games as $g) {
    echo "Ngày: " . $g->match_at . "\n";
    echo "Trận: " . ($g->homeTeam->name ?? 'N/A') . " [" . $g->home_score . "] - [" . $g->away_score . "] " . ($g->awayTeam->name ?? 'N/A') . "\n";
    echo "------------------------------------------\n";
}
