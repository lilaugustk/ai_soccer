<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballStanding;
use App\Services\StatisticalModelService;
use App\Services\FootballApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;


class GameController extends Controller
{
    public function show($id, FootballApiService $apiService, StatisticalModelService $statService)
    {
        // 1. Tự động đồng bộ (Automation) - Không chặn hiển thị
        $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);
        if (!$match) abort(404);

        $status = $this->mapStatus($match->status);
        $syncCacheKey = "auto_sync_lock_{$id}";
        
        // Kiểm tra xem trận đấu có cần được "bù đắp" dữ liệu không
        $isMissingData = empty($match->lineups) || empty($match->statistics);
        $isLive = $status === 'live';
        $isUpcoming = $status === 'scheduled' && $match->match_at && $match->match_at->diffInMinutes(now(), false) > -60;

        // Nếu thiếu dữ liệu hoặc đang đá, tự động kích hoạt đồng bộ từ API
        if (!cache()->has($syncCacheKey) && ($isMissingData || $isLive || $isUpcoming)) {
            // Gọi API đồng bộ âm thầm
            $apiService->getFixtureDetails($id);
            $apiService->getPredictions($id);
            
            // Thiết lập cooldown để bảo vệ API key: Live thì 2 phút, đã xong thì 30 phút
            $cooldown = $isLive ? 2 : 30;
            cache()->put($syncCacheKey, true, now()->addMinutes($cooldown));
            
            // Làm mới dữ liệu sau khi đồng bộ
            $match->refresh();
            
            // Xóa cache hiển thị để đảm bảo dữ liệu mới được nạp vào ở bước sau
            Cache::forget("match_display_v21_{$id}");
        }

        // 2. Trả dữ liệu về View (Sử dụng cache hiển thị ngắn hạn)
        $displayCacheKey = "match_display_v21_{$id}";
        $data = Cache::remember($displayCacheKey, 60, function () use ($match, $apiService, $statService) {
            // Lấy lại match từ closure để đảm bảo dữ liệu mới nhất
            $id = $match->id;
            $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);

            // Map data an toàn
            $mappedGame = [
                'id' => $match->id,
                'match_datetime' => optional($match->match_at)->toIso8601String(),
                'status' => $this->mapStatus($match->status),
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'round' => $match->round,
                'season' => $match->season,
                'home_team' => [
                    'id' => $match->home_team_id,
                    'name' => optional($match->homeTeam)->name ?? "N/A",
                    'logo_url' => optional($match->homeTeam)->logo,
                ],
                'away_team' => [
                    'id' => $match->away_team_id,
                    'name' => optional($match->awayTeam)->name ?? "N/A",
                    'logo_url' => optional($match->awayTeam)->logo,
                ],
                'league' => [
                    'id' => $match->league_id,
                    'name' => optional($match->league)->name ?? "N/A",
                    'logo_url' => optional($match->league)->logo,
                ],
                'referee' => $match->referee,
                'venue' => [
                    'name' => $match->venue_name,
                    'city' => $match->venue_city,
                ],
                'attendance' => $match->attendance,
                'season' => $match->season,
                'statistics' => $match->statistics ?? [],
                'stats_1h' => $match->stats_1h ?? [],
                'stats_2h' => $match->stats_2h ?? [],
                'lineups' => $match->lineups ?? [],
                'events' => $match->events ?? [],
                'players' => $match->players ?? [],
                'prediction' => $match->predictions ?? null,
                'injuries' => $match->injuries ?? [],
            ];

            // Nếu trận đấu đã kết thúc/đang diễn ra nhưng thiếu stats hiệp, gọi API bổ sung
            if (empty($mappedGame['stats_1h']) && in_array($match->status, ['1H', 'HT', '2H', 'FT', 'AET'])) {
                $apiService->getFixtureDetails($id);
                $match->refresh();
                $mappedGame['stats_1h'] = $match->stats_1h ?? [];
                $mappedGame['stats_2h'] = $match->stats_2h ?? [];
            }

            // 2. Dữ liệu H2H (Ép kiểu ID về int để đảm bảo chính xác)
            $homeId = (int)$mappedGame['home_team']['id'];
            $awayId = (int)$mappedGame['away_team']['id'];
            // 2. Head to Head (Sync from API then get from DB for consistency)
            $apiService->getH2H($homeId, $awayId);
            
            $h2h = FootballMatch::with(['homeTeam', 'awayTeam'])
                ->where(function($q) use ($homeId, $awayId) {
                    $q->where('home_team_id', $homeId)->where('away_team_id', $awayId);
                })
                ->orWhere(function($q) use ($homeId, $awayId) {
                    $q->where('home_team_id', $awayId)->where('away_team_id', $homeId);
                })
                ->orderBy('match_at', 'desc')
                ->get()
                ->map(function($m) {
                    return [
                        'fixture' => [
                            'id' => $m->id,
                            'date' => $m->match_at
                        ],
                        'league' => [
                            'name' => $m->league?->name,
                            'logo' => $m->league?->logo,
                        ],
                        'teams' => [
                            'home' => [
                                'id' => $m->home_team_id,
                                'name' => $m->homeTeam?->name,
                                'logo' => $m->homeTeam?->logo,
                            ],
                            'away' => [
                                'id' => $m->away_team_id,
                                'name' => $m->awayTeam?->name,
                                'logo' => $m->awayTeam?->logo,
                            ],
                        ],
                        'goals' => [
                            'home' => $m->home_score,
                            'away' => $m->away_score,
                        ]
                    ];
                });
            
            Log::info("H2H Matches for {$homeId} vs {$awayId}: " . $h2h->count());

            // 3. Standings (Dùng season của trận đấu)
            $season = $match->season;
            $leagueId = (int)$mappedGame['league']['id'];

            $standingsQuery = FootballStanding::with('team')
                ->where('league_id', $leagueId)
                ->where('season', $season)
                ->orderBy('rank', 'asc');

            $standings = $standingsQuery->get();

            // Nếu bảng xếp hạng trống, gọi API lấy về ngay
            if ($standings->isEmpty()) {
                $apiService->getStandings($leagueId, $season);
                $standings = $standingsQuery->get();
                
                // Fallback cho Free Plan: Nếu 2025 không có quyền truy cập, thử lấy 2024
                if ($standings->isEmpty() && $season == 2025) {
                    $apiService->getStandings($leagueId, 2024);
                    $standings = FootballStanding::with('team')
                        ->where('league_id', $leagueId)
                        ->where('season', 2024)
                        ->orderBy('rank', 'asc')
                        ->get();
                }
            }

            // 3.1 Lấy 5 trận gần nhất của giải đấu này cho mỗi đội để hiển thị chi tiết trong tooltip
            $teamIds = $standings->pluck('team_id');
            $allMatches = FootballMatch::query()->where('league_id', $leagueId)
                ->where('season', $season)
                ->where(function($q) use ($teamIds) {
                    $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
                })
                ->whereIn('status', ['FT', 'AET', 'PEN'])
                ->orderBy('match_at', 'desc')
                ->with(['homeTeam', 'awayTeam'])
                ->get();

            $matchesByTeam = [];
            foreach ($allMatches as $m) {
                foreach ([$m->home_team_id, $m->away_team_id] as $tId) {
                    if ($teamIds->contains($tId)) {
                        if (!isset($matchesByTeam[$tId])) $matchesByTeam[$tId] = [];
                        if (count($matchesByTeam[$tId]) < 5) {
                            $res = 'D';
                            if ($m->home_score > $m->away_score) {
                                $res = ($tId == $m->home_team_id) ? 'W' : 'L';
                            } elseif ($m->home_score < $m->away_score) {
                                $res = ($tId == $m->away_team_id) ? 'W' : 'L';
                            }

                            $matchesByTeam[$tId][] = [
                                'date' => Carbon::parse($m->match_at)->format('d/m'),
                                'home' => $m->homeTeam?->name,
                                'away' => $m->awayTeam?->name,
                                'score' => "{$m->home_score} - {$m->away_score}",
                                'res' => $res
                            ];
                        }
                    }
                }
            }

            $standings = $standings->map(function($s) use ($matchesByTeam) {
                    if ($s->team) {
                        $s->team->logo_url = $s->team->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($s->team->name);
                    }
                    $s->recent_matches = array_reverse($matchesByTeam[$s->team_id] ?? []);
                    return $s;
                });

            // 4. Custom Poisson Prediction & Value Bets
            $poissonData = null;
            $valueBets = [];
            $homeStanding = $standings->where('team_id', $match->home_team_id)->first();
            $awayStanding = $standings->where('team_id', $match->away_team_id)->first();

            if ($homeStanding && $awayStanding && $homeStanding->played > 0 && $awayStanding->played > 0) {
                $avgHomeGoals = $standings->avg('goals_for') / ($standings->avg('played') ?: 1);
                $homeAttack = ($homeStanding->goals_for / $homeStanding->played) / ($avgHomeGoals ?: 1);
                $homeDefense = ($homeStanding->goals_against / $homeStanding->played) / ($avgHomeGoals ?: 1);
                $awayAttack = ($awayStanding->goals_for / $awayStanding->played) / ($avgHomeGoals ?: 1);
                $awayDefense = ($awayStanding->goals_against / $awayStanding->played) / ($avgHomeGoals ?: 1);
                
                $homeLambda = $homeAttack * $awayDefense * $avgHomeGoals;
                $awayLambda = $awayAttack * $homeDefense * $avgHomeGoals;
                
                $poissonData = $statService->calculateProbabilities($homeLambda, $awayLambda);

                // Fetch Odds for Value Betting
                $odds = $apiService->getOdds($id);
                if (!empty($odds)) {
                    $valueBets = $statService->calculateValueBets($poissonData, $odds);
                }
            };

            return [
                'game' => $mappedGame,
                'h2hMatches' => $h2h->values()->all(),
                'standings' => $standings->values()->all(),
                'poisson' => $poissonData,
                'valueBets' => $valueBets,
            ];
        });

        return Inertia::render('Games/Show', $data);
    }

    private function mapStatus($status)
    {
        return match ($status) {
            'FT', 'AET', 'PEN' => 'finished',
            '1H', '2H', 'HT', 'ET', 'P', 'LIVE' => 'live',
            default => 'scheduled',
        };
    }

    public function predictions()
    {
        return Inertia::render('Predictions/Index');
    }

    public function sync($id, FootballApiService $apiService)
    {
        // 1. Xóa cache
        Cache::forget("match_display_v21_{$id}");

        // 2. Ép buộc call API lấy chi tiết
        $apiService->getFixtureDetails($id);
        $apiService->getPredictions($id);

        return back()->with('success', 'Dữ liệu đã được cập nhật!');
    }
}
     