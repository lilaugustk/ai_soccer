<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/matches/sync-states', function (Request $request) {
    $ids = explode(',', $request->input('ids', ''));
    if (empty($ids) || (count($ids) === 1 && $ids[0] === '')) {
        return response()->json([]);
    }
    
    return \App\Models\FootballMatch::with(['homeTeam', 'awayTeam'])
        ->whereIn('id', $ids)
        ->get();
});

Route::get('/world-cup/live', [\App\Http\Controllers\WorldCupController::class, 'getLiveMatch']);