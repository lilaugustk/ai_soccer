<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$match = App\Models\FootballMatch::whereNotNull('injuries')
    ->where('injuries', '!=', '[]')
    ->where('injuries', '!=', '')
    ->first();

if ($match) {
    echo "Found match with injuries: " . $match->id . "\n";
    echo "League: " . $match->league_id . "\n";
    echo "Injuries count: " . count($match->injuries) . "\n";
} else {
    echo "No matches found with injuries in DB.\n";
}
