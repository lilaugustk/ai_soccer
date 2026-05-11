<?php

namespace App\Http\Controllers;

use App\Models\FootballLeague;
use App\Models\FootballMatch;
use App\Models\FootballTeam;
use App\Models\FootballStanding;
use App\Models\FootballScorer;
use App\Models\PlayerMatchStat;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

use App\Services\FootballApiService;

class LeagueController extends Controller
{
    protected $apiService;

    public function __construct(FootballApiService $apiService)
    {
        $this->apiService = $apiService;
    }


    public function show(Request $request, $id)
    {
        $league = FootballLeague::query()->find($id);
        if (!$league) {
            // Nếu không có trong DB, thử lấy từ API
            $leagues = $this->apiService->getLeagues();
            $apiLeague = collect($leagues)->firstWhere('league.id', (int)$id);
            
            if ($apiLeague) {
                $league = FootballLeague::query()->create([
                    'id' => $apiLeague['league']['id'],
                    'name' => $apiLeague['league']['name'],
                    'type' => $apiLeague['league']['type'] ?? null,
                    'logo' => $apiLeague['league']['logo'] ?? null,
                    'country_name' => $apiLeague['country']['name'] ?? null,
                    'country_code' => $apiLeague['country']['flag'] ?? null
                ]);
            } else {
                return redirect()->route('dashboard')->with('error', 'Không tìm thấy giải đấu này.');
            }
        }
        $season = $request->input('season', 2024); 

        $standings = FootballStanding::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->orderBy('rank', 'asc')
            ->get();

        // 1.0 Tự động đồng bộ Bảng xếp hạng nếu trống
        if ($standings->isEmpty()) {
            $this->apiService->getStandings($id, $season);
            $standings = FootballStanding::with('team')
                ->where('league_id', $id)
                ->where('season', $season)
                ->orderBy('rank', 'asc')
                ->get();
        }

        // 1.1 Lấy 5 trận gần nhất của giải đấu này cho mỗi đội để hiển thị chi tiết trong tooltip
        $teamIds = $standings->pluck('team_id');
        $allMatches = FootballMatch::query()->where('league_id', $id)
            ->where('season', $season)
            ->where(function($q) use ($teamIds) {
                $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
            })
            ->where('status', 'FT')
            ->orderBy('match_at', 'desc')
            ->with(['homeTeam', 'awayTeam'])
            ->get();

        $matchesByTeam = [];
        foreach ($allMatches as $m) {
            foreach ([$m->home_team_id, $m->away_team_id] as $tId) {
                if ($teamIds->contains($tId)) {
                    if (!isset($matchesByTeam[$tId])) $matchesByTeam[$tId] = [];
                    if (count($matchesByTeam[$tId]) < 5) {
                        // Tính toán kết quả cho đội này (W/L/D)
                        $res = 'D';
                        if ($m->home_score > $m->away_score) {
                            $res = ($tId == $m->home_team_id) ? 'W' : 'L';
                        } elseif ($m->home_score < $m->away_score) {
                            $res = ($tId == $m->away_team_id) ? 'W' : 'L';
                        }

                        $matchesByTeam[$tId][] = [
                            'date' => Carbon::parse($m->match_at)->format('d/m'),
                            'home' => $m->homeTeam->name,
                            'away' => $m->awayTeam->name,
                            'score' => "{$m->home_score} - {$m->away_score}",
                            'res' => $res
                        ];
                    }
                }
            }
        }

        $standings->map(function($s) use ($matchesByTeam) {
            if ($s->team) {
                $s->team->logo_url = $s->team->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($s->team->name);
            }
            // Gán 5 trận gần nhất, đảo ngược để khớp với thứ tự form từ cũ đến mới (trái sang phải)
            $s->recent_matches = array_reverse($matchesByTeam[$s->team_id] ?? []);
            return $s;
        });

        // 2. Vua phá lưới và các top khác
        $topScorers = FootballScorer::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->where('goals', '>', 0)
            ->orderBy('goals', 'desc')
            ->limit(10)
            ->get();

        if ($topScorers->isEmpty()) {
            $this->apiService->getTopScorers($id, $season);
            $topScorers = FootballScorer::with('team')
                ->where('league_id', $id)
                ->where('season', $season)
                ->where('goals', '>', 0)
                ->orderBy('goals', 'desc')
                ->limit(10)
                ->get();
        }

        $topAssists = FootballScorer::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->where('assists', '>', 0)
            ->orderBy('assists', 'desc')
            ->limit(10)
            ->get();

        if ($topAssists->isEmpty()) {
            $this->apiService->getTopAssists($id, $season);
            $topAssists = FootballScorer::with('team')
                ->where('league_id', $id)
                ->where('season', $season)
                ->where('assists', '>', 0)
                ->orderBy('assists', 'desc')
                ->limit(10)
                ->get();
        }

        $topYellowCards = FootballScorer::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->where('yellow_cards', '>', 0)
            ->orderBy('yellow_cards', 'desc')
            ->limit(10)
            ->get();

        if ($topYellowCards->isEmpty()) {
            $this->apiService->getTopYellowCards($id, $season);
            $topYellowCards = FootballScorer::with('team')
                ->where('league_id', $id)
                ->where('season', $season)
                ->where('yellow_cards', '>', 0)
                ->orderBy('yellow_cards', 'desc')
                ->limit(10)
                ->get();
        }

        $topRedCards = FootballScorer::with('team')
            ->where('league_id', $id)
            ->where('season', $season)
            ->where('red_cards', '>', 0)
            ->orderBy('red_cards', 'desc')
            ->limit(10)
            ->get();

        if ($topRedCards->isEmpty()) {
            $this->apiService->getTopRedCards($id, $season);
            $topRedCards = FootballScorer::with('team')
                ->where('league_id', $id)
                ->where('season', $season)
                ->where('red_cards', '>', 0)
                ->orderBy('red_cards', 'desc')
                ->limit(10)
                ->get();
        }

        // 3. Trận đấu với logo_url và sắp xếp - LỌC THEO MÙA GIẢI
        $matchesQuery = FootballMatch::with(['homeTeam', 'awayTeam'])
            ->where('league_id', $id)
            ->where('season', $season)
            ->orderBy('match_at', 'desc');

        $matches = $matchesQuery->get();

        // 3.1 Tự động đồng bộ Trận đấu nếu trống
        if ($matches->isEmpty()) {
            $this->apiService->getFixturesByLeagueSeason($id, $season);
            $matches = FootballMatch::with(['homeTeam', 'awayTeam'])
                ->where('league_id', $id)
                ->where('season', $season)
                ->orderBy('match_at', 'desc')
                ->get();
        }

        $matches = $matches->map(function($m) {
                if ($m->homeTeam) {
                    $m->homeTeam->logo_url = $m->homeTeam->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($m->homeTeam->name);
                }
                if ($m->awayTeam) {
                    $m->awayTeam->logo_url = $m->awayTeam->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($m->awayTeam->name);
                }
                return $m;
            });

        // 4. Lấy danh sách mùa giải có sẵn (Cache 24h)
        $availableSeasons = Cache::remember("league_seasons_{$id}", 86400, function() use ($id) {
            $seasons = $this->apiService->getLeagueSeasons($id);
            // Fallback nếu API lỗi
            return !empty($seasons) ? $seasons : [2025, 2024, 2023, 2022];
        });

        return Inertia::render('Leagues/Show', [
            'league' => $league,
            'standings' => $standings,
            'topScorers' => $topScorers,
            'topAssists' => $topAssists,
            'topYellowCards' => $topYellowCards,
            'topRedCards' => $topRedCards,
            'matches' => $matches,
            'season' => $season,
            'availableSeasons' => $availableSeasons
        ]);
    }
}