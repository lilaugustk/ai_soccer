<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = 1379316;
$m = App\Models\FootballMatch::find($id);
if (!$m) {
    echo "Match not found\n";
    exit;
}

echo "Lineups count: " . (is_array($m->lineups) ? count($m->lineups) : "not array") . "\n";
if (is_array($m->lineups)) {
    foreach ($m->lineups as $l) {
        echo "Team " . $l['team']['id'] . " StartXI: " . (isset($l['startXI']) ? count($l['startXI']) : "none") . "\n";
    }
}
echo "Players count: " . (is_array($m->players) ? count($m->players) : "not array") . "\n";
