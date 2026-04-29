<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SearchController;
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