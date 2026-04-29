<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = ['football_leagues', 'football_teams', 'football_matches', 'football_players', 'football_standings', 'football_scorers'];

foreach($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "--- TABLE: $table ---\n";
        $columns = Schema::getColumnListing($table);
        foreach($columns as $col) {
            $type = Schema::getColumnType($table, $col);
            echo "  - $col ($type)\n";
        }
    } else {
        echo "!!! TABLE MISSING: $table !!!\n";
    }
    echo "\n";
}
