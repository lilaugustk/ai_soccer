<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$m = App\Models\FootballMatch::find(1379305);
if ($m && $m->predictions) {
    echo "Keys: " . implode(', ', array_keys($m->predictions)) . "\n";
    if (isset($m->predictions['predictions'])) {
        echo "Found 'predictions' key.\n";
    } else {
        echo "TOP-LEVEL keys instead.\n";
    }
} else {
    echo "No predictions found.\n";
}
