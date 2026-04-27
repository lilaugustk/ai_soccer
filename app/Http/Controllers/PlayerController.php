<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\PlayerMatchStat;
use App\Models\PlayerSeasonStat;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function index(Request $request)
    {
        $query = Player::query()->with(['team', 'latestSeasonStat.league']);

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
            return Player::query()
                ->whereNotNull('nationality')
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

    public function __construct(\App\Services\FootballApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function show($id)
    {
        // Load player first
        $player = Player::findOrFail($id);

        // Check if we need to sync from API (e.g. if no season stats or missing detailed_stats)
        $latestStat = PlayerSeasonStat::where('player_id', $id)
            ->where('season', 2024)
            ->first();

        if (!$latestStat || is_null($latestStat->detailed_stats)) {
            $this->apiService->getPlayerStats($id, 2024); // Sync for 2024 season
            // Refresh relationships
            $player->load(['team', 'seasonStats.league', 'seasonStats.team']);
        } else {
            $player->load(['team', 'seasonStats.league', 'seasonStats.team']);
        }

        // Season stats sorted newest first — this is the "career timeline"
        $seasonStats = $player->seasonStats()
            ->with(['league', 'team'])
            ->orderByDesc('id')
            ->get();

        // Match history — last 30 games
        $matchHistory = PlayerMatchStat::where('player_id', $id)
            ->with([
                'match.homeTeam',
                'match.awayTeam',
                'match.league',
                'team',
            ])
            ->join('football_matches', 'football_player_match_stats.match_id', '=', 'football_matches.id')
            ->orderBy('football_matches.match_at', 'desc')
            ->select('football_player_match_stats.*')
            ->limit(30)
            ->get();

        // Latest season stat for quick stats panel
        $latestStat = $seasonStats->first();

        // Sync additional career data if empty
        if (is_null($player->transfers)) {
            $this->apiService->getPlayerTransfers($id);
            $player->refresh();
        }
        if (is_null($player->trophies)) {
            $this->apiService->getPlayerTrophies($id);
            $player->refresh();
        }
        if (is_null($player->sidelined_history)) {
            $this->apiService->getPlayerSidelined($id);
            $player->refresh();
        }

        // Aggregate career totals across all seasons
        $careerTotals = [
            'total_games'   => $seasonStats->sum('games'),
            'total_starts'  => $seasonStats->sum('games_starts'),
            'total_minutes' => $seasonStats->sum('minutes'),
            'total_goals'   => $seasonStats->sum('goals'),
            'total_assists' => $seasonStats->sum('assists'),
            'total_yellows' => $seasonStats->sum('cards_yellow'),
            'total_reds'    => $seasonStats->sum('cards_red'),
        ];

        return Inertia::render('Players/Show', [
            'player'       => $player,
            'seasonStats'  => $seasonStats,
            'latestStat'   => $latestStat,
            'matchHistory' => $matchHistory,
            'careerTotals' => $careerTotals,
        ]);
    }
}
