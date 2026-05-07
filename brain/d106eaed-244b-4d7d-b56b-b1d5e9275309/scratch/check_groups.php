<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';

use Illuminate\Support\Facades\Http;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = env('WC2026_API_KEY');
$baseUrl = 'https://api.wc2026api.com';

echo "--- RAW GROUPS CHECK ---\n";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Accept' => 'application/json',
])->withoutVerifying()->get("{$baseUrl}/groups");

echo "Status: " . $response->status() . "\n";
echo "Body: " . substr($response->body(), 0, 1000) . "...\n";

echo "---------------------\n";
