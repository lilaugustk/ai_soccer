<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FavoriteTeamController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/api/search', [SearchController::class, 'index'])->name('api.search');

Route::get('/matches', function () {
    return redirect()->route('dashboard');
});
Route::get('/matches/{id}', [GameController::class, 'show'])->name('games.show');
Route::get('/predictions', [GameController::class, 'predictions'])->name('predictions.index');

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
