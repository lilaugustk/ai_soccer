<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$api = app(App\Services\FootballApiService::class);
echo "Calling getStandings(39, 2025)...\n";
$res = $api->getStandings(39, 2025);
echo "Done. Response length: " . count($res ?? []) . "\n";
