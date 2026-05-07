<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';

use App\Services\WorldCupService;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new WorldCupService();

echo "--- DATA STRUCTURE CHECK ---\n";

$teams = $service->getTeams();
echo "Teams First Item:\n";
print_r($teams[0] ?? 'EMPTY');

$matches = $service->getMatches();
echo "\nMatches First Item:\n";
print_r($matches[0] ?? 'EMPTY');

$live = $service->getLiveSandbox();
echo "\nLive Sandbox:\n";
print_r($live);

echo "---------------------------\n";
