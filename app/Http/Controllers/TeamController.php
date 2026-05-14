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
use Illuminate\Support\Collection;

class TeamController extends Controller
{
    protected $apiService;

    public function __construct(BsdSportsApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function show(Request $request, $id)
    {
        $team = FootballTeam::findOrFail($id);
        $year = $request->input('season', 2025);
        $now = Carbon::now();

        // Lấy danh sách các mùa giải mà đội bóng này từng tham gia (dựa trên Standings)
        $availableSeasons = FootballSeason::query()
            ->whereHas('standings', function($q) use ($id) {
                $q->where('team_id', $id);
            })
            ->orderBy('year', 'desc')
            ->get()
            ->pluck('year')
            ->unique()
            ->values();

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
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('event_date', 'desc')
        ->limit(10)
        ->get();

        // 1.1 Lấy đội hình trận gần nhất có dữ liệu
        // 1.1 Lấy đội hình trận gần nhất có dữ liệu (ưu tiên trận đã kết thúc, nhưng phải có cầu thủ)
        $lastMatchWithLineup = FootballMatch::query()->where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->whereHas('lineup.teams', function($q) use ($id) {
            $q->where('team_id', $id)->has('players');
        })
        ->with(['lineup.teams.players.player'])
        ->orderBy('event_date', 'desc')
        ->first();

        $lineup = $lastMatchWithLineup?->lineup;
        $teamLineup = null;
        if ($lineup) {
            $lt = $lineup->teams->where('team_id', $id)->first();
            if ($lt) {
                $formation = $lt->formation ?? '4-4-2';
                $players = $lt->players->sortBy('is_substitute');
                
                $startXI = $players->where('is_substitute', false);
                $substitutes = $players->where('is_substitute', true);

                // Synthesize grids for Start XI
                $gridXI = $this->synthesizeGrids($startXI, $formation);

                $teamLineup = [
                    'formation' => $formation,
                    'startXI' => $gridXI->map(fn($p) => [
                        'player' => [
                            'id' => $p['player_id'], 
                            'name' => $p['name'], 
                            'number' => $p['number'], 
                            'pos' => $p['pos'], 
                            'grid' => $p['grid'],
                            'rating' => $p['rating'] ? number_format($p['rating'] * 10, 1) : null
                        ],
                    ])->values()->all(),
                    'substitutes' => $substitutes->map(fn($p) => [
                        'player' => [
                            'id' => $p->player_id, 
                            'name' => $p->player?->name, 
                            'number' => $p->jersey_number, 
                            'pos' => $p->position, 
                            'grid' => null,
                            'rating' => $p->ai_score ? number_format($p->ai_score * 10, 1) : null
                        ],
                    ])->values()->all(),
                ];
            }
        }

        // 2. Lấy trận đấu sắp tới (Không lọc theo mùa giải để luôn thấy trận thực tế nhất)
        $upcomingGames = FootballMatch::query()->where(function($query) use ($id) {
            $query->where('home_team_id', $id)
                  ->orWhere('away_team_id', $id);
        })
        ->where('event_date', '>=', $now)
        ->with(['homeTeam', 'awayTeam', 'league'])
        ->orderBy('event_date', 'asc')
        ->limit(5)
        ->get();

        // 3. Lấy danh sách cầu thủ (Squad)
        $players = FootballPlayer::query()->where('current_team_id', $id)
            ->with('latestSeasonStat')
            ->get();
        
        $hasDetails = $players->isNotEmpty() && $players->filter(fn($p) => !is_null($p->jersey_number) || !is_null($p->date_of_birth))->count() > 0;
        $hasStats = FootballPlayerCareerStat::query()->where('team_id', $id)->where('season_id', $seasonId)->exists();

        if ($players->count() < 10 || !$hasStats || !$hasDetails) {
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
            ->orderBy('date_from', 'asc')
            ->get();

        // Đồng bộ nếu chưa có lịch sử HOẶC HLV hiện tại chưa có dữ liệu chi tiết (matches_total = 0)
        $currentCoach = $coachHistory->last();
        if ($coachHistory->isEmpty() || (optional($currentCoach?->manager)->matches_total == 0)) {
            $bsdApiService = app(BsdSportsApiService::class);
            $bsdApiService->syncTeamManager($id);

            // Lấy lại sau khi đồng bộ
            $coachHistory = FootballManagerCareer::query()
                ->where('team_id', $id)
                ->with('manager')
                ->orderBy('date_from', 'asc')
                ->get();
        }

        $formattedCoachHistory = $coachHistory->map(function($career) {
            $matches = $career->matches ?: 0;
            $wins = $career->wins ?: 0;
            $draws = $career->draws ?: 0;
            $losses = $career->losses ?: 0;
            $ppg = $matches > 0 ? number_format(($wins * 3 + $draws) / $matches, 1) : '0.0';

            // Lấy thông tin giải đấu của đội bóng để hiển thị trong tooltip
            $team = $career->team;
            $league = \App\Models\FootballLeague::whereHas('standings', function($q) use ($team) {
                $q->where('team_id', $team->id);
            })->first();

            return [
                'id' => $career->manager_id,
                'name' => $career->manager->name,
                'photo' => $career->manager->photo,
                'date_from' => $career->date_from,
                'date_to' => $career->date_to,
                'season' => $career->date_from ? (Carbon::parse($career->date_from)->month >= 7 ? Carbon::parse($career->date_from)->year : Carbon::parse($career->date_from)->year - 1) . '/' . (Carbon::parse($career->date_from)->month >= 7 ? Carbon::parse($career->date_from)->year + 1 : Carbon::parse($career->date_from)->year) : 'N/A',
                'display_season' => $career->date_from ? (Carbon::parse($career->date_from)->month >= 7 ? Carbon::parse($career->date_from)->format('y') : Carbon::parse($career->date_from)->subYear()->format('y')) . '/' . (Carbon::parse($career->date_from)->month >= 7 ? Carbon::parse($career->date_from)->addYear()->format('y') : Carbon::parse($career->date_from)->format('y')) : 'N/A',
                'matches' => $matches,
                'wins' => $wins,
                'draws' => $draws,
                'losses' => $losses,
                'winRate' => round($career->win_pct ?: ($matches > 0 ? ($wins / $matches) * 100 : 0)),
                'ppg' => $ppg,
                'league' => [
                    'name' => $league->name ?? 'Premier League',
                    'logo' => $league->id ? "https://sports.bzzoiro.com/img/league/{$league->id}/" : null
                ],
                'tactical_profile' => $career->manager->tactical_profile,
                'preferred_formation' => $career->manager->preferred_formation,
                'avg_possession' => $career->manager->avg_possession,
                'clean_sheet_pct' => $career->manager->clean_sheet_pct,
                'btts_pct' => $career->manager->btts_pct,
                'over_25_pct' => $career->manager->over_25_pct,
                'avg_goals_scored' => $career->manager->avg_goals_scored,
                'avg_goals_conceded' => $career->manager->avg_goals_conceded,
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
            'coachHistory' => $formattedCoachHistory,
            'currentSeason' => (int)$year,
            'availableSeasons' => $availableSeasons,
        ]);
    }

    private function synthesizeGrids($players, $formation)
    {
        $formationParts = explode('-', $formation);
        $rows = [1]; // GK always row 1
        foreach ($formationParts as $part) {
            $rows[] = (int)$part;
        }

        // Chuẩn hóa position về G/D/M/F để phân loại
        $normalize = function($pos) {
            if (!$pos) return 'M';
            $pos = strtoupper(trim($pos));
            if (in_array($pos, ['G', 'GK', 'POR', 'GOL'])) return 'G';
            if (in_array($pos, ['D', 'DEF', 'CB', 'LB', 'RB', 'WB', 'SW'])) return 'D';
            if (in_array($pos, ['F', 'FW', 'ST', 'CF', 'SS', 'LW', 'RW', 'ATT'])) return 'F';
            return 'M';
        };

        $sortedPlayers = ['G' => collect(), 'D' => collect(), 'M' => collect(), 'F' => collect()];
        foreach ($players as $p) {
            $cat = $normalize($p->position);
            $sortedPlayers[$cat]->push($p);
        }

        if ($sortedPlayers['G']->isEmpty() && $players->count() > 0) {
            $first = $players->first();
            $sortedPlayers['G']->push($first);
            $allRest = $players->skip(1)->values();
            $sortedPlayers['D'] = collect();
            $sortedPlayers['M'] = collect();
            $sortedPlayers['F'] = collect();
            foreach ($allRest as $p) {
                $cat = $normalize($p->position);
                if ($cat === 'G') $cat = 'M';
                $sortedPlayers[$cat]->push($p);
            }
        }

        $outfieldPlayers = $sortedPlayers['D']->concat($sortedPlayers['M'])->concat($sortedPlayers['F']);
        $result = collect();

        // GK
        if ($sortedPlayers['G']->count() > 0) {
            $p = $sortedPlayers['G'][0];
            $result->push([
                'player_id' => $p->player_id,
                'name' => $p->player?->name,
                'number' => $p->jersey_number,
                'pos' => $p->position,
                'grid' => (!empty($p->grid)) ? $p->grid : "1:1",
                'rating' => $p->ai_score
            ]);
        }

        // Outfield players theo hàng formation
        $playerIdx = 0;
        for ($rowIdx = 1; $rowIdx < count($rows); $rowIdx++) {
            $countInRow = $rows[$rowIdx];
            for ($colIdx = 1; $colIdx <= $countInRow; $colIdx++) {
                if ($playerIdx < $outfieldPlayers->count()) {
                    $p = $outfieldPlayers[$playerIdx];
                    $result->push([
                        'player_id' => $p->player_id,
                        'name' => $p->player?->name,
                        'number' => $p->jersey_number,
                        'pos' => $p->position,
                        'grid' => (!empty($p->grid)) ? $p->grid : ($rowIdx + 1) . ":" . $colIdx,
                        'rating' => $p->ai_score
                    ]);
                    $playerIdx++;
                }
            }
        }

        // Remaining players fallback
        while ($playerIdx < $outfieldPlayers->count()) {
            $p = $outfieldPlayers[$playerIdx];
            $result->push([
                'player_id' => $p->player_id,
                'name' => $p->player?->name,
                'number' => $p->jersey_number,
                'pos' => $p->position,
                'grid' => (!empty($p->grid)) ? $p->grid : "5:1",
                'rating' => $p->ai_score
            ]);
            $playerIdx++;
        }

        return $result;
    }
}
