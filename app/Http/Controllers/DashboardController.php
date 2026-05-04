<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballLeague;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\FootballApiService;

class DashboardController extends Controller
{
    public function index(Request $request, FootballApiService $apiService)
    {
        $dateStr = $request->input('date', \Carbon\Carbon::today()->toDateString());
        $leagueId = $request->input('league_id');

        // 1. Tính toán dải thời gian (Start - End) theo GMT+7 để truy vấn DB (UTC)
        // Ví dụ: Ngày 21/04 VN bắt đầu từ 20/04 17:00 UTC
        $startOfDay = \Carbon\Carbon::parse($dateStr, 'Asia/Ho_Chi_Minh')->startOfDay()->setTimezone('UTC');
        $endOfDay = \Carbon\Carbon::parse($dateStr, 'Asia/Ho_Chi_Minh')->endOfDay()->setTimezone('UTC');

        $matchesQuery = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])
            ->whereBetween('match_at', [$startOfDay, $endOfDay]);

        if ($leagueId) {
            $matchesQuery->where('league_id', $leagueId);
        }

        $matches = $matchesQuery->get();

        // 2. Tự động đồng bộ nếu:
        // - Chưa có trận đấu nào trong DB cho ngày này
        // - HOẶC còn trận đấu ở trạng thái 'NS' (chưa đá) mặc dù đã qua ngày
        $hasPendingMatches = $matches->whereIn('status', ['NS', 'TBD'])->count() > 0;
        $isPastDate = \Carbon\Carbon::parse($dateStr)->isPast();

        if ($matches->isEmpty() || ($isPastDate && $hasPendingMatches)) {
            // Nạp dữ liệu ngày hiện tại
            $apiService->getFixturesByDate($dateStr); 
            
            // Nạp thêm dữ liệu ngày hôm trước (UTC) để xử lý các trận rạng sáng (GMT+7)
            $yesterdayUTC = \Carbon\Carbon::parse($dateStr)->subDay()->toDateString();
            $apiService->getFixturesByDate($yesterdayUTC);

            $matches = $matchesQuery->get();
        }

        // 3. Mapping dữ liệu cho Frontend (format chuẩn cũ)
        $groupedGames = $matches->map(function ($match) {
            return [
                'id' => $match->id,
                'match_datetime' => $match->match_at->toIso8601ZuluString(),
                'status' => $this->mapStatus($match->status),
                'home_team' => [
                    'name' => $match->homeTeam->name,
                    'logo_url' => $match->homeTeam->logo,
                ],
                'away_team' => [
                    'name' => $match->awayTeam->name,
                    'logo_url' => $match->awayTeam->logo,
                ],
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'league' => [
                    'name' => $match->league->name,
                    'logo_url' => $match->league->logo,
                ]
            ];
        })->groupBy(fn($game) => $game['league']['name']);

        // Danh sách các giải đấu có trận trong ngày (cho filter nhanh)
        $availableLeagues = $matches->map(fn($m) => [
            'id' => $m->league->id,
            'name' => $m->league->name,
            'logo_url' => $m->league->logo,
        ])->unique('id')->values();

        return Inertia::render('Welcome', [
            'groupedGames' => $groupedGames,
            'filters' => [
                'date' => $dateStr,
                'league_id' => $leagueId,
            ],
            'availableLeagues' => $availableLeagues,
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
}

