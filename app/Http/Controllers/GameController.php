<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\FootballStanding;
use App\Services\StatisticalModelService;
use App\Services\BsdSportsApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Inertia\Inertia;


class GameController extends Controller
{
    public function show($id, BsdSportsApiService $apiService, StatisticalModelService $statService)
    {
        // 1. Load match with all necessary relations
        $match = FootballMatch::with([
            'league', 'homeTeam', 'awayTeam', 'venue', 'season',
            'stats', 'lineup.teams.players.player', 
            'incidents.player', 'incidents.playerIn', 'incidents.playerOut',
            'shotmap', 'heatmap'
        ])->find($id);
        if (!$match) abort(404);

        $status = $this->mapStatus($match->status);
        $isLive = $status === 'live';
        $isUpcoming = $status === 'scheduled' && $match->event_date && $match->event_date->diffInMinutes(now(), false) > -60;
        $isMissingData = $match->stats->isEmpty() || !$match->lineup;

        // --- LOGIC SO SÁNH & CẬP NHẬT ---
        // Xác định xem có cần gọi API không:
        // - Chưa có dữ liệu (isMissingData)
        // - Đang live hoặc sắp diễn ra (luôn refresh)
        // - Người dùng ép buộc reload
        $shouldFetchApi = request()->has('force') || $isMissingData || $isLive || $isUpcoming;

        if ($shouldFetchApi) {
            // Dùng lock để tránh nhiều request đồng thời gọi API cùng lúc
            $lockKey = "api_fetch_lock_{$id}";
            
            // Nếu không live/force và đang có lock (đang fetch), bỏ qua
            $hasLock = cache()->has($lockKey);
            if (!$hasLock || $isLive || request()->has('force')) {
                Log::info("[Match {$id}] Fetching API data. Missing={$isMissingData}, Live={$isLive}, Upcoming={$isUpcoming}");
                
                // Đặt lock tạm thời
                cache()->put($lockKey, true, now()->addSeconds(30));
                
                try {
                    // Gọi API và so sánh/lưu dữ liệu
                    $this->fetchAndCompare($id, $apiService, $match);
                } finally {
                    // Giải phóng lock sau khi xong
                    cache()->forget($lockKey);
                }
                
                // Xóa display cache để rebuild với data mới
                Cache::forget("match_display_v23_{$id}");
            }

            // Reload match với data mới nhất từ DB
            $match->refresh()->load([
                'league', 'homeTeam', 'awayTeam', 'venue', 'season',
                'stats', 'lineup.teams.players.player',
                'incidents.player', 'incidents.playerIn', 'incidents.playerOut',
                'shotmap', 'heatmap'
            ]);
        }

        Log::info("[Match {$id}] Stats={$match->stats->count()}, HasLineup=" . ($match->lineup ? 'yes' : 'no'));

        // 2. Build & cache display data
        $displayCacheKey = "match_display_v23_{$id}";
        $data = Cache::get($displayCacheKey);
        
        if (!$data) {
            $data = [
                'game' => [
                    'id' => $match->id,
                    'match_datetime' => optional($match->event_date)->toIso8601String(),
                    'status' => $this->mapStatus($match->status),
                    'period' => $match->period,
                    'current_minute' => $match->current_minute,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                    'home_team' => [
                        'id' => $match->home_team_id,
                        'name' => optional($match->homeTeam)->name ?? "N/A",
                        'logo_url' => optional($match->homeTeam)->logo_url,
                    ],
                    'away_team' => [
                        'id' => $match->away_team_id,
                        'name' => optional($match->awayTeam)->name ?? "N/A",
                        'logo_url' => optional($match->awayTeam)->logo_url,
                    ],
                    'league' => [
                        'id' => $match->league_id,
                        'name' => optional($match->league)->name ?? "N/A",
                        'logo_url' => optional($match->league)->logo_url,
                        'country' => optional($match->league)->country,
                    ],
                    'venue' => [
                        'id' => $match->venue_id,
                        'name' => optional($match->venue)->name ?? "Unknown Venue",
                    ],
                    'attendance' => $match->attendance,
                    'weather' => $match->weather_description,
                    'season' => $match->season->year ?? $match->season_id,
                    'statistics' => $this->shimStatistics($match),
                    'momentum' => $match->momentum->toArray(),
                    'shotmap' => $match->shotmap->toArray(),
                    'heatmap' => $match->heatmap->toArray(),
                    'lineups' => $this->shimLineups($match),
                    'events' => $this->shimEvents($match),
                    'injuries' => $this->shimInjuries($match),
                    'odds' => [], 
                    'metadata' => [], 
                ],
            ];

            // H2H & Standings
            $homeId = (int)$data['game']['home_team']['id'];
            $awayId = (int)$data['game']['away_team']['id'];
            
            $h2h = FootballMatch::with(['homeTeam', 'awayTeam'])
                ->where(function($q) use ($homeId, $awayId) {
                    $q->where('home_team_id', $homeId)->where('away_team_id', $awayId);
                })
                ->orWhere(function($q) use ($homeId, $awayId) {
                    $q->where('home_team_id', $awayId)->where('away_team_id', $homeId);
                })
                ->orderBy('event_date', 'desc')
                ->limit(10)
                ->get()
                ->map(fn($m) => [
                    'fixture' => ['id' => $m->id, 'date' => $m->event_date],
                    'league' => ['name' => $m->league?->name, 'logo' => $m->league?->logo_url],
                    'teams' => [
                        'home' => ['id' => $m->home_team_id, 'name' => $m->homeTeam?->name, 'logo' => $m->homeTeam?->logo_url],
                        'away' => ['id' => $m->away_team_id, 'name' => $m->awayTeam?->name, 'logo' => $m->awayTeam?->logo_url],
                    ],
                    'goals' => ['home' => $m->home_score, 'away' => $m->away_score]
                ]);

            $standings = FootballStanding::with('team')
                ->where('league_id', $match->league_id)
                ->where('season_id', $match->season_id)
                ->orderBy('position', 'asc')
                ->get();

            $data['h2hMatches'] = $h2h->values()->all();
            $data['standings'] = $standings->values()->all();
            $data['aiInsights'] = null;

            // Cache display data:
            // - Nếu có đủ data (lineups hoặc trận scheduled/finished) → cache 60 phút
            // - Nếu live → cache 1 phút để luôn refresh
            // - Nếu vẫn thiếu data → KHÔNG cache để lần sau tiếp tục retry
            $hasData = !empty($data['game']['lineups']) || !empty($data['game']['statistics']);
            $isFinishedOrScheduled = in_array($status, ['scheduled', 'finished']);
            
            if ($isLive) {
                Cache::put($displayCacheKey, $data, now()->addMinute());
            } elseif ($hasData || $isFinishedOrScheduled) {
                Cache::put($displayCacheKey, $data, now()->addMinutes(60));
            }
            // else: không cache nếu vẫn chưa có data → lần sau sẽ retry API
        }

        return Inertia::render('Games/Show', [
            'game' => $data['game'],
            'h2hMatches' => $data['h2hMatches'],
            'standings' => $data['standings'],
            'aiInsights' => $data['aiInsights'] ?? null,
            'momentum' => $data['game']['momentum'] ?? [],
            'shotmap' => $data['game']['shotmap'] ?? [],
            'heatmap' => $data['game']['heatmap'] ?? [],
        ]);
    }

    /**
     * Gọi API và so sánh với dữ liệu trong DB:
     * - Chưa có dữ liệu → Lưu mới
     * - Đã có nhưng khác dữ liệu mới → Cập nhật
     * - Đã có và giống → Bỏ qua (không ghi DB)
     */
    private function fetchAndCompare($matchId, BsdSportsApiService $apiService, FootballMatch $match): void
    {
        // Kiểm tra nhanh xem API có trả về data không trước khi quyết định
        $hasStats   = $match->stats->isNotEmpty();
        $hasLineup  = (bool) $match->lineup;
        $hasIncidents = $match->incidents->isNotEmpty();

        // Gọi hydrateMatch - nó đã dùng updateOrCreate nên tự động so sánh và update
        // Chỉ cần gọi khi thực sự cần thiết
        $apiService->hydrateMatch($matchId);

        Log::info("[Match {$matchId}] fetchAndCompare done. PrevStats={$hasStats}, PrevLineup={$hasLineup}, PrevIncidents={$hasIncidents}");
    }

    private function mapStatus($status)
    {
        return match ($status) {
            'finished' => 'finished',
            'inprogress', 'penalties' => 'live',
            default => 'scheduled',
        };
    }

    private function shimStatistics($match)
    {
        $stats = $match->stats;
        if ($stats->isEmpty()) return [];
        
        $formatted = [];
        foreach (['home', 'away'] as $side) {
            $teamId = $match->{$side . '_team_id'};
            $s = $stats->where('team_id', $teamId)->first();
            
            if (!$s) {
                $formatted[] = [
                    'team' => ['id' => $teamId, 'name' => optional($match->{$side . 'Team'})->name ?? "N/A"],
                    'statistics' => []
                ];
                continue;
            }

            $teamStats = [
                ['type' => 'Ball Possession', 'value' => ($s->ball_possession ?? 0) . '%'],
                ['type' => 'Total Shots', 'value' => $s->total_shots ?? 0],
                ['type' => 'Crosses', 'value' => ($s->crosses_value ?? 0) . '/' . ($s->crosses_total ?? 0)],
                ['type' => 'Dribbles', 'value' => ($s->dribbles_value ?? 0) . '/' . ($s->dribbles_total ?? 0)],
                ['type' => 'Long Balls', 'value' => ($s->long_balls_value ?? 0) . '/' . ($s->long_balls_total ?? 0)],
                ['type' => 'Attacks', 'value' => $s->attack ?? 0],
                ['type' => 'Dangerous Attacks', 'value' => $s->dangerous_attack ?? 0],
                ['type' => 'Pass Accuracy', 'value' => ($s->pass_accuracy_pct ?? 0) . '%'],
                ['type' => 'Expected Goals', 'value' => $s->xg_actual ?? 0],
            ];

            $formatted[] = [
                'team' => ['id' => $teamId, 'name' => optional($match->{$side . 'Team'})->name ?? "N/A"],
                'statistics' => $teamStats
            ];
        }
        return $formatted;
    }

    private function shimLineups($match)
    {
        if (!$match->lineup) return [];

        $result = [];
        foreach (['home', 'away'] as $side) {
            $teamId = $match->{$side . '_team_id'};
            $lt = $match->lineup->teams->where('team_id', $teamId)->first();
            if (!$lt) continue;

            $formation = $lt->formation ?? '4-4-2';
            $players = $lt->players->sortBy('is_substitute');
            
            $startXI = $players->where('is_substitute', false);
            $substitutes = $players->where('is_substitute', true);

            // Synthesize grids for Start XI
            $gridXI = $this->synthesizeGrids($startXI, $formation);

            $result[] = [
                'team' => ['id' => $teamId, 'name' => optional($match->{$side . 'Team'})->name ?? "N/A"],
                'formation' => $formation,
                'startXI' => $gridXI->map(fn($p) => [
                    'player' => [
                        'id' => $p['player_id'], 
                        'name' => $p['name'], 
                        'number' => $p['number'], 
                        'pos' => $p['pos'], 
                        'grid' => $p['grid']
                    ],
                ])->values(),
                'substitutes' => $substitutes->map(fn($p) => [
                    'player' => [
                        'id' => $p->player_id, 
                        'name' => $p->player?->name, 
                        'number' => $p->jersey_number, 
                        'pos' => $p->position, 
                        'grid' => null
                    ],
                ])->values(),
            ];
        }
        return $result;
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
            if (!$pos) return 'M'; // fallback cho null
            $pos = strtoupper(trim($pos));
            if (in_array($pos, ['G', 'GK', 'POR', 'GOL'])) return 'G';
            if (in_array($pos, ['D', 'DEF', 'CB', 'LB', 'RB', 'WB', 'SW'])) return 'D';
            if (in_array($pos, ['F', 'FW', 'ST', 'CF', 'SS', 'LW', 'RW', 'ATT'])) return 'F';
            // Mặc định là M (midfielder) cho mọi position còn lại
            return 'M';
        };

        $sortedPlayers = ['G' => collect(), 'D' => collect(), 'M' => collect(), 'F' => collect()];
        foreach ($players as $p) {
            $cat = $normalize($p->position);
            $sortedPlayers[$cat]->push($p);
        }

        // Nếu không có GK nào nhưng có player → đặt player đầu tiên làm GK
        if ($sortedPlayers['G']->isEmpty() && $players->count() > 0) {
            $first = $players->first();
            $sortedPlayers['G']->push($first);
            // Bỏ player đầu tiên khỏi danh sách outfield
            $allRest = $players->skip(1)->values();
            $sortedPlayers['D'] = collect();
            $sortedPlayers['M'] = collect();
            $sortedPlayers['F'] = collect();
            foreach ($allRest as $p) {
                $cat = $normalize($p->position);
                if ($cat === 'G') $cat = 'M'; // không để GK trong hàng outfield
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
                'grid' => $p->grid ?? "1:1"
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
                        'grid' => $p->grid ?? ($rowIdx + 1) . ":" . $colIdx
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
                'grid' => $p->grid ?? "5:1"
            ]);
            $playerIdx++;
        }

        return $result;
    }


    private function shimEvents($match)
    {
        $incidents = $match->incidents->sortBy('minute');
        if ($incidents->isEmpty()) return [];
        
        return array_values($incidents->filter(function($e) {
            // Loại bỏ các sự kiện không phải hành động của cầu thủ (thường gây Unknown Player)
            return !in_array(strtolower($e->type), ['period', 'injurytime']);
        })->map(function($e) use ($match) {
            $player = $e->player;
            $playerIn = $e->playerIn;
            $playerOut = $e->playerOut;

            return [
                'time' => ['elapsed' => $e->minute ?? 0, 'extra' => $e->payload['extra'] ?? null],
                'team' => ['id' => $e->is_home ? $match->home_team_id : $match->away_team_id],
                'player' => [
                    'id' => $e->player_id, 
                    'name' => $player->name ?? ($e->payload['player'] ?? null)
                ],
                'assist' => [
                    'id' => $e->payload['assist_id'] ?? null,
                    'name' => $e->payload['assist_name'] ?? null,
                ],
                'subst' => $e->type === 'substitution' ? [
                    'playerIn' => ['id' => $e->player_in_id, 'name' => $playerIn->name ?? ($e->payload['player_in'] ?? 'In')],
                    'playerOut' => ['id' => $e->player_out_id, 'name' => $playerOut->name ?? ($e->payload['player_out'] ?? 'Out')],
                ] : null,
                'type' => $e->type === 'substitution' ? 'subst' : $e->type, 
                'detail' => $e->card_type ?? ($e->payload['detail'] ?? ''),
                'comments' => $e->payload['comments'] ?? null,
            ];
        })->values()->all());
    }

    private function shimInjuries($match)
    {
        if (!$match->lineup) return [];

        // Load unavailable players with their team info (via player's current team)
        $unavailable = $match->lineup->unavailablePlayers()->with('player')->get();

        return $unavailable->map(function($up) {
            return [
                'player' => [
                    'id' => $up->player_id,
                    'name' => $up->player?->name ?? 'Unknown',
                    'photo' => "https://sports.bzzoiro.com/img/player/{$up->player_id}/",
                ],
                'team' => [
                    'id' => $up->player?->current_team_id,
                ],
                'type' => $up->status === 'injured' ? 'Out' : 'Questionable',
                'reason' => $up->reason ?? 'N/A',
            ];
        })->values();
    }

    public function predictions()
    {
        return Inertia::render('Predictions/Index');
    }

    public function sync($id, BsdSportsApiService $apiService)
    {
        // 1. Xóa cache
        Cache::forget("match_display_v22_{$id}");
        Cache::forget("match_display_v23_{$id}");

        // 2. Ép buộc call API lấy chi tiết
        $apiService->hydrateMatch($id);

        return back()->with('success', 'Dữ liệu đã được cập nhật!');
    }
}
     