<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables_to_inspect = ['football_leagues', 'football_matches', 'football_teams', 'football_players'];

foreach($tables_to_inspect as $name) {
    $count = DB::table($name)->count();
    echo "--- $name ($count records) ---\n";
    $samples = DB::table($name)->limit(3)->get();
    foreach($samples as $sample) {
        print_r($sample);
    }
    echo "\n";
}
