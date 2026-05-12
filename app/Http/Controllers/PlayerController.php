<?php

namespace App\Http\Controllers;

use App\Models\FootballPlayer;
use App\Models\FootballPlayerMatchStat;
use App\Models\FootballPlayerCareerStat;
use App\Models\FootballTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\FootballApiService;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $query = FootballPlayer::query()->with(['team', 'latestSeasonStat.league']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        if ($request->filled('nationality')) {
            $query->where('nationality', $request->nationality);
        }

        if ($request->filled('team_id')) {
            $query->where('current_team_id', $request->team_id);
        }

        $players = $query->paginate(24)->withQueryString();

        $nationalities = Cache::remember('player_nationalities', 3600, function () {
            return FootballPlayer::query()
                ->where('nationality', '!=', '')
                ->distinct()
                ->orderBy('nationality')
                ->pluck('nationality')
                ->map(fn($n) => trim($n))
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        });

        return Inertia::render('Players/Index', [
            'players'      => $players,
            'filters'      => $request->only(['search', 'position', 'nationality', 'team_id']),
            'nationalities' => $nationalities,
        ]);
    }

    protected $apiService;

    public function __construct(FootballApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function show(Request $request, $id)
    {
        // Try to find the player, if not found, fetch from API
        $player = FootballPlayer::query()->find($id);

        if (!$player) {
            // Player doesn't exist, fetch their latest season profile
            $this->apiService->syncPlayerIfNotFound($id);
            $player = FootballPlayer::findOrFail($id); // If still fails, it throws 404
        }

        // Fetch seasons list if available_seasons is null or profile is incomplete
        if (is_null($player->available_seasons) || is_null($player->firstname) || is_null($player->birth_date)) {
            $this->apiService->syncPlayerIfNotFound($id);
            $player->refresh();
        }

        // Determine requested season (default to latest available or 2024)
        $availableSeasons = $player->available_seasons ?: [];
        $requestedSeason = $request->input('season', (!empty($availableSeasons) ? max($availableSeasons) : 2024));

        // Check if we need to sync from API for the requested season
        $hasStatForSeason = FootballPlayerCareerStat::query()->where('player_id', $id)
            ->where('season_id', $requestedSeason)
            ->exists();

        if (!$hasStatForSeason) {
            $this->apiService->getPlayerStats($id, $requestedSeason); // Sync for requested season
            $player->refresh();
        }

        $player->load(['team', 'seasonStats.league', 'seasonStats.team']);

        // Season stats sorted newest first — this is the "career timeline"
        $seasonStats = $player->seasonStats()
            ->with(['league', 'team'])
            ->orderByDesc('id')
            ->get();

        // Match history — last 30 games
        $matchHistory = FootballPlayerMatchStat::query()->where('player_id', $id)
            ->with([
                'match.homeTeam',
                'match.awayTeam',
                'match.league',
                'team',
            ])
            ->join('events', 'player_match_stats.event_id', '=', 'events.id')
            ->orderBy('events.event_date', 'desc')
            ->select('player_match_stats.*')
            ->limit(30)
            ->get();

        // Latest season stat for quick stats panel
        $latestStat = $seasonStats->first();

        // Sync additional career data if empty
        if (empty($player->transfers)) {
            $this->apiService->getPlayerTransfers($id);
            $player->refresh();
        }
        if (empty($player->trophies)) {
            $this->apiService->getPlayerTrophies($id);
            $player->refresh();
        }
        if (empty($player->sidelined_history)) {
            $this->apiService->getPlayerSidelined($id);
            $player->refresh();
        }

        // Aggregate career totals across all seasons
        $careerTotals = [
            'total_games'   => $seasonStats->sum('matches'),
            'total_starts'  => 0, // Need to add this field to table if needed
            'total_minutes' => $seasonStats->sum('minutes'),
            'total_goals'   => $seasonStats->sum('goals'),
            'total_assists' => $seasonStats->sum('assists'),
            'total_yellows' => 0,
            'total_reds'    => 0,
        ];

        // Derive available seasons from synced stats + player profile
        $syncedSeasons = $seasonStats->pluck('season_id')->unique()->toArray();
        $apiAvailableSeasons = $player->available_seasons ?: [];
        
        // Filter: Only include seasons where we have stats OR which are part of the player's recorded career
        // If apiAvailableSeasons looks generic (long list), we might want to be careful.
        // For now, let's merge synced ones with api ones, but we can filter by birth year if available.
        $finalSeasons = array_unique(array_merge($syncedSeasons, $apiAvailableSeasons));
        
        // If we have birth year, filter out impossible seasons (before age 15)
        if ($player->birth_year) {
            $finalSeasons = array_filter($finalSeasons, fn($s) => $s >= ($player->birth_year + 15));
        } elseif ($player->birth_date) {
            $birthYear = \Illuminate\Support\Carbon::parse($player->birth_date)->year;
            $finalSeasons = array_filter($finalSeasons, fn($s) => $s >= ($birthYear + 15));
        }

        sort($finalSeasons);
        $finalSeasons = array_reverse(array_values($finalSeasons));

        return Inertia::render('Players/Show', [
            'player'          => $player,
            'seasonStats'     => $seasonStats,
            'latestStat'      => $latestStat,
            'matchHistory'    => $matchHistory,
            'careerTotals'    => $careerTotals,
            'availableSeasons' => $finalSeasons,
            'currentSeason'   => (int)$requestedSeason,
        ]);
    }
}
