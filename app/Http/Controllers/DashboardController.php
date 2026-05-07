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
        $statusFilter = strtoupper($request->input('status', 'ALL'));

        // 1. Tính toán dải thời gian (Start - End) theo GMT+7 để truy vấn DB (UTC)
        $startOfDay = \Carbon\Carbon::parse($dateStr, 'Asia/Ho_Chi_Minh')->startOfDay()->setTimezone('UTC');
        $endOfDay = \Carbon\Carbon::parse($dateStr, 'Asia/Ho_Chi_Minh')->endOfDay()->setTimezone('UTC');

        $matchesQuery = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])
            ->whereBetween('match_at', [$startOfDay, $endOfDay]);

        // Lọc theo trạng thái
        if ($statusFilter === 'LIVE') {
            $matchesQuery->whereIn('status', ['1H', 'HT', '2H', 'ET', 'P', 'LIVE']);
        } elseif ($statusFilter === 'FINISHED') {
            $matchesQuery->whereIn('status', ['FT', 'AET', 'PEN']);
        } elseif ($statusFilter === 'SCHEDULED') {
            $matchesQuery->where('status', 'NS');
        }

        if ($leagueId) {
            $matchesQuery->where('league_id', $leagueId);
        }

        $matches = $matchesQuery->get();

        // 2. Logic Automation Thông Minh (Quy tắc 1, 2, 3)
        $shouldSync = false;
        $cacheKey = "last_sync_v2_{$dateStr}";
        $lastSync = cache()->get($cacheKey);

        if ($matches->isEmpty()) {
            $shouldSync = true; // Rule 1: Chưa có tí dữ liệu nào
        } else {
            $hasMissingScores = $matches->whereIn('status', ['FT', 'AET', 'PEN', '1H', '2H', 'HT'])->whereNull('home_score')->count() > 0;
            $hasPendingMatches = $matches->whereIn('status', ['NS', 'TBD'])->count() > 0;
            $isPastDate = \Carbon\Carbon::parse($dateStr)->isPast();
            $isToday = \Carbon\Carbon::parse($dateStr)->isToday();

            if ($hasMissingScores) {
                $shouldSync = true; // Rule 3: Thiếu thông tin tỉ số dù trạng thái đã thay đổi
            } elseif ($isToday && !$lastSync) {
                $shouldSync = true; // Rule 2: Ngày hôm nay, cập nhật định kỳ (theo cooldown 2 phút)
            } elseif ($isPastDate && $hasPendingMatches && !$lastSync) {
                $shouldSync = true; // Rule 2: Trận cũ nhưng chưa có kết quả (cần so sánh/cập nhật, cooldown 10 phút)
            }
        }

        if ($shouldSync) {
            // Nạp dữ liệu ngày hiện tại
            $apiService->getFixturesByDate($dateStr); 
            
            // Nạp thêm dữ liệu ngày hôm trước (UTC) để xử lý các trận rạng sáng (GMT+7)
            $yesterdayUTC = \Carbon\Carbon::parse($dateStr)->subDay()->toDateString();
            $apiService->getFixturesByDate($yesterdayUTC);

            // Thiết lập cooldown để bảo vệ API Key Free
            $cooldown = \Carbon\Carbon::parse($dateStr)->isToday() ? 2 : 10;
            cache()->put($cacheKey, now()->toDateTimeString(), now()->addMinutes($cooldown));

            // Reload dữ liệu mới nhất từ DB
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
                    'country' => $match->league->country_name,
                    'country_code' => $match->league->country_code,
                ]
            ];
        })->groupBy(function($game) {
            $name = $game['league']['name'];
            $country = $game['league']['country'];
            return $country ? "{$name} ({$country})" : $name;
        });

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
                'status' => $statusFilter,
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

