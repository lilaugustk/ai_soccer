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
        // 1. Chỉ Cache dữ liệu mảng (Dưới 1 giây sau lần đầu)
        $mappedGame = \Illuminate\Support\Facades\Cache::remember("match_data_v2_{$id}", 900, function () use ($id, $apiService) {
            $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);

            // Nếu thiếu đội hình hoặc thiếu dữ liệu cầu thủ (ratings) cho trận đã/đang đá
            $needsDetails = empty($match->lineups) || 
                           (empty($match->players) && in_array($match->status, ['FT', 'AET', 'PEN', '1H', '2H', 'HT', 'ET', 'P', 'LIVE']));

            if (!$match || $needsDetails) {
                $apiService->getFixtureDetails($id);
                $match = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])->find($id);
            }

            if (empty($match->predictions)) {
                $apiService->getPredictions($id);
                $match->refresh();
            }

            if (!$match) return null;

            return [
                'id' => $match->id,
                'match_datetime' => $match->match_at->setTimezone('UTC')->toIso8601String(),
                'status' => $this->mapStatus($match->status),
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'home_team' => [
                    'id' => $match->home_team_id,
                    'name' => $match->homeTeam->name,
                    'logo_url' => $match->homeTeam->logo,
                ],
                'away_team' => [
                    'id' => $match->away_team_id,
                    'name' => $match->awayTeam->name,
                    'logo_url' => $match->awayTeam->logo,
                ],
                'league' => [
                    'id' => $match->league->id,
                    'name' => $match->league->name,
                    'logo_url' => $match->league->logo,
                ],
                'referee' => $match->referee,
                'venue' => [
                    'name' => $match->venue_name,
                    'city' => $match->venue_city,
                ],
                'attendance' => $match->attendance,
                'statistics' => $match->statistics ?? [],
                'lineups' => $match->lineups ?? [],
                'events' => $match->events ?? [],
                'prediction' => $match->predictions,
                'players' => $match->players ?? [],
            ];
        });

        if (!$mappedGame) abort(404);

        // Lấy dữ liệu H2H (Có thể cache riêng hoặc gọi trực tiếp)
        $h2h = $apiService->getH2H($mappedGame['home_team']['id'], $mappedGame['away_team']['id']);

        return Inertia::render('Games/Show', [
            'game' => $mappedGame,
            'h2hMatches' => $h2h
        ]);
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
     