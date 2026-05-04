<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballLeague;
use App\Models\FootballTeam;
use App\Services\StatisticalModelService;
use App\Services\AiAnalysisService;
use App\Services\MomentumService;
use App\Services\FootballApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class GameController extends Controller
{
    public function show($id, FootballApiService $apiService, StatisticalModelService $statService, AiAnalysisService $aiService, MomentumService $momentumService)
    {
        // 1. Dùng Cache (15 phút)
        $data = \Illuminate\Support\Facades\Cache::remember("match_data_v17_{$id}", 900, function () use ($id, $apiService, $statService, $aiService, $momentumService) {
            $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);

            if (!$match) abort(404);

            $status = $this->mapStatus($match->status);
            $needsUpdate = false;

            if ($status === 'finished') {
                // Đã xong: Chỉ call nếu thiếu 1 trong 3 dữ liệu cốt lõi
                if (empty($match->lineups) || empty($match->events) || empty($match->statistics)) {
                    $needsUpdate = true;
                }
            } elseif ($status === 'live') {
                // Đang đá: Luôn ưu tiên lấy dữ liệu mới nhất (polling sẽ lo phần này, nhưng load trang đầu cũng nên có)
                $needsUpdate = true; 
            } elseif ($status === 'scheduled') {
                // Chưa đá: Chỉ call nếu thiếu đội hình VÀ trận đấu sắp bắt đầu (trong vòng 1 tiếng)
                $isStartingSoon = $match->match_at && $match->match_at->diffInMinutes(now(), false) > -60;
                if (empty($match->lineups) && $isStartingSoon) {
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                $apiService->getFixtureDetails($id);
                $match->refresh();
            }

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
            
            $h2h = \App\Models\FootballMatch::with(['homeTeam', 'awayTeam'])
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
            
            \Illuminate\Support\Facades\Log::info("H2H Matches for {$homeId} vs {$awayId}: " . $h2h->count());

            // 3. Standings (Dùng season của trận đấu, fallback về 2024)
            $season = $match->season ?: 2024;
            $leagueId = (int)$mappedGame['league']['id'];

            $standingsQuery = \App\Models\FootballStanding::with('team')
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
                    $standings = \App\Models\FootballStanding::with('team')
                        ->where('league_id', $leagueId)
                        ->where('season', 2024)
                        ->orderBy('rank', 'asc')
                        ->get();
                }
            }

            $standings = $standings->map(function($s) {
                    if ($s->team) {
                        $s->team->logo_url = $s->team->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($s->team->name);
                    }
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
            }

            // 5. Momentum Engine
            $momentum = null;
            if (in_array($match->status, ['1H', '2H', 'HT', 'FT'])) {
                $elapsed = $match->statistics[0]['time']['elapsed'] ?? 90;
                $momentum = $momentumService->getMatchMomentum($match->events ?? [], $match->home_team_id, $match->away_team_id, $elapsed);
            }

            // 6. Groq AI Tactical Insights
            $aiInsights = null;
            if ($match->status === 'NS') {
                $aiInsights = $aiService->getTacticalInsights([
                    'home_team' => $mappedGame['home_team'],
                    'away_team' => $mappedGame['away_team'],
                    'league' => $mappedGame['league'],
                    'statistics' => $mappedGame['statistics'],
                    'h2h' => $h2h
                ]);
            }

            return [
                'game' => $mappedGame,
                'h2hMatches' => $h2h,
                'standings' => $standings,
                'poisson' => $poissonData,
                'valueBets' => $valueBets,
                'momentum' => $momentum,
                'aiInsights' => $aiInsights,
            ];
        });

        return \Inertia\Inertia::render('Games/Show', $data);
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
}
     