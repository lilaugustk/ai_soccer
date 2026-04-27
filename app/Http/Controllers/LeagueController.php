<?php

namespace App\Http\Controllers;

use App\Models\FootballLeague;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\PlayerMatchStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeagueController extends Controller
{
    public function index()
    {
        // 1. Xác định danh sách ID các giải đấu hàng đầu (Featured)
        $topLeagueIds = [39, 140, 135, 78, 61, 2, 3, 1, 4]; // EPL, La Liga, Serie A, Bundesliga, Ligue 1, UCL, UEL, World Cup, Euro

        // 2. Lấy số lượng trận đấu hôm nay cho mỗi giải đấu
        $todayMatchCounts = FootballMatch::whereDate('match_at', \Carbon\Carbon::today())
            ->select('league_id', DB::raw('count(*) as count'))
            ->groupBy('league_id')
            ->pluck('count', 'league_id')
            ->toArray();

        // 3. Lấy tất cả các giải đấu, ưu tiên những giải có trong danh sách Top hoặc có dữ liệu
        $leagues = FootballLeague::all()->map(function($league) use ($todayMatchCounts, $topLeagueIds) {
            $league->today_matches_count = $todayMatchCounts[$league->id] ?? 0;
            $league->is_featured = in_array($league->id, $topLeagueIds);
            
            // Xử lý logo URL nếu cần (đảm bảo luôn có URL)
            $league->logo_url = $league->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($league->name);
            
            return $league;
        });

        // 4. Nhóm theo quốc gia (trừ các giải Featured)
        $featuredLeagues = $leagues->where('is_featured', true)->values();
        
        $groupedByCountry = $leagues->filter(fn($l) => !$l->is_featured)
            ->groupBy(function($item) {
                return $item->country_name ?: 'Quốc tế';
            })
            ->map(function($items, $country) {
                return [
                    'country' => $country,
                    'leagues' => $items->sortBy('name')->values()
                ];
            })
            ->values()
            ->sortBy('country');

        return Inertia::render('Leagues/Index', [
            'featuredLeagues' => $featuredLeagues,
            'groupedLeagues' => $groupedByCountry->values(),
            'allLeagues' => $leagues // Để phục vụ Search client-side
        ]);
    }

    public function show(Request $request, $id)
    {
        $league = \App\Models\FootballLeague::findOrFail($id);
        $season = $request->input('season', 2024); 

        // 1. Bảng xếp hạng với logo_url
        $standings = \App\Models\FootballStanding::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->orderBy('rank', 'asc')
            ->get()
            ->map(function($s) {
                if ($s->team) {
                    $s->team->logo_url = $s->team->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($s->team->name);
                }
                return $s;
            });

        // 2. Vua phá lưới
        $topScorers = \App\Models\FootballScorer::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->orderBy('goals', 'desc')
            ->limit(10)
            ->get();

        // 3. Trận đấu với logo_url và sắp xếp - LỌC THEO MÙA GIẢI
        $matches = \App\Models\FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('league_id', $id)
            ->where('season', $season)
            ->orderBy('match_at', 'desc')
            ->get()
            ->map(function($m) {
                if ($m->homeTeam) {
                    $m->homeTeam->logo_url = $m->homeTeam->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($m->homeTeam->name);
                }
                if ($m->awayTeam) {
                    $m->awayTeam->logo_url = $m->awayTeam->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($m->awayTeam->name);
                }
                return $m;
            });

        return Inertia::render('Leagues/Show', [
            'league' => $league,
            'standings' => $standings,
            'topScorers' => $topScorers,
            'matches' => $matches,
            'season' => $season
        ]);
    }
}
