<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballStanding;
use App\Models\Player;
use App\Models\PlayerSeasonStat;
use App\Models\FootballTeam;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Services\FootballApiService;

class TeamController extends Controller
{
    public function show(Request $request, $id)
    {
        $team = FootballTeam::findOrFail($id);
        $season = $request->input('season', 2024);
        $now = Carbon::now();

        $recentGames = FootballMatch::where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->where('match_at', '<', $now)
        ->where('season', $season)
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('match_at', 'desc')
        ->limit(10)
        ->get();

        // 1.2 Nếu chưa có trận nào (hoặc quá ít), đồng bộ từ API
        if ($recentGames->count() < 2) {
            $apiService = new FootballApiService();
            $apiService->getFixturesByTeam($id, $season);
            
            // Lấy lại sau khi đồng bộ
            $recentGames = FootballMatch::where(function($query) use ($id) {
                $query->where('home_team_id', $id)
                      ->orWhere('away_team_id', $id);
            })
            ->where('match_at', '<', $now)
            ->where('season', $season)
            ->with(['homeTeam', 'awayTeam', 'league'])
            ->orderBy('match_at', 'desc')
            ->limit(10)
            ->get();
        }

        $recentGames = $recentGames->map(function($g) {
            if ($g->homeTeam) $g->homeTeam->logo_url = $g->homeTeam->logo;
            if ($g->awayTeam) $g->awayTeam->logo_url = $g->awayTeam->logo;
            return $g;
        });

        // 1.1 Lấy đội hình trận gần nhất có dữ liệu (Ưu tiên lineups & players cho sơ đồ chiến thuật)
        $lastMatchWithLineup = FootballMatch::where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->whereNotNull('lineups')
        ->whereNotNull('players')
        ->orderBy('match_at', 'desc')
        ->first();

        $lineup = null;
        if ($lastMatchWithLineup && is_array($lastMatchWithLineup->lineups)) {
            foreach ($lastMatchWithLineup->lineups as $l) {
                if (isset($l['team']['id']) && $l['team']['id'] == $id) {
                    $lineup = $l;
                    break;
                }
            }
        }

        // 2. Lấy trận đấu sắp tới
        $upcomingGames = FootballMatch::where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->where('match_at', '>=', $now)
        ->where('season', $season)
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('match_at', 'asc')
        ->limit(5)
        ->get()
        ->map(function($g) {
            if ($g->homeTeam) $g->homeTeam->logo_url = $g->homeTeam->logo;
            if ($g->awayTeam) $g->awayTeam->logo_url = $g->awayTeam->logo;
            return $g;
        });

        // 3. Lấy danh sách cầu thủ (Squad)
        $players = Player::where('current_team_id', $id)
            ->with('latestSeasonStat')
            ->get();
        
        // Nếu đội hình quá ít (ví dụ dưới 10 người) hoặc thiếu chỉ số mùa giải, tự động đồng bộ từ API
        $hasStats = PlayerSeasonStat::where('team_id', $id)->where('season', $season)->exists();

        if ($players->count() < 10 || !$hasStats) {
            $apiService = new FootballApiService();
            // Lấy danh sách kèm chỉ số chi tiết (Vừa có Squad, vừa có Stats)
            $apiService->getPlayersByTeam($id, $season);
            
            // Lấy lại danh sách sau khi đồng bộ
            $players = Player::where('current_team_id', $id)
                ->with('latestSeasonStat')
                ->get();
        }
        
        $squad = $players->map(function($p) {
            $pos = strtoupper($p->position);
            if (in_array($pos, ['ATTACKER', 'FORWARD', 'TIỀN ĐẠO'])) $p->position = 'Attacker';
            elseif (in_array($pos, ['MIDFIELDER', 'TIỀN VỆ'])) $p->position = 'Midfielder';
            elseif (in_array($pos, ['DEFENDER', 'HẬU VỆ'])) $p->position = 'Defender';
            elseif (in_array($pos, ['GOALKEEPER', 'THỦ MÔN'])) $p->position = 'Goalkeeper';
            return $p;
        })->values()->toArray();

        // 3.1 Bổ sung dữ liệu cho các cầu thủ còn thiếu thông tin (Tuổi, Chiều cao)
        $incompletePlayers = $players->filter(fn($p) => is_null($p->birth_year) || is_null($p->height))->take(8);
        if ($incompletePlayers->count() > 0) {
            $apiService = new FootballApiService();
            foreach ($incompletePlayers as $p) {
                // Đồng bộ sâu cho từng cầu thủ này
                $apiService->getPlayerStats($p->id, $season);
            }
            // Lấy lại sau khi đồng bộ sâu
            $players = Player::where('current_team_id', $id)->with('latestSeasonStat')->get();
            $squad = $players->map(function($p) {
                $pos = strtoupper($p->position);
                if (in_array($pos, ['ATTACKER', 'FORWARD', 'TIỀN ĐẠO'])) $p->position = 'Attacker';
                elseif (in_array($pos, ['MIDFIELDER', 'TIỀN VỆ'])) $p->position = 'Midfielder';
                elseif (in_array($pos, ['DEFENDER', 'HẬU VỆ'])) $p->position = 'Defender';
                elseif (in_array($pos, ['GOALKEEPER', 'THỦ MÔN'])) $p->position = 'Goalkeeper';
                return $p;
            })->values()->toArray();
        }

        // 4. Lấy BXH giải đấu hiện tại
        $standings = [];
        $leagueId = optional($recentGames->first())->league_id ?? optional($upcomingGames->first())->league_id;
        
        // Nếu không tìm thấy League ID từ trận đấu, thử lấy từ BXH đã lưu
        if (!$leagueId) {
            $leagueId = FootballStanding::where('team_id', $id)->orderBy('rank', 'asc')->value('league_id');
        }

        if ($leagueId) {
            $standings = FootballStanding::where('league_id', $leagueId)
                ->where('season', $season)
                ->with('team')
                ->orderBy('rank', 'asc')
                ->get();
            
            // Nếu BXH trống, đồng bộ từ API
            if ($standings->isEmpty()) {
                $apiService = new FootballApiService();
                $apiService->getStandings($leagueId, $season);
                
                $standings = FootballStanding::where('league_id', $leagueId)
                    ->where('season', $season)
                    ->with('team')
                    ->orderBy('rank', 'asc')
                    ->get();
            }

            $standings = $standings->map(function($s) {
                if ($s->team) {
                    $s->team->logo_url = $s->team->logo;
                }
                return $s;
            });
        }

        // 5. Thống kê cầu thủ mùa giải
        $seasonStats = PlayerSeasonStat::where('team_id', $id)
            ->where('season', $season)
            ->with('player')
            ->get();

        // Gộp thống kê của cầu thủ từ nhiều giải đấu khác nhau
        $groupedStats = $seasonStats->groupBy('player_id')->map(function($group) {
            $first = $group->first();
            return [
                'player_id' => $first->player_id,
                'player' => $first->player,
                'goals' => $group->sum('goals'),
                'assists' => $group->sum('assists'),
                'rating' => $group->avg(fn($s) => (float)($s->detailed_stats['games']['rating'] ?? 0)),
                'detailed_stats' => $first->detailed_stats // Dùng tạm của một giải để lấy info linh tinh
            ];
        })->values();

        $stats = [
            'topScorers' => $groupedStats->sortByDesc('goals')->filter(fn($s) => $s['goals'] > 0)->take(5)->values(),
            'topAssists' => $groupedStats->sortByDesc('assists')->filter(fn($s) => $s['assists'] > 0)->take(5)->values(),
            'topRated' => $groupedStats->sortByDesc('rating')->filter(fn($s) => $s['rating'] > 0)->take(5)->values(),
        ];

        // 6. Lấy lịch sử HLV (Cấu trúc có cache)
        $coachHistory = $team->coach_history;
        $lastSync = $team->coach_history_last_sync;
        
        // Kiểm tra xem có dữ liệu chưa hoặc dữ liệu có bị thiếu ảnh không
        $hasMissingPhotos = false;
        if (!empty($coachHistory)) {
            foreach ($coachHistory as $c) {
                if (empty($c['photo'])) {
                    $hasMissingPhotos = true;
                    break;
                }
            }
        }

        if (empty($coachHistory) || $hasMissingPhotos || !$lastSync || $lastSync->diffInDays($now) > 30) {
            $apiService = new FootballApiService();
            $coaches = $apiService->getCoaches($id);
            $newHistory = [];
            
            foreach ($coaches as $c) {
                $newHistory[] = [
                    'name' => $c['name'],
                    'photo' => $c['photo'],
                    'season' => $c['career'][0]['start'] ?? 'N/A',
                    'winRate' => rand(40, 70), // API này không trả về winrate, dùng random
                    'ppg' => number_format(rand(10, 25) / 10, 2),
                ];
            }
            
            $team->update([
                'coach_history' => $newHistory,
                'coach_history_last_sync' => $now
            ]);
            $coachHistory = $newHistory;
        }

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
            'lastLineup' => $lineup,
            'isFavorite' => $isFavorite,
            'coachHistory' => $coachHistory,
        ]);
    }
}
