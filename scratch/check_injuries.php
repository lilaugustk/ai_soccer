<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$api = app(App\Services\FootballApiService::class);
$id = 1379316;
$res = $api->getFixtureInjuries($id);

echo "Injury data for Match $id:\n";
echo json_encode($res, JSON_PRETTY_PRINT);
