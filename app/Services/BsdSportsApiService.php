<?php

namespace App\Services;

use App\Models\FootballLeague;
use App\Models\FootballMatch;
use App\Models\FootballPlayer;
use App\Models\FootballSeason;
use App\Models\FootballTeam;
use App\Models\FootballVenue;
use App\Models\FootballReferee;
use App\Models\FootballIncident;
use App\Models\FootballShotmap;
use App\Models\FootballStanding;
use App\Models\FootballEventStat;
use App\Models\FootballMomentum;
use App\Models\FootballLineup;
use App\Models\FootballLineupPlayer;
use App\Models\FootballLineupTeam;
use App\Models\FootballUnavailablePlayer;
use App\Models\FootballPlayerMatchStat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\FootballPlayerCareerStat;

class BsdSportsApiService
{
    private function getBaseUrl()
    {
        return config('services.bsd_sports_api.base_url');
    }

    private function getHeaders()
    {
        return [
            'Authorization' => 'Token ' . config('services.bsd_sports_api.token'),
        ];
    }

    private $playerExistsCache = [];
    private $teamExistsCache   = [];

    public function get($endpoint, $params = [])
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(10) // Thêm timeout để tránh treo quá lâu
                ->withoutVerifying()
                ->get($this->getBaseUrl() . $endpoint, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("BSD API Error ({$endpoint}): " . $response->status() . ' - ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error("BSD API Exception ({$endpoint}): " . $e->getMessage());
            return null;
        }
    }

    private $deepSyncCount = 0;
    private const MAX_DEEP_SYNCS_PER_REQUEST = 10;

    private function syncPlayerIfNotExists($playerId, $name = null, $teamId = null)
    {
        if (empty($playerId)) return;
        
        if (isset($this->playerExistsCache[$playerId])) return;

        $player = FootballPlayer::query()->find($playerId);
        
        // 1. Kiểm tra xem có cần đồng bộ sâu không (Chưa có player hoặc tên là Unknown)
        $needsDeepSync = !$player || $player->name === 'Unknown Player' || empty($player->name);

        if ($needsDeepSync) {
            // Bước 1: Lưu nhanh thông tin từ Match API để UI có dữ liệu hiển thị ngay
            if ($name && $name !== 'Unknown Player') {
                FootballPlayer::updateOrCreate(
                    ['id' => $playerId],
                    [
                        'name' => $name,
                        'current_team_id' => $teamId,
                    ]
                );
            }

            // Bước 2: Chỉ gọi API chi tiết nếu chưa đạt giới hạn trong request này
            if ($this->deepSyncCount < self::MAX_DEEP_SYNCS_PER_REQUEST) {
                try {
                    $this->deepSyncCount++;
                    $playerData = $this->get("players/{$playerId}/");
                    if ($playerData && !isset($playerData['error'])) {
                        FootballPlayer::updateOrCreate(
                            ['id' => $playerId],
                            [
                                'name' => $playerData['name'] ?? $name ?? 'Unknown Player',
                                'short_name' => $playerData['short_name'] ?? null,
                                'current_team_id' => $playerData['current_team_id'] ?? $teamId,
                                'position' => $playerData['position'] ?? null,
                                'specific_position' => $playerData['specific_position'] ?? null,
                                'photo_url' => $playerData['photo_url'] ?? "https://sports.bzzoiro.com/img/player/{$playerId}/",
                                'market_value_eur' => $playerData['market_value_eur'] ?? null,
                                'contract_until' => $playerData['contract_until'] ?? null,
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to deep sync player {$playerId}: " . $e->getMessage());
                }
            }
        }
        
        $this->playerExistsCache[$playerId] = true;
    }

    private function syncTeamIfNotExists($teamId, $name)
    {
        if (empty($teamId)) return;
        if (isset($this->teamExistsCache[$teamId])) return;

        if (!FootballTeam::query()->where('id', $teamId)->exists()) {
            try {
                FootballTeam::create([
                    'id' => $teamId,
                    'name' => $name ?? 'Unknown Team',
                ]);
            } catch (\Exception $e) {
            }
        }
        $this->teamExistsCache[$teamId] = true;
    }

    /**
     * Đồng bộ danh sách giải đấu
     */
    public function syncLeagues()
    {
        $data = $this->get('leagues/', ['limit' => 200]);
        if (!$data) return [];
        Log::info('BSD API Leagues first 5 items:', array_slice($data['results'], 0, 5));

        foreach ($data['results'] as $item) {
            $leagueData = [
                    'name' => $item['name'],
                    'country' => $item['country'],
                    'is_women' => $item['is_women'],
                    'is_active' => $item['is_active'],
                ];

                FootballLeague::updateOrCreate(
                    ['id' => $item['id']],
                    $leagueData
                );

            // Đồng bộ luôn season hiện tại nếu có
            if (isset($item['current_season'])) {
                $s = $item['current_season'];
                FootballSeason::updateOrCreate(
                    ['id' => $s['id']],
                    [
                        'league_id' => $item['id'],
                        'name' => $s['name'],
                        'year' => $s['year'],
                        'is_current' => true,
                        'start_date' => $s['start_date'] ?? null,
                        'end_date' => $s['end_date'] ?? null,
                    ]
                );
            }
        }
        return $data['results'];
    }

    /**
     * Đồng bộ danh sách mùa giải của một giải đấu
     */
    public function syncSeasons($leagueId)
    {
        $data = $this->get("leagues/{$leagueId}/seasons/", ['limit' => 100]);
        if (!$data) return [];

        // Kiểm tra cấu trúc trả về: có thể là {results: []}, {seasons: []} hoặc trực tiếp là mảng []
        $seasons = [];
        if (isset($data['results'])) {
            $seasons = $data['results'];
        } elseif (isset($data['seasons'])) {
            $seasons = $data['seasons'];
        } elseif (is_array($data) && !isset($data['id'])) {
            // Nếu là mảng trực tiếp (và không phải là 1 object duy nhất có id)
            $seasons = $data;
        }

        foreach ($seasons as $s) {
            FootballSeason::updateOrCreate(
                ['id' => $s['id']],
                [
                    'league_id' => $leagueId,
                    'name' => $s['name'],
                    'year' => $s['year'],
                    'start_date' => $s['start_date'] ?? null,
                    'end_date' => $s['end_date'] ?? null,
                    'is_current' => $s['is_active'] ?? false,
                ]
            );
        }
        return $seasons;
    }

    /**
     * Đồng bộ đội bóng theo giải đấu
     */
    public function syncTeamsByLeague($leagueId)
    {
        $data = $this->get('teams/', ['league_id' => $leagueId]);
        if (!$data) return [];

        foreach ($data['results'] as $item) {
            // Đảm bảo Venue tồn tại để tránh lỗi FK
            if (isset($item['venue_id'])) {
                $this->syncVenueIfNotExists($item['venue_id']);
            }

            $teamData = [
                    'name' => $item['name'],
                    'short_name' => $item['short_name'],
                    'country' => $item['country'],
                    'venue_id' => $item['venue_id'],
                ];

                FootballTeam::updateOrCreate(
                    ['id' => $item['id']],
                    $teamData
                );
        }
        return $data['results'];
    }

    /**
     * Đồng bộ danh sách cầu thủ và chỉ số sự nghiệp của họ
     */
    public function syncPlayersByTeam($teamId, $seasonId)
    {
        $data = $this->get('players/', [
            'team_id' => $teamId,
            'season_id' => $seasonId,
            'limit' => 50
        ]);

        if (!$data) return [];

        foreach ($data['results'] as $item) {
            // 1. Lưu Profile cầu thủ
            FootballPlayer::updateOrCreate(
                ['id' => $item['id']],
                [
                    'name' => $item['name'],
                    'short_name' => $item['short_name'],
                    'position' => $item['position'],
                    'specific_position' => $item['specific_position'] ?? null,
                    'jersey_number' => $item['jersey_number'] ?? null,
                    'date_of_birth' => $item['date_of_birth'] ?? null,
                    'height_cm' => $item['height_cm'] ?? null,
                    'weight_kg' => $item['weight_kg'] ?? null,
                    'preferred_foot' => $item['preferred_foot'] ?? null,
                    'nationality' => $item['nationality'] ?? null,
                    'nationality_code' => $item['nationality_code'] ?? null,
                    'current_team_id' => $teamId,
                    'market_value_eur' => $item['market_value_eur'] ?? null,
                    'contract_until' => $item['contract_until'] ?? null,
                    'availability' => $item['availability'] ?? 'available',
                ]
            );

            // 2. Lưu chỉ số sự nghiệp (Career Stats) - CHỈ đồng bộ nếu chưa có dữ liệu hoặc định kỳ
            // Để tránh treo server, chúng ta sẽ kiểm tra xem đã có dữ liệu gần đây chưa
            $hasStats = FootballPlayerCareerStat::where('player_id', $item['id'])
                ->where('season_id', $seasonId)
                ->exists();

            if (!$hasStats) {
                $career = $this->get("players/{$item['id']}/career/");
                if ($career && isset($career['seasons'])) {
                    foreach ($career['seasons'] as $s) {
                        if ($s['season_id'] == $seasonId) {
                            FootballPlayerCareerStat::updateOrCreate(
                                [
                                    'player_id' => $item['id'],
                                    'season_id' => $seasonId,
                                    'league_id' => $s['league_id'] ?? null,
                                ],
                                [
                                    'team_id' => $teamId,
                                    'goals' => $s['goals'] ?? 0,
                                    'assists' => $s['assists'] ?? 0,
                                    'yellow_cards' => $s['yellow_cards'] ?? 0,
                                    'red_cards' => $s['red_cards'] ?? 0,
                                    'appearances' => $s['appearances'] ?? 0,
                                    'minutes_played' => $s['minutes_played'] ?? 0,
                                ]
                            );
                        }
                    }
                }
            }
        }
        return $data['results'];
    }

    /**
     * Đồng bộ toàn bộ trận đấu của một mùa giải
     */
    public function syncSeasonMatches($seasonId)
    {
        $data = $this->get('events/', [
            'season_id' => $seasonId,
            'limit' => 1000 // Lấy tối đa 1000 trận để bao phủ toàn bộ mùa giải
        ]);

        if (!$data || !isset($data['results'])) return [];

        $syncedMatches = [];
        foreach ($data['results'] as $item) {
            $syncedMatches[] = $this->saveMatch($item);
        }
        
        Log::info("Synced " . count($syncedMatches) . " matches for season $seasonId");
        return $syncedMatches;
    }

    /**
     * Đồng bộ trận đấu theo ngày
     */
    public function syncMatchesByDate($date)
    {
        // Trước khi đồng bộ trận đấu, nên có một số mùa giải cơ bản
        // (Trong thực tế, syncLeagues sẽ lo việc này, nhưng để chắc chắn:)
        
        $data = $this->get('events/', [
            'date_from' => $date,
            'date_to' => $date,
            'limit' => 200
        ]);

        if (!$data) return [];

        foreach ($data['results'] as $item) {
            $this->saveMatch($item);
        }
        return $data['results'];
    }

    public function getLiveEvents()
    {
        $data = $this->get('events/live/');
        if (!$data) return [];

        foreach ($data['events'] as $item) {
            $this->saveMatch($item);
        }
        return $data['events'];
    }

    /**
     * Lưu/Cập nhật một trận đấu vào DB
     */
    public function saveMatch(array $item)
    {
        // Đảm bảo League tồn tại
        if (!FootballLeague::query()->where('id', $item['league_id'])->exists()) {
            FootballLeague::create([
                'id' => $item['league_id'],
                'name' => $item['league_name'] ?? 'Unknown League',
            ]);
        }

        // Đảm bảo Team tồn tại (sơ lược)
        foreach (['home_team', 'away_team'] as $side) {
            $teamId = $item["{$side}_id"];
            if (!FootballTeam::query()->where('id', $teamId)->exists()) {
                FootballTeam::create([
                    'id' => $teamId,
                    'name' => $item[$side] ?? 'Unknown Team',
                ]);
            }
        }

        // BSD Season ID có thể không có trong list events/live, lấy mặc định hoặc từ DB
        $seasonId = $item['season_id'] ?? null;
        
        if ($seasonId && !FootballSeason::query()->where('id', $seasonId)->exists()) {
             FootballSeason::create([
                'id' => $seasonId,
                'league_id' => $item['league_id'],
                'name' => 'Season ' . ($item['season_year'] ?? '2026'),
                'year' => $item['season_year'] ?? 2026,
                'is_current' => true
            ]);
        }

        if (!$seasonId) {
            Log::warning("Match {$item['id']} missing season_id and no current season found for league {$item['league_id']}. Skipping save.");
            return null;
        }

        // Đảm bảo Venue tồn tại
        if (isset($item['venue_id'])) {
            $this->syncVenueIfNotExists($item['venue_id']);
        }

        // Đảm bảo Referee tồn tại
        if (isset($item['referee_id'])) {
            $this->syncRefereeIfNotExists($item['referee_id']);
        }

        return FootballMatch::updateOrCreate(
            ['id' => $item['id']],
            [
                'league_id' => $item['league_id'],
                'season_id' => $seasonId,
                'home_team_id' => $item['home_team_id'],
                'away_team_id' => $item['away_team_id'],
                'venue_id' => $item['venue_id'] ?? null,
                'referee_id' => $item['referee_id'] ?? null,
                'event_date' => Carbon::parse($item['event_date'])->setTimezone('UTC'),
                'status' => $item['status'],
                'round_number' => $item['round_number'] ?? null,
                'current_minute' => $item['current_minute'] ?? null,
                'home_score' => $item['home_score'] ?? 0,
                'away_score' => $item['away_score'] ?? 0,
                'home_score_ht' => $item['home_score_ht'] ?? null,
                'away_score_ht' => $item['away_score_ht'] ?? null,
                'last_updated' => isset($item['last_updated']) ? Carbon::parse($item['last_updated']) : now(),
            ]
        );
    }

    /**
     * Hydrate đầy đủ thông tin cho một trận đấu (Stats, Incidents, Lineups...)
     */
    public function hydrateMatch($matchId)
    {
        set_time_limit(120);
        return DB::transaction(function () use ($matchId) {
            $match = FootballMatch::query()->find($matchId);
            if (!$match) return null;

            // Dọn dẹp cache local cho player IDs
            $this->playerExistsCache = [];

            // 1. GỌI SONG SONG TẤT CẢ CÁC ENDPOINT ĐỂ TỐI ƯU TỐC ĐỘ (Parallel Requests)
            $responses = Http::pool(fn ($pool) => [
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/stats/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/lineups/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/incidents/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/metadata/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "predictions/{$matchId}/"),
            ]);

            $eventData    = $responses[0]->successful() ? $responses[0]->json() : null;
            $statsData    = $responses[1]->successful() ? $responses[1]->json() : null;
            $lineupsData  = $responses[2]->successful() ? $responses[2]->json() : null;
            $incidentsData = $responses[3]->successful() ? $responses[3]->json() : null;
            $metaData     = $responses[4]->successful() ? $responses[4]->json() : null;
            $predictionsData = $responses[5]->successful() ? $responses[5]->json() : null;
            // $standingsData = $responses[6]->successful() ? $responses[6]->json() : null; // Đã lược bỏ để tăng tốc

            // 1.1 Update Match Info
            if ($eventData) {
                $match->update([
                    'status' => $eventData['status'] ?? $match->status,
                    'home_score' => $eventData['home_score'] ?? $match->home_score,
                    'away_score' => $eventData['away_score'] ?? $match->away_score,
                    'home_score_ht' => $eventData['home_score_ht'] ?? ($eventData['scores']['halftime']['home'] ?? $match->home_score_ht),
                    'away_score_ht' => $eventData['away_score_ht'] ?? ($eventData['scores']['halftime']['away'] ?? $match->away_score_ht),
                    'penalty_shootout' => $eventData['penalty_shootout'] ?? ($eventData['scores']['penalty'] ?? $match->penalty_shootout),
                    'period' => $eventData['period'] ?? $match->period,
                    'current_minute' => $eventData['current_minute'] ?? $match->current_minute,
                    'weather_code' => $eventData['weather']['code'] ?? $match->weather_code,
                    'weather_description' => $eventData['weather']['description'] ?? $match->weather_description,
                    'weather_wind_speed' => $eventData['weather']['wind_speed'] ?? ($eventData['weather']['wind'] ?? $match->weather_wind_speed),
                    'weather_temperature_c' => $eventData['weather']['temperature_c'] ?? ($eventData['weather']['temp'] ?? $match->weather_temperature_c),
                    'attendance' => $eventData['attendance'] ?? $match->attendance,
                    'last_updated' => now(),
                ]);

                // Sync Venue & Referee details if missing or placeholders
                if (isset($eventData['venue_id'])) {
                    $this->syncVenueIfNotExists($eventData['venue_id']);
                }
                if (isset($eventData['referee_id'])) {
                    $this->syncRefereeIfNotExists($eventData['referee_id']);
                }
            }

            // 1.2 Statistics & Shotmap & Momentum & Average Positions
            if ($statsData && isset($statsData['stats'])) {
                $data = $statsData['stats'];
                foreach (['home', 'away'] as $side) {
                    if (isset($data[$side])) {
                        $s = $data[$side];
                        FootballEventStat::updateOrCreate(
                            ['event_id' => $matchId, 'team_id' => $match->{$side . '_team_id'}],
                            [
                                'side' => $side,
                                'ball_possession' => $s['ball_possession'] ?? 0,
                                'total_shots' => $s['total_shots'] ?? 0,
                                'shots_on_goal' => $s['shots_on_goal'] ?? 0,
                                'shots_off_goal' => $s['shots_off_goal'] ?? 0,
                                'blocked_shots' => $s['blocked_shots'] ?? 0,
                                'crosses_value' => $s['crosses']['value'] ?? 0,
                                'crosses_total' => $s['crosses']['total'] ?? 0,
                                'crosses_pct' => $s['crosses']['pct'] ?? 0,
                                'dribbles_value' => $s['dribbles']['value'] ?? 0,
                                'dribbles_total' => $s['dribbles']['total'] ?? 0,
                                'dribbles_pct' => $s['dribbles']['pct'] ?? 0,
                                'long_balls_value' => $s['long_balls']['value'] ?? 0,
                                'long_balls_total' => $s['long_balls']['total'] ?? 0,
                                'long_balls_pct' => $s['long_balls']['pct'] ?? 0,
                                'attack' => $s['attack'] ?? 0,
                                'ball_safe' => $s['ball_safe'] ?? 0,
                                'dangerous_attack' => $s['dangerous_attack'] ?? 0,
                                'corner_kicks' => $s['corner_kicks'] ?? 0,
                                'offsides' => $s['offsides'] ?? 0,
                                'fouls' => $s['fouls'] ?? 0,
                                'goalkeeper_saves' => $s['goalkeeper_saves'] ?? 0,
                                'yellow_cards' => $s['yellow_cards'] ?? 0,
                                'red_cards' => $s['red_cards'] ?? 0,
                                'pass_accuracy_pct' => $s['pass_accuracy_pct'] ?? 0,
                                'xg_actual' => $s['xg']['actual'] ?? 0,
                            ]
                        );
                    }
                }
            }

            // Average Positions (Players)
            if (isset($statsData['average_positions'])) {
                foreach (['home', 'away'] as $side) {
                    if (isset($statsData['average_positions'][$side])) {
                        foreach ($statsData['average_positions'][$side] as $p) {
                            $pid = $p['pid'] ?? $p['id'] ?? null;
                            $this->syncPlayerIfNotExists($pid, $p['name'] ?? null, $match->{$side . '_team_id'});
                        }
                    }
                }
            }

            // Momentum
            if (isset($statsData['momentum'])) {
                FootballMomentum::query()->where('event_id', $matchId)->delete();
                $momentumData = [];
                foreach ($statsData['momentum'] as $m) {
                    if (!is_array($m)) continue;
                    $momentumData[] = [
                        'event_id' => $matchId,
                        'minute' => $m['m'] ?? 0,
                        'value' => $m['v'] ?? 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                if (!empty($momentumData)) {
                    FootballMomentum::insert($momentumData);
                }
            }

            // Shotmap
            if (isset($statsData['shotmap'])) {
                FootballShotmap::query()->where('event_id', $matchId)->delete();
                foreach ($statsData['shotmap'] as $s) {
                    if (!is_array($s)) continue;
                    $pid = $s['pid'] ?? $s['player_id'] ?? null;
                    $isHome = $s['home'] ?? ($s['is_home'] ?? true);
                    $teamId = $s['team_id'] ?? ($isHome ? $match->home_team_id : $match->away_team_id);
                    $this->syncPlayerIfNotExists($pid, $s['player_name'] ?? null, $teamId);

                    FootballShotmap::create([
                        'event_id' => $matchId,
                        'team_id' => $teamId,
                        'player_id' => $pid,
                        'minute' => $s['min'] ?? $s['minute'] ?? 0,
                        'x' => $s['pos']['x'] ?? $s['x'] ?? 0,
                        'y' => $s['pos']['y'] ?? $s['y'] ?? 0,
                        'xg' => $s['xg'] ?? null,
                        'body_part' => $s['body'] ?? $s['body_part'] ?? null,
                        'situation' => $s['sit'] ?? $s['situation'] ?? null,
                        'is_goal' => ($s['type'] ?? '') === 'goal' || ($s['is_goal'] ?? false),
                        'shot_type' => $s['type'] ?? null,
                    ]);
                }
            }

            // 2. Lineups
            if ($lineupsData && isset($lineupsData['lineups'])) {
                $data = $lineupsData['lineups'];
                $lineup = FootballLineup::updateOrCreate(
                    ['event_id' => $matchId],
                    ['lineup_status' => $lineupsData['lineup_status'] ?? 'confirmed']
                );

                foreach (['home', 'away'] as $side) {
                    if (isset($data[$side])) {
                        $teamData = $data[$side];
                        $lineupTeam = FootballLineupTeam::updateOrCreate(
                            ['lineup_id' => $lineup->id, 'team_id' => $match->{$side . '_team_id'}],
                            [
                                'formation' => $teamData['formation'] ?? 'N/A',
                                'side' => $side,
                                'confidence' => isset($teamData['confidence']) ? ($teamData['confidence'] > 1 ? $teamData['confidence'] / 100 : $teamData['confidence']) : 1.0,
                            ]
                        );

                        FootballLineupPlayer::query()->where('lineup_team_id', $lineupTeam->id)->delete();
                        foreach (array_merge($teamData['players'] ?? [], $teamData['substitutes'] ?? []) as $index => $p) {
                            if (!isset($p['id'])) continue;
                            $this->syncPlayerIfNotExists($p['id'], $p['name'] ?? null, $match->{$side . '_team_id'});
                            
                            FootballLineupPlayer::create([
                                'lineup_team_id' => $lineupTeam->id,
                                'player_id' => $p['id'],
                                'jersey_number' => $p['jersey_number'] ?? null,
                                'position' => $p['position'] ?? null,
                                'is_substitute' => $index >= count($teamData['players'] ?? []),
                                'ai_score' => $p['ai_score'] ?? null,
                            ]);
                        }

                        // Update Coaches in the match record
                        if (isset($teamData['coach']['id'])) {
                            $coach = $teamData['coach'];
                            // Use Manager model or sync logic if needed, but for now just update match column
                            // Check if manager exists or create
                            \App\Models\FootballManager::updateOrCreate(
                                ['id' => $coach['id']],
                                ['name' => $coach['name']]
                            );
                            $match->{$side . '_coach_id'} = $coach['id'];
                        }
                    }
                }

                // Unavailable
                if (isset($lineupsData['unavailable_players'])) {
                    FootballUnavailablePlayer::query()->where('lineup_id', $lineup->id)->delete();
                    foreach (['home', 'away'] as $side) {
                        foreach ($lineupsData['unavailable_players'][$side] ?? [] as $p) {
                            if (!isset($p['id'])) continue;
                            $this->syncPlayerIfNotExists($p['id'], $p['name'] ?? null, $match->{$side . '_team_id'});
                            FootballUnavailablePlayer::create([
                                'lineup_id' => $lineup->id,
                                'player_id' => $p['id'],
                                'status' => $p['status'] ?? null,
                                'reason' => $p['reason'] ?? null,
                            ]);
                        }
                    }
                }
            }

            // 3. Incidents
            if ($incidentsData && isset($incidentsData['incidents'])) {
                FootballIncident::query()->where('event_id', $matchId)->delete();
                foreach ($incidentsData['incidents'] as $e) {
                    if (!is_array($e)) continue;
                    $isHome = $e['is_home'] ?? true;
                    $teamId = $isHome ? $match->home_team_id : $match->away_team_id;
                    $this->syncPlayerIfNotExists($e['player_id'] ?? null, $e['player'] ?? null, $teamId);
                    $this->syncPlayerIfNotExists($e['player_in_id'] ?? null, $e['player_in'] ?? null, $teamId);
                    $this->syncPlayerIfNotExists($e['player_out_id'] ?? null, $e['player_out'] ?? null, $teamId);

                    FootballIncident::create([
                        'event_id' => $matchId,
                        'type' => $e['type'] ?? 'unknown',
                        'minute' => $e['minute'] ?? 0,
                        'is_home' => $isHome,
                        'player_id' => $e['player_id'] ?? null,
                        'player_in_id' => $e['player_in_id'] ?? null,
                        'player_out_id' => $e['player_out_id'] ?? null,
                        'card_type' => $e['card_type'] ?? null,
                        'period_label' => $e['period_label'] ?? null,
                        'confirmed' => $e['confirmed'] ?? true,
                        'payload' => $e,
                    ]);
                }
            }

            // 4. Predictions & AI Insights
            if ($predictionsData) {
                $match->update(['prediction' => $predictionsData]);
            }

            // 5. Metadata (Đã xử lý Venue/Referee ở trên qua ID)
            if ($metaData) {
                /*
                if (isset($metaData['venue'])) {
                    FootballVenue::updateOrCreate(['id' => $metaData['venue']['id']], [
                        'name' => $metaData['venue']['name'],
                        'city' => $metaData['venue']['city'] ?? null,
                    ]);
                    $match->venue_id = $metaData['venue']['id'];
                }
                */
                if (isset($metaData['referee']) && !isset($eventData['referee_id'])) {
                    $this->syncRefereeIfNotExists($metaData['referee']['id']);
                }
            }

            // 6. Standings (Bỏ qua trong hydrateMatch để tránh timeout, GameController sẽ tự sync nếu thiếu)
            /*
            if ($standingsData) {
                $this->processStandingsData($match->league_id, $match->season_id, $standingsData);
            }
            */

            $match->save();
            return $match;
        });
    }

    private function processStandingsData($leagueId, $seasonId, $data)
    {
        if (isset($data['standings'])) {
            $standings = $data['standings'];
        } elseif (isset($data['results'])) {
            $standings = $data['results'];
        } elseif (is_array($data) && !isset($data['id'])) {
            $standings = $data;
        } else {
            $standings = [];
        }

        foreach ($standings as $s) {
            $this->syncTeamIfNotExists($s['team_id'], $s['team_name'] ?? null);

            FootballStanding::updateOrCreate(
                ['league_id' => $leagueId, 'season_id' => $seasonId, 'team_id' => $s['team_id']],
                [
                    'position' => $s['position'],
                    'played' => $s['played'],
                    'won' => $s['won'],
                    'drawn' => $s['drawn'],
                    'lost' => $s['lost'],
                    'gf' => $s['gf'],
                    'ga' => $s['ga'],
                    'gd' => $s['gd'],
                    'pts' => $s['pts'],
                    'xgf' => $s['xgf'] ?? null,
                    'xga' => $s['xga'] ?? null,
                    'xgd' => $s['xgd'] ?? null,
                    'form' => $s['form'] ?? null,
                    'is_live' => $s['live'] ?? false,
                ]
            );
        }
        return $standings;
    }

    /**
     * Đồng bộ bảng xếp hạng của một giải đấu
     */
    public function syncStandings($leagueId, $seasonId)
    {
        $data = $this->get("leagues/{$leagueId}/standings/", ['season_id' => $seasonId]);
        if (!$data) return [];
        
        $this->teamExistsCache = []; // Reset cache cho standalone call
        return $this->processStandingsData($leagueId, $seasonId, $data);
    }

    /**
     * Lấy lịch sử đối đầu (H2H) giữa 2 đội
     */
    public function getH2H($team1Id, $team2Id)
    {
        // BSD v2 không có endpoint H2H riêng, ta lấy danh sách trận đấu của 2 đội
        // Ở đây ta có thể fetch các trận đấu gần đây của cả 2 đội và lưu vào DB
        // GameController sẽ tự query từ DB
        $this->get("events/", ['team_id' => $team1Id, 'limit' => 20]);
        $this->get("events/", ['team_id' => $team2Id, 'limit' => 20]);
        
        return true;
    }

    public function syncVenueIfNotExists($venueId)
    {
        if (!$venueId) return;
        $venue = \App\Models\FootballVenue::query()->where('id', $venueId)->first();
        // Cập nhật lại nếu chưa tồn tại hoặc đang là Unknown Venue
        if (!$venue || str_contains($venue->name, 'Unknown Venue')) {
            $venueData = $this->get("venues/{$venueId}/");
            if ($venueData && !isset($venueData['error']) && isset($venueData['name'])) {
                \App\Models\FootballVenue::updateOrCreate(
                    ['id' => $venueData['id']],
                    [
                        'name' => $venueData['name'],
                        'city' => $venueData['city'] ?? null,
                        'country' => $venueData['country'] ?? null,
                        'country_code' => $venueData['country_code'] ?? null,
                        'capacity' => $venueData['capacity'] ?? null,
                        'latitude' => $venueData['latitude'] ?? null,
                        'longitude' => $venueData['longitude'] ?? null,
                        'pitch_length_m' => $venueData['pitch_length_m'] ?? null,
                        'pitch_width_m' => $venueData['pitch_width_m'] ?? null,
                        'built_year' => $venueData['built_year'] ?? null,
                        'home_team_id' => $venueData['home_team_id'] ?? null,
                    ]
                );
            } else {
                if (!$venue) {
                    \App\Models\FootballVenue::updateOrCreate(['id' => $venueId], ['name' => 'Unknown Venue']);
                }
            }
        }
    }

    public function syncRefereeIfNotExists($refereeId)
    {
        if (!$refereeId) return;
        $referee = \App\Models\FootballReferee::query()->where('id', $refereeId)->first();
        
        // Cập nhật lại nếu chưa tồn tại hoặc đang là Unknown Referee
        if (!$referee || str_contains($referee->name, 'Unknown Referee')) {
            $refereeData = $this->get("referees/{$refereeId}/");
            if ($refereeData && !isset($refereeData['error']) && isset($refereeData['name'])) {
                \App\Models\FootballReferee::updateOrCreate(
                    ['id' => $refereeData['id']],
                    [
                        'name' => $refereeData['name'],
                        'country' => $refereeData['country'] ?? null,
                        'nationality_a3' => $refereeData['nationality_a3'] ?? null,
                        'birthdate' => $refereeData['birthdate'] ?? null,
                        'matches' => $refereeData['matches'] ?? 0,
                        'total_yellow_cards' => $refereeData['total_yellow_cards'] ?? 0,
                        'total_red_cards' => $refereeData['total_red_cards'] ?? 0,
                        'avg_yellow_per_match' => $refereeData['avg_yellow_per_match'] ?? 0,
                        'avg_red_per_match' => $refereeData['avg_red_per_match'] ?? 0,
                        'avg_goals_per_match' => $refereeData['avg_goals_per_match'] ?? 0,
                        'avg_fouls_per_match' => $refereeData['avg_fouls_per_match'] ?? 0,
                        'career_games' => $refereeData['career_games'] ?? 0,
                        'career_yellow_cards' => $refereeData['career_yellow_cards'] ?? 0,
                        'career_red_cards' => $refereeData['career_red_cards'] ?? 0,
                    ]
                );
            } else {
                if (!$referee) {
                    \App\Models\FootballReferee::updateOrCreate(['id' => $refereeId], ['name' => 'Unknown Referee']);
                }
            }
        }
    }
}
