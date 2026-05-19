<?php

namespace App\Http\Controllers;

use App\Models\FootballEventMetadata;
use App\Models\FootballEventPrediction;
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

        $activeTab = request()->query('tab', 'lineups');
        $validTabs = ['lineups', 'stats', 'h2h', 'standings', 'timeline', 'analysis', 'social'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'lineups';
        }

        // 1. Load match with basic relations first (extremely fast)
        $match = FootballMatch::with([
            'league', 'homeTeam', 'awayTeam', 'venue', 'season',
            'referee', 'homeCoach', 'awayCoach'
        ])->find($id);
        if (!$match) abort(404);

        $status = $this->mapStatus($match->status);
        $isLive = $status === 'live';
        $isUpcoming = $status === 'scheduled' && $match->event_date && $match->event_date->diffInMinutes(now(), false) > -60;
        $isRecentlyFinished = $status === 'finished' && $match->event_date && $match->event_date->diffInHours(now()) < 24;
        $isFinishedOrScheduled = in_array($status, ['finished', 'scheduled']);
        
        // Cheap DB exist checks to see if we are missing data for the active tab
        $isMissingData = false;
        if ($activeTab === 'lineups') {
            $isMissingData = !$match->lineup()->exists() || ($status === 'finished' && !$match->playerStats()->exists());
        } elseif ($activeTab === 'stats') {
            $isMissingData = !$match->stats()->exists() || !$match->shotmap()->exists();
        } elseif ($activeTab === 'timeline') {
            $isMissingData = !$match->incidents()->exists();
        }

        // Calculate if shotmap is incomplete only on the stats tab
        $isShotmapIncomplete = false;
        if ($activeTab === 'stats') {
            $totalShotsInStats = $match->stats()->sum('total_shots');
            $shotmapCount = $match->shotmap()->count();
            $isShotmapIncomplete = ($status === 'finished' || $status === 'live') && $totalShotsInStats > 0 && ($shotmapCount === 0 || ($totalShotsInStats > 5 && $shotmapCount < 2));
        }
        
        // --- LOGIC SO SÁNH & CẬP NHẬT ---
        $shouldFetchApi = request()->has('force') 
            || $isMissingData 
            || $isLive 
            || $isUpcoming 
            || ($isRecentlyFinished && $activeTab === 'stats' && $match->shotmap()->count() === 0) 
            || $isShotmapIncomplete;

        $isCurrentlyFetching = false;
        if ($shouldFetchApi) {
            $lockKey = "api_fetch_lock_{$id}";
            $hasLock = cache()->has($lockKey);
            
            if (!$hasLock || $isLive || request()->has('force')) {
                Log::info("[Match {$id}] Fetching API data for tab {$activeTab}. Missing={$isMissingData}, Live={$isLive}");
                cache()->put($lockKey, true, now()->addSeconds(30));
                
                try {
                    $this->fetchAndCompare($id, $apiService, $match);
                    
                    // Clear cache for ALL tabs since new data has been fetched
                    foreach ($validTabs as $t) {
                        Cache::forget("match_display_v24_{$id}_{$t}");
                    }
                } catch (\Exception $e) {
                    Log::error("[Match {$id}] Error fetching API data: " . $e->getMessage());
                } finally {
                    cache()->forget($lockKey);
                }
            } else {
                $isCurrentlyFetching = true;
                Log::info("[Match {$id}] API fetch locked by another request.");
            }
        }

        // Load relations conditionally based on the active tab
        $tabRelations = [];
        if ($activeTab === 'lineups') {
            $tabRelations = [
                'lineup.teams.players.player',
                'playerStats',
                'incidents.player', 'incidents.playerIn', 'incidents.playerOut'
            ];
        } elseif ($activeTab === 'stats') {
            $tabRelations = [
                'stats',
                'shotmap.player',
                'momentum'
            ];
        } elseif ($activeTab === 'timeline') {
            $tabRelations = [
                'incidents.player', 'incidents.playerIn', 'incidents.playerOut'
            ];
        } elseif ($activeTab === 'analysis') {
            $tabRelations = [
                'funfacts'
            ];
        } elseif ($activeTab === 'social') {
            $tabRelations = [
                'socialPosts'
            ];
        }

        // Refresh and load base relations + conditional tab relations
        $match->refresh()->load(array_merge([
            'league', 'homeTeam', 'awayTeam', 'venue', 'season',
            'referee', 'homeCoach', 'awayCoach'
        ], $tabRelations));

        Log::info("[Match {$id}] Tab={$activeTab}. Loaded.");

        // 2. Build & cache display data per tab
        $displayCacheKey = "match_display_v24_{$id}_{$activeTab}";
        $data = Cache::get($displayCacheKey);
        
        if (!$data) {
            Log::info("[Match {$id}] Cache miss for tab {$activeTab}. Building display data.");
            
            $gameData = [
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
                'season_start_date' => $match->season?->start_date,
                'season_end_date' => $match->season?->end_date,
                
                // Lazy loaded tab specific data
                'statistics' => $activeTab === 'stats' ? $this->shimStatistics($match) : [],
                'momentum' => $activeTab === 'stats' ? ($match->momentum->sortBy('minute')->pluck('value')->toArray()) : [],
                'shotmap' => $activeTab === 'stats' ? $match->shotmap->map(fn($s) => [
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
                ])->toArray() : [],
                'lineups' => $activeTab === 'lineups' ? $this->shimLineups($match) : [],
                'player_stats' => [],
                'events' => ($activeTab === 'timeline' || $activeTab === 'lineups') ? $this->shimEvents($match) : [],
                'injuries' => $activeTab === 'lineups' ? $this->shimInjuries($match) : [],
                'odds' => [], 
                'metadata' => [], 
                'prediction' => null,
                'funfacts' => $activeTab === 'analysis' ? $match->funfacts->map(fn($f) => [
                    'id' => $f->id,
                    'type_id' => $f->type_id,
                    'sentence' => $f->sentence
                ])->toArray() : [],
                'social_posts' => $activeTab === 'social' ? $match->socialPosts()->orderBy('published_at', 'desc')->get()->map(fn($p) => [
                    'id' => $p->id,
                    'type' => $p->type,
                    'url' => $p->url,
                    'text' => $p->text,
                    'title' => $p->title,
                    'thumbnail' => $p->thumbnail,
                    'media' => $p->media,
                    'account_handle' => $p->account_handle,
                    'account_name' => $p->account_name,
                    'account_verified' => $p->account_verified,
                    'published_at' => $p->published_at ? $p->published_at->toIso8601String() : null,
                ])->toArray() : [],
            ];

            // Build player stats lookup on demand
            if ($activeTab === 'lineups' && $match->lineup) {
                $playerStatsLookup = $match->playerStats->keyBy('player_id');
                $gameData['player_stats'] = $match->lineup->teams->flatMap->players->map(function($p) use ($playerStatsLookup) {
                    $stat = $playerStatsLookup->get($p->player_id);
                    return [
                        'id' => $p->player_id,
                        'rating' => $stat?->rating,
                        'minutes_played' => $stat?->minutes_played ?? 0,
                        'goals' => $stat?->goals ?? 0,
                        'assists' => $stat?->goal_assist ?? 0,
                        'expected_goals' => $stat?->expected_goals !== null ? (float)$stat->expected_goals : null,
                        'expected_assists' => $stat?->expected_assists !== null ? (float)$stat->expected_assists : null,
                        'total_shots' => $stat?->total_shots ?? 0,
                        'shots_on_target' => $stat?->shots_on_target ?? 0,
                        'total_pass' => $stat?->total_pass ?? 0,
                        'accurate_pass' => $stat?->accurate_pass ?? 0,
                        'key_pass' => $stat?->key_pass ?? 0,
                        'total_tackle' => $stat?->total_tackle ?? 0,
                        'interception' => $stat?->interception ?? 0,
                        'yellow_card' => $stat?->yellow_card ?? 0,
                        'red_card' => $stat?->red_card ?? 0,
                        'saves' => $stat?->saves ?? 0,
                    ];
                })->values()->all();
            }

            // Build prediction details on demand
            if ($activeTab === 'analysis') {
                $predModel = FootballEventPrediction::query()->where('event_id', $id)->first();
                if ($predModel) {
                    $gameData['prediction'] = [
                        'predictions' => [
                            'percent' => [
                                'home' => $predModel->prob_home ? (number_format($predModel->prob_home * 100, 0) . '%') : '0%',
                                'draw' => $predModel->prob_draw ? (number_format($predModel->prob_draw * 100, 0) . '%') : '0%',
                                'away' => $predModel->prob_away ? (number_format($predModel->prob_away * 100, 0) . '%') : '0%',
                            ]
                        ],
                        'comparison' => [
                            'total' => ['home' => '50%', 'away' => '50%'],
                            'form' => ['home' => '50%', 'away' => '50%'],
                            'att' => ['home' => '50%', 'away' => '50%'],
                            'def' => ['home' => '50%', 'away' => '50%'],
                            'poisson_distribution' => ['home' => '50%', 'away' => '50%'],
                            'h2h' => ['home' => '50%', 'away' => '50%'],
                            'goals' => ['home' => '50%', 'away' => '50%'],
                        ]
                    ];
                }
            }

            $data = ['game' => $gameData];

            // 3. Load H2H matches dynamically
            $data['h2hMatches'] = [];
            if ($activeTab === 'h2h') {
                $homeId = (int)$gameData['home_team']['id'];
                $awayId = (int)$gameData['away_team']['id'];
                
                $data['h2hMatches'] = FootballMatch::with(['homeTeam', 'awayTeam', 'league'])
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
                    ])->values()->all();
            }

            // 4. Load standings dynamically
            $data['standings'] = [];
            if ($activeTab === 'standings') {
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

                // 4.1 Lấy 5 trận gần nhất của giải đấu này cho mỗi đội để hiển thị chi tiết trong tooltip
                $teamIds = $standings->pluck('team_id');
                $allMatches = FootballMatch::query()->where('league_id', $match->league_id)
                    ->where('season_id', $match->season_id)
                    ->where(function($q) use ($teamIds) {
                        $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds);
                    })
                    ->where('status', 'finished')
                    ->orderBy('event_date', 'desc')
                    ->with(['homeTeam', 'awayTeam'])
                    ->get();

                $matchesByTeam = [];
                foreach ($allMatches as $m) {
                    foreach ([$m->home_team_id, $m->away_team_id] as $tId) {
                        if ($teamIds->contains($tId)) {
                            if (!isset($matchesByTeam[$tId])) $matchesByTeam[$tId] = [];
                            if (count($matchesByTeam[$tId]) < 5) {
                                // Tính toán kết quả cho đội này (W/L/D)
                                $res = 'D';
                                if ($m->home_score > $m->away_score) {
                                    $res = ($tId == $m->home_team_id) ? 'W' : 'L';
                                } elseif ($m->home_score < $m->away_score) {
                                    $res = ($tId == $m->away_team_id) ? 'W' : 'L';
                                }

                                $matchesByTeam[$tId][] = [
                                    'date' => optional($m->event_date)->format('d/m'),
                                    'home' => optional($m->homeTeam)->name ?? 'Unknown',
                                    'away' => optional($m->awayTeam)->name ?? 'Unknown',
                                    'score' => "{$m->home_score} - {$m->away_score}",
                                    'res' => $res
                                ];
                            }
                        }
                    }
                }

                $standings->map(function($s) use ($matchesByTeam) {
                    if ($s->team) {
                        $s->team->logo_url = $s->team->logo_url ?: 'https://via.placeholder.com/150?text=' . urlencode($s->team->name);
                    }
                    // Gán 5 trận gần nhất, đảo ngược để khớp với thứ tự form từ cũ đến mới (trái sang phải)
                    $s->recent_matches = array_reverse($matchesByTeam[$s->team_id] ?? []);
                    return $s;
                });

                $data['standings'] = $standings->toArray();
            }

            // 5. Load AI Insights dynamically
            $data['aiInsights'] = null;
            if ($activeTab === 'analysis') {
                $metaModel = FootballEventMetadata::query()->where('event_id', $id)->first();
                $data['aiInsights'] = $metaModel?->ai_preview_text;
            }

            // Cache data conditionally
            $hasData = ($activeTab === 'lineups' && !empty($data['game']['lineups'])) 
                    || ($activeTab === 'stats' && !empty($data['game']['statistics']))
                    || ($activeTab === 'h2h' && !empty($data['h2hMatches']))
                    || ($activeTab === 'standings' && !empty($data['standings']))
                    || ($activeTab === 'timeline' && !empty($data['game']['events']))
                    || ($activeTab === 'analysis' && !empty($data['aiInsights']));

            if ($isLive) {
                Cache::put($displayCacheKey, $data, now()->addMinute());
            } elseif ($hasData || $isFinishedOrScheduled) {
                Cache::put($displayCacheKey, $data, now()->addMinutes(60));
            }
        }

        $response = Inertia::render('Games/Show', [
            'game' => $data['game'],
            'h2hMatches' => $data['h2hMatches'] ?? [],
            'standings' => $data['standings'] ?? [],
            'aiInsights' => $data['aiInsights'] ?? null,
            'momentum' => $data['game']['momentum'] ?? [],
            'shotmap' => $data['game']['shotmap'] ?? [],
            'activeTab' => $activeTab,
        ]);

        Log::info("[Match {$id}] Rendering Show page for tab {$activeTab}");
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
        
        if ($status === 'postponed') {
            return 'postponed';
        }

        if ($status === 'cancelled' || $status === 'abandoned') {
            return 'cancelled';
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
                ['type' => 'Ball Safe', 'value' => $s->ball_safe ?? 0],
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
                        'pos' => $this->cleanPosition($p->player?->specific_position, $p->position), 
                        'grid' => null,
                        'rating' => null
                    ],
                ])->values()->all(),
            ];
        }
        return $result;
    }

    private function cleanPosition($specificPosition, $generalPosition)
    {
        $specificPosition = strtoupper(trim($specificPosition ?? ''));
        $genericPositions = ['G', 'D', 'M', 'F', 'GK', 'DEF', 'MID', 'FW', 'ATT', 'SUB', 'UNKNOWN'];
        
        if (!empty($specificPosition) && !in_array($specificPosition, $genericPositions) && strlen($specificPosition) <= 4) {
            return $specificPosition;
        }
        
        $gen = strtoupper(trim($generalPosition ?? ''));
        return match($gen) {
            'G', 'GK', 'POR', 'GOL', 'GOALKEEPER' => 'GK',
            'D', 'DEF', 'DEFENDER' => 'DF',
            'M', 'MID', 'MIDFIELDER' => 'MF',
            'F', 'FW', 'ATT', 'FORWARD', 'STRIKER' => 'FW',
            default => !empty($gen) ? $gen : 'DF'
        };
    }

    private function getSpecificPlayingPosition($player, $generalPos, $colIdx, $rowSize)
    {
        $dbSpecific = strtoupper(trim($player->player?->specific_position ?? ''));
        $genericPositions = ['G', 'D', 'M', 'F', 'GK', 'DEF', 'MID', 'FW', 'ATT', 'SUB', 'UNKNOWN'];
        
        if (!empty($dbSpecific) && !in_array($dbSpecific, $genericPositions)) {
            return $dbSpecific;
        }

        $generalPos = strtoupper(trim($generalPos));
        if ($generalPos === 'G' || $generalPos === 'GK' || $generalPos === 'GOALKEEPER') {
            return 'GK';
        }

        if ($generalPos === 'D' || $generalPos === 'DEF' || $generalPos === 'DEFENDER') {
            if ($rowSize <= 2) {
                return 'CB';
            }
            if ($colIdx === 1) {
                return $rowSize >= 5 ? 'LWB' : 'LB';
            }
            if ($colIdx === $rowSize) {
                return $rowSize >= 5 ? 'RWB' : 'RB';
            }
            return 'CB';
        }

        if ($generalPos === 'M' || $generalPos === 'MID' || $generalPos === 'MIDFIELDER') {
            if ($rowSize <= 1) {
                return 'CM';
            }
            if ($colIdx === 1) {
                return 'LM';
            }
            if ($colIdx === $rowSize) {
                return 'RM';
            }
            return 'CM';
        }

        if ($generalPos === 'F' || $generalPos === 'FW' || $generalPos === 'FORWARD' || $generalPos === 'ATT' || $generalPos === 'STRIKER') {
            if ($rowSize <= 1) {
                return 'ST';
            }
            if ($colIdx === 1) {
                return 'LW';
            }
            if ($colIdx === $rowSize) {
                return 'RW';
            }
            return 'ST';
        }

        return $generalPos;
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
                'pos' => 'GK',
                'grid' => (!empty($p->grid)) ? $p->grid : "1:1",
                'rating' => null
            ]);
        }

        // Outfield players theo hàng formation
        $playerIdx = 0;
        for ($rowIdx = 1; $rowIdx < count($rows); $rowIdx++) {
            $countInRow = $rows[$rowIdx];
            for ($colIdx = 1; $colIdx <= $countInRow; $colIdx++) {
                if ($playerIdx < $outfieldPlayers->count()) {
                    $p = $outfieldPlayers[$playerIdx];
                    $cat = $normalize($p->position);
                    $specificPos = $this->getSpecificPlayingPosition($p, $cat, $colIdx, $countInRow);
                    
                    $result->push([
                        'player_id' => $p->player_id,
                        'name' => $p->player?->name,
                        'number' => $p->jersey_number,
                        'pos' => $specificPos,
                        'grid' => (!empty($p->grid)) ? $p->grid : ($rowIdx + 1) . ":" . $colIdx,
                        'rating' => null
                    ]);
                    $playerIdx++;
                }
            }
        }

        // Remaining players fallback
        while ($playerIdx < $outfieldPlayers->count()) {
            $p = $outfieldPlayers[$playerIdx];
            $cat = $normalize($p->position);
            $specificPos = $this->getSpecificPlayingPosition($p, $cat, 1, 1);
            $result->push([
                'player_id' => $p->player_id,
                'name' => $p->player?->name,
                'number' => $p->jersey_number,
                'pos' => $specificPos,
                'grid' => (!empty($p->grid)) ? $p->grid : "5:1",
                'rating' => null
            ]);
            $playerIdx++;
        }

        return $result;
    }


    private function shimEvents($match)
    {
        $incidents = $match->incidents->sortBy(function($e) {
            $extra = $e->payload['added_time'] ?? ($e->payload['extra'] ?? 0);
            return $e->minute * 1000 + (int)$extra;
        });
        if ($incidents->isEmpty()) return [];
        
        return array_values($incidents->filter(function($e) {
            // Loại bỏ các sự kiện không phải hành động của cầu thủ (thường gây Unknown Player)
            return !in_array(strtolower($e->type), ['period', 'injurytime']);
        })->map(function($e) use ($match) {
            $player = $e->player;
            $playerIn = $e->playerIn;
            $playerOut = $e->playerOut;

            return [
                'time' => [
                    'elapsed' => $e->minute ?? 0, 
                    'extra' => $e->payload['added_time'] ?? ($e->payload['extra'] ?? null)
                ],
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
        $validTabs = ['lineups', 'stats', 'h2h', 'standings', 'timeline', 'analysis'];
        foreach ($validTabs as $t) {
            Cache::forget("match_display_v24_{$id}_{$t}");
        }
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
     