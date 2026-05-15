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
        set_time_limit(120);
        // 1. Load match with all necessary relations
        $match = FootballMatch::with([
            'league', 'homeTeam', 'awayTeam', 'venue', 'season',
            'referee', 'homeCoach', 'awayCoach',
            'stats', 'lineup.teams.players.player', 
            'incidents.player', 'incidents.playerIn', 'incidents.playerOut',
            'shotmap.player'
        ])->find($id);
        if (!$match) abort(404);

        $status = $this->mapStatus($match->status);
        $isLive = $status === 'live';
        $isUpcoming = $status === 'scheduled' && $match->event_date && $match->event_date->diffInMinutes(now(), false) > -60;
        $isMissingData = $match->stats->isEmpty() || !$match->lineup || $match->shotmap->isEmpty();
        $isRecentlyFinished = $status === 'finished' && $match->event_date && $match->event_date->diffInHours(now()) < 24;
        
        // Kiểm tra xem shotmap có đầy đủ so với thống kê tổng cú sút không
        $totalShotsInStats = $match->stats->sum('total_shots');
        $shotmapCount = $match->shotmap->count();
        // Nếu trận đã kết thúc hoặc đang live mà stats có sút nhưng shotmap lại quá ít (ví dụ < 2 shots trong khi stats > 5)
        // Hoặc shotmap trống rỗng hoàn toàn mặc dù có stats cú sút
        $isShotmapIncomplete = ($status === 'finished' || $status === 'live') && $totalShotsInStats > 0 && ($shotmapCount === 0 || ($totalShotsInStats > 5 && $shotmapCount < 2));
        
        // --- LOGIC SO SÁNH & CẬP NHẬT ---
        // Xác định xem có cần gọi API không:
        // - Đang live hoặc sắp diễn ra (luôn refresh)
        // - Vừa kết thúc (trong 24h) và chưa có shotmap hoặc data chưa đủ
        // - Có thống kê cú sút nhưng shotmap lại trống rỗng hoặc quá ít
        // - Người dùng ép buộc reload
        $shouldFetchApi = request()->has('force') || $isMissingData || $isLive || $isUpcoming || ($isRecentlyFinished && $shotmapCount === 0) || $isShotmapIncomplete;

        $isCurrentlyFetching = false;
        if ($shouldFetchApi) {
            $lockKey = "api_fetch_lock_{$id}";
            $hasLock = cache()->has($lockKey);
            
            if (!$hasLock || $isLive || request()->has('force')) {
                Log::info("[Match {$id}] Fetching API data. Missing={$isMissingData}, Live={$isLive}, Upcoming={$isUpcoming}");
                cache()->put($lockKey, true, now()->addSeconds(30));
                
                try {
                    $this->fetchAndCompare($id, $apiService, $match);
                    Cache::forget("match_display_v23_{$id}");
                } catch (\Exception $e) {
                    Log::error("[Match {$id}] Error fetching API data: " . $e->getMessage());
                } finally {
                    cache()->forget($lockKey);
                }
            } else {
                // Đang có request khác fetch, chúng ta sẽ không fetch lại nhưng vẫn tiếp tục để build data từ DB
                $isCurrentlyFetching = true;
                Log::info("[Match {$id}] API fetch locked by another request.");
            }
        }

        // LUÔN LUÔN refresh và load lại quan hệ trước khi build data
        $match->refresh()->load([
            'league', 'homeTeam', 'awayTeam', 'venue', 'season',
            'referee', 'homeCoach', 'awayCoach',
            'stats', 'lineup.teams.players.player',
            'incidents.player', 'incidents.playerIn', 'incidents.playerOut',
            'shotmap.player', 'momentum'
        ]);

        Log::info("[Match {$id}] Stats={$match->stats->count()}, HasLineup=" . ($match->lineup ? 'yes' : 'no'));

        // 2. Build & cache display data
        $displayCacheKey = "match_display_v23_{$id}";
        $data = Cache::get($displayCacheKey);
        
        if (!$data) {
            Log::info("[Match {$id}] Cache miss or forced reload. Building display data. League: {$match->league_id}, Season: {$match->season_id}");
            $data = [
                'game' => [
                    'id' => $match->id,
                    'match_datetime' => optional($match->event_date)->toIso8601String(),
                    'status' => $this->mapStatus($match->status),
                    'period' => $match->period,
                    'current_minute' => $match->current_minute,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                    'home_score_ht' => $match->home_score_ht,
                    'away_score_ht' => $match->away_score_ht,
                    'home_team' => [
                        'id' => $match->home_team_id,
                        'name' => optional($match->homeTeam)->name ?? "N/A",
                        'logo_url' => optional($match->homeTeam)->logo_url,
                        'coach' => optional($match->homeCoach)->name,
                    ],
                    'away_team' => [
                        'id' => $match->away_team_id,
                        'name' => optional($match->awayTeam)->name ?? "N/A",
                        'logo_url' => optional($match->awayTeam)->logo_url,
                        'coach' => optional($match->awayCoach)->name,
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
                        'city' => optional($match->venue)->city,
                    ],
                    'referee' => optional($match->referee)->name,
                    'attendance' => $match->attendance,
                    'weather' => [
                        'description' => $match->weather_description,
                        'code' => $match->weather_code,
                        'temp' => $match->weather_temperature_c,
                        'wind' => $match->weather_wind_speed,
                    ],
                    'season' => $match->season->year ?? $match->season_id,
                    'statistics' => $this->shimStatistics($match),
                    'momentum' => $match->momentum->sortBy('minute')->pluck('value')->toArray(),
                    'shotmap' => $match->shotmap->map(fn($s) => [
                        'id' => $s->id,
                        'x' => ($s->team_id == $match->home_team_id) ? (100 - (float)$s->x) : (float)$s->x,
                        'y' => (float)$s->y,
                        'xg' => (float)$s->xg,
                        'type' => $s->is_goal ? 'goal' : $s->shot_type,
                        'type_label' => $s->is_goal ? 'Bàn thắng' : match($s->shot_type) {
                            'save' => 'Cản phá',
                            'miss' => 'Sút ra ngoài',
                            'block' => 'Bị chặn',
                            'post' => 'Trúng cột dọc',
                            default => 'Khác'
                        },
                        'player_name' => $s->player->name ?? 'Unknown',
                        'team_id' => $s->team_id,
                        'team_name' => ($s->team_id == $match->home_team_id) ? $match->homeTeam?->name : $match->awayTeam?->name,
                        'team_logo' => ($s->team_id == $match->home_team_id) ? $match->homeTeam?->logo_url : $match->awayTeam?->logo_url,
                        'body_part' => match($s->body_part) {
                            'right-foot' => 'Chân phải',
                            'left-foot' => 'Chân trái',
                            'head', 'header' => 'Đánh đầu',
                            default => $s->body_part
                        },
                        'situation' => match($s->situation) {
                            'assisted' => 'Phối hợp',
                            'regular' => 'Thường',
                            'fast-break' => 'Phản công',
                            'set-piece' => 'Cố định',
                            'penalty' => 'Phạt đền',
                            'corner' => 'Phạt góc',
                            default => $s->situation
                        },
                    ])->toArray(),
                    'lineups' => $this->shimLineups($match),
                    'player_stats' => $match->lineup ? $match->lineup->teams->flatMap->players->map(fn($p) => [
                        'id' => $p->player_id,
                        'rating' => $p->ai_score
                    ])->values()->all() : [],
                    'events' => $this->shimEvents($match),
                    'injuries' => $this->shimInjuries($match),
                    'odds' => [], 
                    'metadata' => [], 
                ],
            ];

            // H2H & Standings
            $homeId = (int)$data['game']['home_team']['id'];
            $awayId = (int)$data['game']['away_team']['id'];
            
            $h2h = FootballMatch::with(['homeTeam', 'awayTeam', 'league'])
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
                    'fixture' => [
                        'id' => $m->id, 
                        'date' => $m->event_date ? $m->event_date->toIso8601String() : null
                    ],
                    'league' => [
                        'name' => $m->league?->name, 
                        'logo_url' => $m->league?->logo_url
                    ],
                    'teams' => [
                        'home' => [
                            'id' => $m->home_team_id, 
                            'name' => $m->homeTeam?->name, 
                            'logo_url' => $m->homeTeam?->logo_url
                        ],
                        'away' => [
                            'id' => $m->away_team_id, 
                            'name' => $m->awayTeam?->name, 
                            'logo_url' => $m->awayTeam?->logo_url
                        ],
                    ],
                    'goals' => ['home' => $m->home_score, 'away' => $m->away_score]
                ]);

            $standings = FootballStanding::with('team')
                ->where('league_id', $match->league_id)
                ->where('season_id', $match->season_id)
                ->orderBy('position', 'asc')
                ->get();

            // FALLBACK: Nếu không có standings trong DB, thử đồng bộ từ API
            if ($standings->isEmpty()) {
                Log::info("[Match {$id}] Standings empty in DB for League {$match->league_id}, Season {$match->season_id}. Syncing...");
                $apiService->syncStandings($match->league_id, $match->season_id);
                $standings = FootballStanding::with('team')
                    ->where('league_id', $match->league_id)
                    ->where('season_id', $match->season_id)
                    ->orderBy('position', 'asc')
                    ->get();
            }

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

        $response = Inertia::render('Games/Show', [
            'game' => $data['game'],
            'h2hMatches' => $data['h2hMatches'],
            'standings' => $data['standings'],
            'aiInsights' => $data['aiInsights'] ?? null,
            'momentum' => $data['game']['momentum'] ?? [],
            'shotmap' => $data['game']['shotmap'] ?? [],
        ]);

        Log::info("[Match {$id}] Rendering Show page. Shotmap count: " . count($data['game']['shotmap'] ?? []));
        return $response;
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
        $status = strtolower($status);
        if ($status === 'finished' || $status === 'ft' || $status === 'full_time') {
            return 'finished';
        }
        
        $liveStatuses = ['inprogress', 'penalties', '1st_half', 'ht', '2nd_half', 'et', 'postponed_rain', 'postponed_fog'];
        if (in_array($status, $liveStatuses) || str_contains($status, 'half') || str_contains($status, 'time')) {
             return 'live';
        }

        return 'scheduled';
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
                ['type' => 'Shots on Goal', 'value' => $s->shots_on_goal ?? 0],
                ['type' => 'Shots off Goal', 'value' => $s->shots_off_goal ?? 0],
                ['type' => 'Blocked Shots', 'value' => $s->blocked_shots ?? 0],
                ['type' => 'Corner Kicks', 'value' => $s->corner_kicks ?? 0],
                ['type' => 'Offsides', 'value' => $s->offsides ?? 0],
                ['type' => 'Fouls', 'value' => $s->fouls ?? 0],
                ['type' => 'Goalkeeper Saves', 'value' => $s->goalkeeper_saves ?? 0],
                ['type' => 'Yellow Cards', 'value' => $s->yellow_cards ?? 0],
                ['type' => 'Red Cards', 'value' => $s->red_cards ?? 0],
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
                        'grid' => $p['grid'],
                        'rating' => $p['rating'] ?? null
                    ],
                ])->values()->all(),
                'substitutes' => $substitutes->map(fn($p) => [
                    'player' => [
                        'id' => $p->player_id, 
                        'name' => $p->player?->name, 
                        'number' => $p->jersey_number, 
                        'pos' => $p->position, 
                        'grid' => null,
                        'rating' => $p->ai_score
                    ],
                ])->values()->all(),
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
        set_time_limit(120);
        // 1. Xóa cache
        Cache::forget("match_display_v22_{$id}");
        Cache::forget("match_display_v23_{$id}");

        // 2. Ép buộc call API lấy chi tiết
        $apiService->hydrateMatch($id);

        // 3. Sync Standings
        $match = FootballMatch::query()->find($id);
        if ($match && $match->league_id && $match->season_id) {
            $apiService->syncStandings($match->league_id, $match->season_id);
        }

        return back()->with('success', 'Dữ liệu đã được cập nhật!');
    }
}
     