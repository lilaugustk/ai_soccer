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
            $liveStatuses = ['inprogress', 'penalties', '1st_half', 'ht', '2nd_half', 'et', 'postponed_rain', 'postponed_fog'];
            $matchesQuery->where(function($q) use ($liveStatuses) {
                $q->whereIn('status', $liveStatuses)
                  ->orWhere('status', 'like', '%half%')
                  ->orWhere('status', 'like', '%time%');
            });
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
            $hasPastPendingMatches = $matches->where('status', 'notstarted')
                ->where('event_date', '<', now()->subMinutes(5)) // Thêm buffer 5 phút
                ->count() > 0;
            $isPastDate = \Carbon\Carbon::parse($dateStr)->isPast();
            $isToday = \Carbon\Carbon::parse($dateStr)->isToday();

            if ($hasMissingScores) {
                $shouldSync = true; 
            } elseif ($hasPastPendingMatches) {
                $shouldSync = true; // Rule 1.5: Có trận lẽ ra đã đá nhưng chưa cập nhật
            } elseif ($isToday && !$lastSync) {
                $shouldSync = true; 
            } elseif ($isPastDate && $hasPendingMatches && !$lastSync) {
                $shouldSync = true; 
            }
        }

        // Gửi flag shouldSync xuống frontend để kích hoạt đồng bộ ngầm
        return Inertia::render('Welcome', [
            'groupedGames' => $this->mapMatches($matches),
            'filters' => [
                'date' => $dateStr,
                'league_id' => $leagueId,
                'status' => $statusFilter,
            ],
            'availableLeagues' => $this->getAvailableLeagues($matches),
            'shouldSync' => $shouldSync,
            'dateStr' => $dateStr
        ]);
    }

    public function syncMatches(Request $request, BsdSportsApiService $apiService)
    {
        $dateStr = $request->input('date', now()->toDateString());
        $cacheKey = "last_sync_v2_{$dateStr}";

        // Thực hiện đồng bộ
        $apiService->syncMatchesByDate($dateStr);
        
        $prevDay = \Carbon\Carbon::parse($dateStr)->subDay()->toDateString();
        $apiService->syncMatchesByDate($prevDay);

        $cooldown = \Carbon\Carbon::parse($dateStr)->isToday() ? 2 : 10;
        cache()->put($cacheKey, now()->toDateTimeString(), now()->addMinutes($cooldown));

        return response()->json([
            'success' => true,
            'message' => 'Dữ liệu đã được cập nhật ngầm thành công.'
        ]);
    }

    private function mapMatches($matches)
    {
        return $matches->map(function ($match) {
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
    }

    private function getAvailableLeagues($matches)
    {
        return $matches->map(fn($m) => [
            'id' => $m->league->id,
            'name' => $m->league->name,
            'logo_url' => $m->league->logo_url,
        ])->unique('id')->values();
    }

    private function mapStatus($status)
    {
        $status = strtolower($status);
        if ($status === 'finished' || $status === 'ft' || $status === 'full_time') {
            return 'finished';
        }
        
        $liveStatuses = ['inprogress', 'penalties', '1st_half', 'ht', '2nd_half', 'et', 'postponed_rain', 'postponed_fog'];
        if (in_array($status, $liveStatuses) || str_contains($status, 'half') || str_contains($status, 'time')) {
             // Ngoại trừ finished đã check ở trên
             return 'live';
        }

        return 'scheduled';
    }
}

