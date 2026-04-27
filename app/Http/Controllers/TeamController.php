<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $query = Team::query();

        // Lọc theo giải đấu
        if ($request->has('league_id') && $request->league_id != 'all') {
            $lid = $request->league_id;
            $query->where(function($q) use ($lid) {
                $q->whereHas('homeGames', fn($g) => $g->where('league_id', $lid))
                  ->orWhereHas('awayGames', fn($g) => $g->where('league_id', $lid));
            });
        }

        // Lọc theo quốc gia
        if ($request->has('country') && $request->country != 'all') {
            $query->where('country', $request->country);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $teams = $query->paginate(24)->through(function($team) {
            $team->logo_url = $team->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($team->name);
            return $team;
        })->withQueryString();

        // Lấy danh sách giải đấu phổ biến
        $topLeagues = \App\Models\League::whereHas('games')
            ->withCount('games')
            ->orderBy('games_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function($l) {
                return [
                    'id' => $l->id,
                    'name' => $l->name,
                    'logo_url' => $l->logo
                ];
            });

        // Lấy danh sách quốc gia dạng Key-Value (Label đẹp, Value gốc để lọc)
        $countries = \App\Models\League::whereNotNull('country_name')
            ->where('country_name', '!=', '')
            ->distinct()
            ->pluck('country_name')
            ->map(function($c) {
                $label = $c;
                if ($c === 'International') {
                    $label = 'INTL';
                } else {
                    $parts = explode(' ', $c);
                    $label = strtoupper(end($parts));
                }
                return [
                    'label' => $label,
                    'value' => $c
                ];
            })
            ->sortBy('label')
            ->values();

        // Lấy danh sách giới tính (Tạm thời để trống vì schema mới không có)
        $genders = collect([]);

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
            'leagues' => $topLeagues,
            'countries' => $countries,
            'genders' => $genders,
            'filters' => $request->only(['search', 'league_id', 'country', 'gender'])
        ]);
    }

    public function show($id)
    {
        $team = Team::findOrFail($id);

        // Lấy 20 trận gần nhất (cả nhà và khách)
        $latestGames = Game::where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('match_at', 'desc')
        ->limit(20)
        ->get();

        // Lấy danh sách cầu thủ nổi bật (từ stats)
        $players = Player::whereHas('matchStats', function($query) use ($id) {
            $query->where('team_id', $id);
        })
        ->withCount(['matchStats' => function($query) use ($id) {
            $query->where('team_id', $id);
        }])
        ->orderBy('match_stats_count', 'desc')
        ->limit(15)
        ->get();

        return Inertia::render('Teams/Show', [
            'team' => $team,
            'latestGames' => $latestGames,
            'players' => $players
        ]);
    }
}
