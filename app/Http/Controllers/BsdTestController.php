<?php

namespace App\Http\Controllers;

use App\Services\BsdSportsApiService;
use Illuminate\Http\Request;

class BsdTestController extends Controller
{
    protected $bsdService;

    public function __construct(BsdSportsApiService $bsdService)
    {
        $this->bsdService = $bsdService;
    }

    public function testLiveEvents()
    {
        $data = $this->bsdService->getLiveEvents();
        return response()->json($data);
    }

    public function syncLeagues()
    {
        $data = $this->bsdService->syncLeagues();
        return response()->json([
            'message' => 'Synced ' . count($data) . ' leagues',
            'data' => $data
        ]);
    }

    public function syncMatches()
    {
        $date = request('date', now()->toDateString());
        $data = $this->bsdService->syncMatchesByDate($date);
        return response()->json([
            'message' => "Synced " . count($data) . " matches for {$date}",
            'data' => $data
        ]);
    }
}
