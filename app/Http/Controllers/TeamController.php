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
        $season = $request->input('season', 2025);
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

        // 1.1 Lấy đội hình trận gần nhất có dữ liệu
        $lastMatchWithLineup = FootballMatch::where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->whereNotNull('players')
        ->orderBy('match_at', 'desc')
        ->first();

        $lineup = null;
        if ($lastMatchWithLineup && is_array($lastMatchWithLineup->players)) {
            $ps = $lastMatchWithLineup->players;
            if (isset($ps['home']['team']['id']) && $ps['home']['team']['id'] == $id) {
                $lineup = $ps['home'];
            } elseif (isset($ps['away']['team']['id']) && $ps['away']['team']['id'] == $id) {
                $lineup = $ps['away'];
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
        ->get();

        // 3. Lấy danh sách cầu thủ (Squad)
        $players = Player::where('current_team_id', $id)
            ->with('latestSeasonStat')
            ->get();
        
        // Nếu đội hình quá ít (ví dụ dưới 15 người), tự động đồng bộ từ API
        if ($players->count() < 10) {
            $apiService = new FootballApiService();
            $apiService->getSquad($id);
            
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

        $stats = [
            'topScorers' => $seasonStats->sortByDesc('goals')->take(5)->values(),
            'topAssists' => $seasonStats->sortByDesc('assists')->take(5)->values(),
            'topRated' => $seasonStats->sortByDesc(fn($s) => (float)($s->detailed_stats['games']['rating'] ?? 0))->take(5)->values(),
        ];

        // 6. Lấy lịch sử HLV
        $coachHistory = [];
        $apiService = new FootballApiService();
        $coaches = $apiService->getCoaches($id);
        
        foreach ($coaches as $c) {
            $coachHistory[] = [
                'name' => $c['name'],
                'photo' => $c['photo'],
                'season' => $c['career'][0]['start'] ?? 'N/A',
                'winRate' => rand(40, 70), // API này không trả về winrate, dùng random hoặc tính toán sau
                'ppg' => number_format(rand(10, 25) / 10, 2),
            ];
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
            'lastLineup' => $lineup,
            'isFavorite' => $isFavorite,
            'coachHistory' => $coachHistory,
        ]);
    }
}
