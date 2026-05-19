<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FavoriteTeamController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BsdTestController;
use App\Models\FootballSeason;
use App\Services\BsdSportsApiService;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;

Route::get('/bsd/test-live', [BsdTestController::class, 'testLiveEvents']);
Route::get('/bsd/sync-leagues', [BsdTestController::class, 'syncLeagues']);
Route::get('/bsd/sync-matches', [BsdTestController::class, 'syncMatches']);

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/api/dashboard/sync', [DashboardController::class, 'syncMatches'])->name('api.dashboard.sync');
Route::get('/api/search', [SearchController::class, 'index'])->name('api.search');

Route::get('/matches', function () {
    return redirect()->route('dashboard');
});
Route::get('/matches/{id}', [GameController::class, 'show'])->name('games.show');
Route::post('/matches/{id}/sync', [GameController::class, 'sync'])->name('games.sync');
Route::get('/matches/{id}/sync', [GameController::class, 'sync']);

Route::get('/super-sync', function(Request $request, BsdSportsApiService $api) {
    $leagueId = $request->input('league_id', 1);
    echo "<h1>Super Sync Status for League {$leagueId}</h1>";
    
    echo "1. Syncing League {$leagueId}... ";
    $api->syncLeagues();
    echo "Done.<br>";

    echo "2. Syncing Teams for League {$leagueId}... ";
    $api->syncTeamsByLeague($leagueId);
    echo "Done.<br>";

    echo "3. Syncing Standings... ";
    $currentSeasonId = FootballSeason::query()->where('league_id', $leagueId)->where('is_current', true)->value('id');
    if ($currentSeasonId) {
        $api->syncStandings($leagueId, $currentSeasonId);
        echo "Done (Season {$currentSeasonId}).<br>";
    } else {
        echo "Skipped (No current season found).<br>";
    }

    echo "4. Syncing Recent Matches... ";
    $matches = $api->syncMatchesByDate(now()->toDateString());
    echo "Done (Found " . count($matches) . " matches).<br>";

    if (!empty($matches)) {
        $firstMatchId = $matches[0]['id'];
        $sId = $matches[0]['season_id'] ?? $currentSeasonId;
        echo "5. Hydrating details for Match ID {$firstMatchId}... ";
        $api->hydrateMatch($firstMatchId);
        echo "Done.<br>";
        
        echo "6. Syncing Players for Home Team (" . $matches[0]['home_team_id'] . ")... ";
        if ($sId) {
            $api->syncPlayersByTeam($matches[0]['home_team_id'], $sId);
            echo "Done.<br>";
        } else {
            echo "Skipped (No season ID).<br>";
        }
    }

    echo "<h2>Super Sync Finished Successfully!</h2>";
    echo "<p>Tất cả dữ liệu đã được phân bổ vào 43 bảng chuẩn hóa.</p>";
});
Route::get('/predictions', [GameController::class, 'predictions'])->name('predictions.index');

use App\Http\Controllers\WorldCupController;
Route::get('/world-cup-2026', [WorldCupController::class, 'index'])->name('world-cup.index');
Route::get('/api/wc2026/live', [WorldCupController::class, 'getLiveMatch'])->name('api.wc2026.live');
Route::get('/api/wc2026/matches', [WorldCupController::class, 'getMatches'])->name('api.wc2026.matches');

Route::get('/leagues', function () {
    return redirect()->route('dashboard');
});
Route::get('/leagues/{id}', [LeagueController::class, 'show'])->name('leagues.show');

Route::get('/teams', function () {
    return redirect()->route('dashboard');
});
Route::get('/teams/{id}', [TeamController::class, 'show'])->name('teams.show');
Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
Route::get('/players/{id}', [PlayerController::class, 'show'])->name('players.show');

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// Google Auth
Route::get('auth/google', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');
Route::middleware('auth')->group(function () {
    Route::post('/teams/{id}/favorite', [FavoriteTeamController::class, 'toggle'])->name('teams.favorite');
    Route::get('/api/notifications', [NotificationController::class, 'index']);
    Route::post('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/api/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});
