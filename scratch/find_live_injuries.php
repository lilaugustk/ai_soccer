<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = app(App\Services\FootballApiService::class);

// Fetch live matches
$response = Http::withHeaders([
    'x-apisports-key' => config('services.football_api.key'),
])->get('https://v3.football.api-sports.io/fixtures', [
    'live' => 'all'
]);

$liveMatches = $response->json()['response'] ?? [];

echo "Checking live matches for injuries:\n";
foreach ($liveMatches as $m) {
    $fid = $m['fixture']['id'];
    $inj = $api->getFixtureInjuries($fid);
    echo "Match $fid ({$m['teams']['home']['name']} vs {$m['teams']['away']['name']}): " . count($inj) . " injuries\n";
    if (count($inj) > 0) {
        echo "FOUND DATA!\n";
        break;
    }
}
