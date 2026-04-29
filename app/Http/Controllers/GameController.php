<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballLeague;
use App\Models\FootballTeam;
use App\Services\FootballApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class GameController extends Controller
{
    public function show($id, FootballApiService $apiService)
    {
        // 1. Dùng Cache để tối ưu hiệu năng
        $data = \Illuminate\Support\Facades\Cache::remember("match_data_v7_{$id}", 900, function () use ($id, $apiService) {
            $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);

            if (!$match) abort(404);

            // Nếu thiếu đội hình, gọi API để đồng bộ
            if (empty($match->lineups)) {
                $apiService->getFixtureDetails($id);
                $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);
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

            // 2. Dữ liệu H2H
            $h2h = $apiService->getH2H($mappedGame['home_team']['id'], $mappedGame['away_team']['id']);

            // 3. Standings (Dùng season của trận đấu, fallback về 2024)
            $season = $match->season ?: 2024;
            $leagueId = $mappedGame['league']['id'];

            $standings = \App\Models\FootballStanding::with('team')
                ->where('league_id', $leagueId)
                ->where('season', $season)
                ->orderBy('rank', 'asc')
                ->get()
                ->map(function($s) {
                    if ($s->team) {
                        $s->team->logo_url = $s->team->logo ?: 'https://via.placeholder.com/150?text=' . urlencode($s->team->name);
                    }
                    return $s;
                });

            return [
                'game' => $mappedGame,
                'h2hMatches' => $h2h,
                'standings' => $standings
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
     