<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = app(App\Services\FootballApiService::class);
$matches = App\Models\FootballMatch::where('match_at', '>=', now()->subDays(1))
    ->where('match_at', '<=', now()->addDays(7))
    ->limit(10)
    ->get();

echo "Checking injuries for recent/upcoming matches:\n";
foreach ($matches as $match) {
    $res = $api->getFixtureInjuries($match->id);
    echo "Match {$match->id} ({$match->match_at}): " . count($res) . " injuries\n";
    if (count($res) > 0) {
        break; // Found one!
    }
}
