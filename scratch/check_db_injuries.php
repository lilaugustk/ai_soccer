<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = 1379316;
$match = App\Models\FootballMatch::find($id);

if (!$match) {
    echo "Match $id not found in DB.\n";
    exit;
}

echo "Match $id from DB:\n";
echo "Injuries column: " . (is_array($match->injuries) ? "ARRAY (count: " . count($match->injuries) . ")" : gettype($match->injuries)) . "\n";
echo "Data: " . json_encode($match->injuries) . "\n";
