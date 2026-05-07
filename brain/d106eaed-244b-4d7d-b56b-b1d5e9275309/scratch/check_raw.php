<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../bootstrap/app.php';

use Illuminate\Support\Facades\Http;

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = env('WC2026_API_KEY');
$baseUrl = 'https://wc2026-api.vercel.app/api';

echo "--- RAW API CHECK ---\n";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Accept' => 'application/json',
])->withoutVerifying()->get("{$baseUrl}/teams");

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";

echo "---------------------\n";
