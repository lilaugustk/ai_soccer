<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Total players: " . App\Models\Player::count() . "\n";
$p = App\Models\Player::where('name', 'LIKE', '%mount%')->first();
if ($p) {
    echo "Found: " . $p->name . " (ID: " . $p->id . ")\n";
} else {
    echo "Not found with 'mount'\n";
    echo "Top 5 players in DB:\n";
    foreach (App\Models\Player::limit(5)->get() as $pl) {
        echo "- " . $pl->name . "\n";
    }
}
