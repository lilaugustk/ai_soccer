<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$teamId = 33;
$game = App\Models\FootballMatch::where('home_team_id', $teamId)
    ->orWhere('away_team_id', $teamId)
    ->whereNotNull('players')
    ->orderBy('match_at', 'desc')
    ->first();

if ($game) {
    echo "Game ID: " . $game->id . "\n";
    // Check if players is string or array (it should be array due to $casts)
    $players = is_string($game->players) ? json_decode($game->players, true) : $game->players;
    
    // Look for lineup of team 33
    $teamLineup = null;
    if (isset($players['home']['team']['id']) && $players['home']['team']['id'] == $teamId) {
        $teamLineup = $players['home'];
    } elseif (isset($players['away']['team']['id']) && $players['away']['team']['id'] == $teamId) {
        $teamLineup = $players['away'];
    }
    
    if ($teamLineup) {
        echo "Found lineup for team $teamId\n";
        echo "Formation: " . ($teamLineup['formation'] ?? 'N/A') . "\n";
        echo "Starting XI (First 3):\n";
        foreach (array_slice($teamLineup['startXI'] ?? [], 0, 3) as $p) {
            echo "- " . ($p['player']['name'] ?? 'N/A') . " (Pos: " . ($p['player']['pos'] ?? 'N/A') . ", Grid: " . ($p['player']['grid'] ?? 'N/A') . ")\n";
        }
    } else {
        // Maybe it's structured differently?
        echo "Structure:\n";
        echo json_encode(array_keys($players), JSON_PRETTY_PRINT) . "\n";
        // If it's the 0, 1 structure
        if (isset($players[0]['team']['id'])) {
             foreach($players as $t) {
                 if ($t['team']['id'] == $teamId) {
                     echo "Found in numeric index\n";
                     break;
                 }
             }
        }
    }
} else {
    echo "No match with players found.\n";
}
