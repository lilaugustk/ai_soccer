<?php

namespace App\Http\Controllers;

use App\Models\FootballPlayer;
use App\Models\FootballPlayerMatchStat;
use App\Models\FootballPlayerCareerStat;
use App\Models\FootballTeam;
use App\Models\FootballPlayerNationalStat;
use App\Models\FootballPlayerTransfer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Services\BsdSportsApiService;

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

    public function __construct(BsdSportsApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function show(Request $request, $id)
    {
        // Try to find the player, if not found, fetch from API
        $player = FootballPlayer::query()->find($id);

        if (!$player || empty($player->position) || empty($player->date_of_birth) || $player->careerStats()->count() === 0) {
            // Player doesn't exist or incomplete, fetch their profile and FULL career
            $this->apiService->syncPlayerCareer($id); 
            $player = FootballPlayer::findOrFail($id);
        }

        // Determine requested season
        $requestedSeason = $request->input('season');
        if (!$requestedSeason) {
             $latestSeasonStat = $player->careerStats()->orderByDesc('season_id')->first();
             // If still no stats after sync, fallback to a sensible default or the latest available
             $requestedSeason = $latestSeasonStat ? $latestSeasonStat->season_id : null;
        }

        // Sync career and other data if needed (if a specific season was requested, sync that too)
        if ($requestedSeason) {
            $this->apiService->syncPlayerCareer($id, $requestedSeason);
        }
        
        $this->apiService->syncPlayerMatches($id);
        $this->apiService->syncPlayerTransfers($id);
        $this->apiService->syncPlayerNationalTeam($id);

        // Fetch match history (last 30 games) with proper joins and ordering
        $matchHistory = FootballPlayerMatchStat::query()->where('player_id', $id)
            ->join('events', 'player_match_stats.event_id', '=', 'events.id')
            ->select('player_match_stats.*')
            ->orderByDesc('events.event_date')
            ->with([
                'match.homeTeam',
                'match.awayTeam',
                'match.league',
                'match.season',
                'team',
                'match.lineup.teams.players' => function($q) use ($id) {
                    $q->where('player_id', $id);
                }
            ])
            ->limit(30)
            ->get();

        $player->load(['team', 'careerStats.league', 'careerStats.team', 'careerStats.season']);

        // Season stats sorted newest first
        $seasonStats = $player->careerStats()
            ->with(['league', 'team', 'season'])
            ->orderByDesc('season_id')
            ->get();

        // Hydrate the last 8 matches if they lack ratings
        foreach ($matchHistory->take(8) as $stat) {
            if ($stat->rating <= 0) {
                $this->apiService->hydrateMatch($stat->event_id);
            }
        }



        // Latest season stat for quick stats panel
        $latestStat = $seasonStats->firstWhere('season_id', $requestedSeason) ?: $seasonStats->first();

        // National team data
        $nationalTeam = FootballPlayerNationalStat::query()->where('player_id', $id)
            ->with('nationalTeam')
            ->first();

        // Transfers
        $transfers = FootballPlayerTransfer::query()->where('player_id', $id)
            ->with(['fromTeam', 'toTeam'])
            ->orderByDesc('transfer_date')
            ->get();

        // Aggregate career totals across all seasons
        $careerTotals = [
            'total_games'   => $seasonStats->sum('matches'),
            'total_minutes' => $seasonStats->sum('minutes'),
            'total_goals'   => $seasonStats->sum('goals'),
            'total_assists' => $seasonStats->sum('assists'),
            'avg_rating'    => $seasonStats->avg('avg_rating'),
        ];

        // Format available seasons with names/years
        $availableSeasons = $seasonStats->map(function($s) {
            return [
                'id' => $s->season_id,
                'name' => $s->season ? ($s->season->year ? $s->season->year . '-' . ($s->season->year + 1) : $s->season->name) : $s->season_id
            ];
        })->unique('id')->sortByDesc('id')->values()->toArray();

        return Inertia::render('Players/Show', [
            'player'          => $player,
            'seasonStats'     => $seasonStats,
            'latestStat'      => $latestStat,
            'matchHistory'    => $matchHistory,
            'careerTotals'    => $careerTotals,
            'availableSeasons' => $availableSeasons,
            'currentSeason'   => (int)$requestedSeason,
            'nationalTeam'    => $nationalTeam,
            'transfers'       => $transfers,
        ]);
    }
}
