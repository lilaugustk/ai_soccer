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

    private function syncPlayerIfNotExists($playerId, $name, $teamId)
    {
        if (empty($playerId)) return;
        
        // Dùng cache trong bộ nhớ để tránh truy vấn DB lặp lại cho cùng 1 cầu thủ trong 1 request
        if (isset($this->playerExistsCache[$playerId])) return;

        if (!FootballPlayer::query()->where('id', $playerId)->exists()) {
            try {
                FootballPlayer::create([
                    'id' => $playerId,
                    'name' => $name ?? 'Unknown Player',
                    'current_team_id' => $teamId,
                ]);
            } catch (\Exception $e) {
                // Có thể đã được tạo bởi tiến trình khác
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
            if (isset($item['venue_id']) && !FootballVenue::query()->where('id', $item['venue_id'])->exists()) {
                FootballVenue::create(['id' => $item['venue_id'], 'name' => 'Unknown Venue']);
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

            // 2. Lưu chỉ số sự nghiệp (Career Stats) cho mùa giải này
            $career = $this->get("players/{$item['id']}/career/");
            if ($career && isset($career['seasons'])) {
                foreach ($career['seasons'] as $s) {
                    // Đảm bảo các ràng buộc FK được thỏa mãn
                    if (!FootballLeague::query()->where('id', $s['league_id'])->exists()) {
                        FootballLeague::create(['id' => $s['league_id'], 'name' => 'Unknown League']);
                    }
                    if (!FootballTeam::query()->where('id', $s['team_id'])->exists()) {
                        FootballTeam::create(['id' => $s['team_id'], 'name' => 'Unknown Team']);
                    }
                    if (!FootballSeason::query()->where('id', $s['season_id'])->exists()) {
                        FootballSeason::create([
                            'id' => $s['season_id'],
                            'league_id' => $s['league_id'],
                            'name' => 'Season ' . ($s['season_year'] ?? 'N/A'),
                            'year' => $s['season_year'] ?? 2026
                        ]);
                    }

                    FootballPlayerCareerStat::updateOrCreate(
                        [
                            'player_id' => $item['id'],
                            'season_id' => $s['season_id'],
                            'league_id' => $s['league_id'],
                            'team_id' => $s['team_id']
                        ],
                        [
                            'matches' => $s['matches'],
                            'minutes' => $s['minutes'],
                            'goals' => $s['goals'],
                            'assists' => $s['assists'],
                            'avg_rating' => $s['avg_rating'],
                        ]
                    );
                }
            }
        }
        return $data['results'];
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
            $seasonId = FootballSeason::query()->where('league_id', $item['league_id'])->where('is_current', true)->value('id');
        }
        
        // Nếu vẫn không có season, tạo dummy
        if (!$seasonId) {
            $seasonId = $item['league_id'] * 1000 + 2026; 
            FootballSeason::updateOrCreate(['id' => $seasonId], [
                'league_id' => $item['league_id'],
                'name' => 'Season 2026',
                'year' => 2026,
                'is_current' => true
            ]);
        }

        // Đảm bảo Venue tồn tại
        if (isset($item['venue_id']) && !FootballVenue::query()->where('id', $item['venue_id'])->exists()) {
            FootballVenue::create(['id' => $item['venue_id'], 'name' => 'Unknown Venue']);
        }

        // Đảm bảo Referee tồn tại
        if (isset($item['referee_id']) && !FootballReferee::query()->where('id', $item['referee_id'])->exists()) {
            FootballReferee::create(['id' => $item['referee_id'], 'name' => 'Unknown Referee']);
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
                'current_minute' => $item['current_minute'] ?? null,
                'home_score' => $item['home_score'] ?? 0,
                'away_score' => $item['away_score'] ?? 0,
                'last_updated' => isset($item['last_updated']) ? Carbon::parse($item['last_updated']) : null,
            ]
        );
    }

    /**
     * Hydrate đầy đủ thông tin cho một trận đấu (Stats, Incidents, Lineups...)
     */
    public function hydrateMatch($matchId)
    {
        set_time_limit(90);
        return DB::transaction(function () use ($matchId) {
            $match = FootballMatch::query()->find($matchId);
            if (!$match) return null;

            // Dọn dẹp cache local cho player IDs
            $this->playerExistsCache = [];

            // 1. GỌI SONG SONG TẤT CẢ CÁC ENDPOINT ĐỂ TỐI ƯU TỐC ĐỘ (Parallel Requests)
            $responses = Http::pool(fn ($pool) => [
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/"),
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/stats/"),
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/lineups/"),
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/incidents/"),
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/metadata/"),
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "predictions/{$matchId}/"),
                // Đồng bộ luôn bảng xếp hạng của giải đấu này
                $pool->withHeaders($this->getHeaders())->timeout(15)->withoutVerifying()->get($this->getBaseUrl() . "leagues/{$match->league_id}/standings/", ['season_id' => $match->season_id]),
            ]);

            $eventData    = $responses[0]->successful() ? $responses[0]->json() : null;
            $statsData    = $responses[1]->successful() ? $responses[1]->json() : null;
            $lineupsData  = $responses[2]->successful() ? $responses[2]->json() : null;
            $incidentsData = $responses[3]->successful() ? $responses[3]->json() : null;
            $metaData     = $responses[4]->successful() ? $responses[4]->json() : null;
            $predictionsData = $responses[5]->successful() ? $responses[5]->json() : null;
            $standingsData = $responses[6]->successful() ? $responses[6]->json() : null;

            // 1.1 Update Match Info
            if ($eventData) {
                $match->update([
                    'status' => $eventData['status'] ?? $match->status,
                    'home_score' => $eventData['home_score'] ?? $match->home_score,
                    'away_score' => $eventData['away_score'] ?? $match->away_score,
                    'weather_description' => $eventData['weather']['description'] ?? $match->weather_description,
                    'attendance' => $eventData['attendance'] ?? $match->attendance,
                ]);
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
                                'grid' => $p['grid'] ?? null,
                                'is_substitute' => $index >= count($teamData['players'] ?? []),
                                'ai_score' => $p['ai_score'] ?? null,
                            ]);
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

            // 5. Metadata
            if ($metaData) {
                if (isset($metaData['venue'])) {
                    FootballVenue::updateOrCreate(['id' => $metaData['venue']['id']], [
                        'name' => $metaData['venue']['name'],
                        'city' => $metaData['venue']['city'] ?? null,
                    ]);
                    $match->venue_id = $metaData['venue']['id'];
                }
                if (isset($metaData['referee'])) {
                    FootballReferee::updateOrCreate(['id' => $metaData['referee']['id']], [
                        'name' => $metaData['referee']['name'],
                    ]);
                    $match->referee_id = $metaData['referee']['id'];
                }
            }

            // 6. Standings
            if ($standingsData) {
                $this->processStandingsData($match->league_id, $match->season_id, $standingsData);
            }

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
}
