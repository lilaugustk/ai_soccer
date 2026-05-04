<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = app(App\Services\FootballApiService::class);
$id = 1379316;
$res = $api->getFixtureDetails($id);

if (!$res) {
    echo "API call returned NULL. Check your API key or connectivity.\n";
    exit;
}

echo "API Response for Match $id:\n";
echo "Lineups: " . (isset($res['lineups']) ? count($res['lineups']) : "NONE") . "\n";
echo "Events: " . (isset($res['events']) ? count($res['events']) : "NONE") . "\n";
echo "Statistics: " . (isset($res['statistics']) ? count($res['statistics']) : "NONE") . "\n";

if (isset($res['lineups']) && count($res['lineups']) > 0) {
    echo "Home Team Lineup: " . count($res['lineups'][0]['startXI'] ?? []) . " players\n";
}
