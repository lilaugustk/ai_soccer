<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballLeague;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\BsdSportsApiService;

class DashboardController extends Controller
{
    public function index(Request $request, BsdSportsApiService $apiService)
    {
        $dateStr = $request->input('date', \Carbon\Carbon::today()->toDateString());
        $leagueId = $request->input('league_id');
        $statusFilter = strtoupper($request->input('status', 'ALL'));

        // 1. Tính toán dải thời gian (Start - End) theo GMT+7 để truy vấn DB (UTC)
        $startOfDay = \Carbon\Carbon::parse($dateStr, 'Asia/Ho_Chi_Minh')->startOfDay()->setTimezone('UTC');
        $endOfDay = \Carbon\Carbon::parse($dateStr, 'Asia/Ho_Chi_Minh')->endOfDay()->setTimezone('UTC');

        $matchesQuery = FootballMatch::with(['league', 'homeTeam', 'awayTeam'])
            ->whereBetween('event_date', [$startOfDay, $endOfDay]);

        // Lọc theo trạng thái
        if ($statusFilter === 'LIVE') {
            $matchesQuery->whereIn('status', ['inprogress', 'penalties']);
        } elseif ($statusFilter === 'FINISHED') {
            $matchesQuery->where('status', 'finished');
        } elseif ($statusFilter === 'SCHEDULED') {
            $matchesQuery->where('status', 'notstarted');
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
            $hasMissingScores = $matches->whereIn('status', ['finished', 'inprogress', 'penalties'])->whereNull('home_score')->count() > 0;
            $hasPendingMatches = $matches->where('status', 'notstarted')->count() > 0;
            $isPastDate = \Carbon\Carbon::parse($dateStr)->isPast();
            $isToday = \Carbon\Carbon::parse($dateStr)->isToday();

            if ($hasMissingScores) {
                $shouldSync = true; 
            } elseif ($isToday && !$lastSync) {
                $shouldSync = true; 
            } elseif ($isPastDate && $hasPendingMatches && !$lastSync) {
                $shouldSync = true; 
            }
        }

        if ($shouldSync) {
            // Nạp dữ liệu ngày hiện tại
            $apiService->syncMatchesByDate($dateStr); 
            
            // BSD v2 lấy theo UTC nên có thể cần lấy thêm ngày hôm sau/trước tùy múi giờ
            // $apiService->syncMatchesByDate($nextDay);

            $cooldown = \Carbon\Carbon::parse($dateStr)->isToday() ? 2 : 10;
            cache()->put($cacheKey, now()->toDateTimeString(), now()->addMinutes($cooldown));

            // Reload dữ liệu mới nhất từ DB
            $matches = $matchesQuery->get();
        }

        // 3. Mapping dữ liệu cho Frontend (format chuẩn cũ)
        $groupedGames = $matches->map(function ($match) {
            return [
                'id' => $match->id,
                'match_datetime' => $match->event_date->toIso8601ZuluString(),
                'status' => $this->mapStatus($match->status),
                'home_team' => [
                    'name' => $match->homeTeam->name,
                    'logo_url' => $match->homeTeam->logo_url,
                ],
                'away_team' => [
                    'name' => $match->awayTeam->name,
                    'logo_url' => $match->awayTeam->logo_url,
                ],
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'league' => [
                    'name' => $match->league->name,
                    'logo_url' => $match->league->logo_url,
                    'country' => $match->league->country,
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
            'logo_url' => $m->league->logo_url,
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
            'finished' => 'finished',
            'inprogress', 'penalties' => 'live',
            default => 'scheduled',
        };
    }
}

