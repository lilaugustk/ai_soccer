<?php

use App\Services\WorldCupService;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = new WorldCupService();

echo "TEAMS:\n";
$teams = $service->getTeams();
print_r(array_slice($teams, 0, 1));

echo "\nGROUPS:\n";
$groups = $service->getGroups();
print_r(array_slice($groups, 0, 1));

echo "\nSTADIUMS:\n";
$stadiums = $service->getStadiums();
print_r(array_slice($stadiums, 0, 1));

echo "\nMATCHES:\n";
$matches = $service->getMatches();
print_r(array_slice($matches, 0, 1));
