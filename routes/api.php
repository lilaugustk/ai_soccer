<?php

use App\Http\Controllers\WorldCupController;
use App\Models\FootballMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/matches/sync-states', function (Request $request) {
    $ids = explode(',', $request->input('ids', ''));
    if (empty($ids) || (count($ids) === 1 && $ids[0] === '')) {
        return response()->json([]);
    }
    
    return FootballMatch::with(['homeTeam', 'awayTeam'])
        ->whereIn('id', $ids)
        ->get();
});

/**
 * Endpoint nhẹ để frontend polling kiểm tra trạng thái sync.
 * Trả về: isSyncing (job đang chạy?), lastHydratedAt (lần cuối data được cập nhật)
 * Frontend dùng endpoint này thay vì reload toàn bộ page.
 */
Route::get('/matches/{id}/sync-status', function (Request $request, $id) {
    $isSyncing     = Cache::has("hydrate_match_job_{$id}");
    $lastHydratedAt = Cache::get("match_hydrated_{$id}");

    return response()->json([
        'id'             => (int) $id,
        'is_syncing'     => $isSyncing,
        'last_hydrated'  => $lastHydratedAt,
    ]);
});

Route::get('/world-cup/live', [WorldCupController::class, 'getLiveMatch']);
