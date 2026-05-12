<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballStanding;
use App\Models\FootballPlayer;
use App\Models\FootballPlayerCareerStat;
use App\Models\FootballTeam;
use App\Models\FootballSeason;
use App\Models\FootballManagerCareer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\BsdSportsApiService;
use App\Services\FootballApiService;

class TeamController extends Controller
{
    public function show(Request $request, $id)
    {
        $team = FootballTeam::findOrFail($id);
        $year = $request->input('season', 2024);
        $now = Carbon::now();

        // Tìm season_id thực tế từ year
        $seasonRecord = FootballSeason::query()
            ->where('year', $year)
            ->whereHas('standings', function($q) use ($id) {
                $q->where('team_id', $id);
            })
            ->first();

        if (!$seasonRecord) {
            $seasonRecord = FootballSeason::query()->where('year', $year)->first();
        }

        $seasonId = $seasonRecord ? $seasonRecord->id : $year;

        $recentGames = FootballMatch::query()->where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->where('event_date', '<', $now)
        ->where('season_id', $seasonId)
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('event_date', 'desc')
        ->limit(10)
        ->get();

        // 1.2 Nếu chưa có trận nào (hoặc quá ít), đồng bộ từ API
        if ($recentGames->count() < 2) {
            // Tạm thời dùng FootballApiService nếu BsdSportsApiService chưa hỗ trợ getFixturesByTeam
            $apiService = new FootballApiService();
            $apiService->getFixturesByTeam($id, $year);
            
            // Lấy lại sau khi đồng bộ
            $recentGames = FootballMatch::query()->where(function($query) use ($id) {
                $query->where('home_team_id', $id)
                      ->orWhere('away_team_id', $id);
            })
            ->where('event_date', '<', $now)
            ->where('season_id', $seasonId)
            ->with(['homeTeam', 'awayTeam', 'league'])
            ->orderBy('event_date', 'desc')
            ->limit(10)
            ->get();
        }

        // 1.1 Lấy đội hình trận gần nhất có dữ liệu
        $lastMatchWithLineup = FootballMatch::query()->where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->where('status', 'finished')
        ->has('lineup')
        ->with(['lineup.teams.players.player'])
        ->orderBy('event_date', 'desc')
        ->first();

        $lineup = $lastMatchWithLineup?->lineup;
        $teamLineup = null;
        if ($lineup) {
            $teamLineup = $lineup->teams->where('team_id', $id)->first();
        }

        // 2. Lấy trận đấu sắp tới
        $upcomingGames = FootballMatch::query()->where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->where('event_date', '>=', $now)
        ->where('season_id', $seasonId)
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('event_date', 'asc')
        ->limit(5)
        ->get();

        // 3. Lấy danh sách cầu thủ (Squad)
        $players = FootballPlayer::query()->where('current_team_id', $id)
            ->with('latestSeasonStat')
            ->get();
        
        $hasStats = FootballPlayerCareerStat::query()->where('team_id', $id)->where('season_id', $seasonId)->exists();

        if ($players->count() < 10 || !$hasStats) {
            $bsdApiService = app(BsdSportsApiService::class);
            $bsdApiService->syncPlayersByTeam($id, $seasonId);
            
            // Lấy lại danh sách sau khi đồng bộ
            $players = FootballPlayer::query()->where('current_team_id', $id)
                ->with('latestSeasonStat')
                ->get();
        }
        
        $squad = $players->map(function($p) {
            $pos = strtoupper($p->position);
            if (in_array($pos, ['ATTACKER', 'FORWARD', 'TIỀN ĐẠO', 'F'])) $p->position = 'Attacker';
            elseif (in_array($pos, ['MIDFIELDER', 'TIỀN VỆ', 'M'])) $p->position = 'Midfielder';
            elseif (in_array($pos, ['DEFENDER', 'HẬU VỆ', 'D'])) $p->position = 'Defender';
            elseif (in_array($pos, ['GOALKEEPER', 'THỦ MÔN', 'G'])) $p->position = 'Goalkeeper';
            return $p;
        })->values()->toArray();

        // 4. Lấy BXH giải đấu hiện tại
        $standings = [];
        $leagueId = optional($recentGames->first())->league_id ?? optional($upcomingGames->first())->league_id;
        
        if (!$leagueId) {
            $leagueId = FootballStanding::query()->where('team_id', $id)->orderBy('position', 'asc')->value('league_id');
        }

        if ($leagueId) {
            $standings = FootballStanding::query()->where('league_id', $leagueId)
                ->where('season_id', $seasonId)
                ->with('team')
                ->orderBy('position', 'asc')
                ->get();
            
            if ($standings->isEmpty()) {
                $bsdApiService = app(BsdSportsApiService::class);
                $bsdApiService->syncStandings($leagueId, $seasonId);

                $standings = FootballStanding::query()->where('league_id', $leagueId)
                    ->where('season_id', $seasonId)
                    ->with('team')
                    ->orderBy('position', 'asc')
                    ->get();
            }
        }

        // 5. Thống kê cầu thủ mùa giải
        $seasonStats = FootballPlayerCareerStat::query()->where('team_id', $id)
            ->where('season_id', $seasonId)
            ->with('player')
            ->get();

        $groupedStats = $seasonStats->groupBy('player_id')->map(function($group) {
            $first = $group->first();
            return [
                'player_id' => $first->player_id,
                'player' => $first->player,
                'goals' => $group->sum('goals'),
                'assists' => $group->sum('assists'),
                'rating' => $group->avg('avg_rating'),
                'detailed_stats' => []
            ];
        })->values();

        $stats = [
            'topScorers' => $groupedStats->sortByDesc('goals')->filter(fn($s) => $s['goals'] > 0)->take(5)->values(),
            'topAssists' => $groupedStats->sortByDesc('assists')->filter(fn($s) => $s['assists'] > 0)->take(5)->values(),
            'topRated' => $groupedStats->sortByDesc('rating')->filter(fn($s) => $s['rating'] > 0)->take(5)->values(),
        ];

        // 6. Lấy lịch sử HLV
        $coachHistory = FootballManagerCareer::query()
            ->where('team_id', $id)
            ->with('manager')
            ->orderBy('date_from', 'desc')
            ->get()
            ->map(function($career) {
                return [
                    'name' => $career->manager->name,
                    'photo' => $career->manager->photo,
                    'season' => $career->date_from ? Carbon::parse($career->date_from)->year : 'N/A',
                    'winRate' => $career->win_pct,
                    'ppg' => 'N/A',
                ];
            });

        $isFavorite = false;
        if ($request->user()) {
            $isFavorite = $request->user()->favoriteTeams()->where('team_id', $id)->exists();
        }

        return Inertia::render('Teams/Show', [
            'team' => $team,
            'recentGames' => $recentGames,
            'upcomingGames' => $upcomingGames,
            'squad' => $squad,
            'standings' => $standings,
            'stats' => $stats,
            'lastMatch' => $lastMatchWithLineup,
            'lastLineup' => $teamLineup,
            'isFavorite' => $isFavorite,
            'coachHistory' => $coachHistory,
            'currentSeason' => $year,
        ]);
    }
}
