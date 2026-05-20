<?php

namespace App\Services;

use App\Models\FootballAveragePosition;
use App\Models\FootballBroadcast;
use App\Models\FootballEventMetadata;
use App\Models\FootballEventOddsConsensus;
use App\Models\FootballEventPrediction;
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
use App\Models\FootballFunfact;
use App\Models\FootballMomentum;
use App\Models\FootballLineup;
use App\Models\FootballLineupPlayer;
use App\Models\FootballLineupTeam;
use App\Models\FootballManager;
use App\Models\FootballManagerCareer;
use App\Models\FootballUnavailablePlayer;
use App\Models\FootballPlayerMatchStat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\FootballPlayerCareerStat;
use App\Models\FootballPlayerNationalStat;
use App\Models\FootballPlayerTransfer;
use App\Models\FootballSocialPost;
use App\Models\FootballTvChannel;
use App\Models\FootballXgPerMinute;

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
                ->timeout(20)
                ->withoutVerifying()
                ->get($this->getBaseUrl() . ltrim($endpoint, '/'), $params);

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

    /**
     * Tự động lấy toàn bộ các trang dữ liệu (Pagination)
     */
    public function getAll($endpoint, $params = [])
    {
        $allResults = [];
        $params['limit'] = $params['limit'] ?? 200;
        $params['offset'] = $params['offset'] ?? 0;

        do {
            $data = $this->get($endpoint, $params);
            if (!$data) break;

            // Xử lý các cấu trúc trả về khác nhau
            $results = $data['results'] ?? $data['events'] ?? $data['players'] ?? $data['teams'] ?? $data['seasons'] ?? [];
            $allResults = array_merge($allResults, $results);

            if (isset($data['next']) && $data['next']) {
                $params['offset'] += $params['limit'];
            } else {
                break;
            }
        } while (true);

        return $allResults;
    }

    private $deepSyncCount = 0;
    private const MAX_DEEP_SYNCS_PER_REQUEST = 1000; // Nâng giới hạn để nạp đủ dữ liệu cho cả mùa giải

    public function syncPlayerProfile($playerId, $name = null, $teamId = null)
    {
        if (empty($playerId)) return;
        
        $hasCache = isset($this->playerExistsCache[$playerId]);
        if ($hasCache) {
            if (!empty($name) && $name !== 'Unknown Player') {
                $player = FootballPlayer::query()->find($playerId);
                if ($player && $player->name !== $name) {
                    $player->update(['name' => $name]);
                }
            }
            return;
        }

        $player = FootballPlayer::query()->find($playerId);
        
        if ($player) {
            $updates = [];
            if (!empty($name) && $player->name !== $name && $name !== 'Unknown Player') {
                $updates['name'] = $name;
            }
            if (!empty($updates)) {
                $player->update($updates);
            }
        }
        
        // 1. Kiểm tra xem có cần đồng bộ sâu không
        $needsDeepSync = !$player 
            || $player->name === 'Unknown Player' 
            || empty($player->name) 
            || empty($player->position)
            || empty($player->date_of_birth)
            || ($player->nationality && empty($player->national_team_id));

        if ($needsDeepSync) {
            $playerData = null;
            // Thử đồng bộ sâu nếu chưa chạm giới hạn
            if ($this->deepSyncCount < self::MAX_DEEP_SYNCS_PER_REQUEST) {
                try {
                    $this->deepSyncCount++;
                    $playerData = $this->get("players/{$playerId}/");
                } catch (\Exception $e) {
                    Log::error("Failed to deep sync player {$playerId}: " . $e->getMessage());
                }
            }
            
            // Xử lý lưu dữ liệu
            if ($playerData && !isset($playerData['error'])) {
                $currentTeamId = $playerData['current_team_id'] ?? $teamId;
                if (!empty($currentTeamId)) {
                    $this->syncTeamIfNotExists($currentTeamId, null);
                }

                $nationalTeamId = $playerData['national_team_id'] ?? null;
                if (!empty($nationalTeamId)) {
                    $this->syncTeamIfNotExists($nationalTeamId, null);
                }

                // Use the real player ID returned by the API if it differs from the requested $playerId
                $actualId = $playerData['id'] ?? $playerId;
                // Nếu ID thực khác PID, ghi log để theo dõi
                if ($actualId !== $playerId) {
                    Log::info("syncPlayerProfile: Mapping temporary pid {$playerId} to real player id {$actualId}");
                }
                FootballPlayer::updateOrCreate(
                    ['id' => $actualId],
                    [
                        'name' => $name ?? $playerData['name'] ?? 'Unknown Player',
                        'short_name' => $playerData['short_name'] ?? null,
                        'current_team_id' => $currentTeamId,
                        'position' => $playerData['position'] ?? null,
                        'specific_position' => $playerData['specific_position'] ?? null,
                        'jersey_number' => $playerData['jersey_number'] ?? null,
                        'date_of_birth' => $playerData['date_of_birth'] ?? null,
                        'height_cm' => $playerData['height_cm'] ?? null,
                        'weight_kg' => $playerData['weight_kg'] ?? null,
                        'preferred_foot' => $playerData['preferred_foot'] ?? null,
                        'nationality' => $playerData['nationality'] ?? null,
                        'nationality_code' => $playerData['nationality_code'] ?? $playerData['country_code'] ?? null,
                        'national_team_id' => $nationalTeamId,
                        'market_value_eur' => $playerData['market_value_eur'] ?? null,
                        'contract_until' => $playerData['contract_until'] ?? null,
                        'availability' => $playerData['availability'] ?? 'available',
                    ]
                );
                // Đánh dấu cache cho cả pid và id thực
                $this->playerExistsCache[$playerId] = true;
                $this->playerExistsCache[$actualId] = true;
            } elseif (!$player) {
                // Không tạo stub player để tránh tạo cầu thủ rác/trùng lặp khi API thất bại.
                // Log để theo dõi và bỏ qua. Các bản ghi liên quan (lineup, incident...) 
                // sẽ không được tạo do kiểm tra FootballPlayer::exists() ở bước sau.
                Log::warning("syncPlayerProfile: Không thể tạo player {$playerId}" . ($name ? " ({$name})" : '') . " - API thất bại hoặc trả về lỗi. Bỏ qua.");
            }
        }
        
        $this->playerExistsCache[$playerId] = true;
    }

    private function syncTeamIfNotExists($teamId, $name)
    {
        if (empty($teamId)) return;
        if (isset($this->teamExistsCache[$teamId])) return;

        if (!FootballTeam::query()->where('id', $teamId)->exists()) {
            $teamName = $name;

            if (!$teamName) {
                // Try to fetch team details from API
                $data = $this->get("teams/{$teamId}/");
                if ($data && !isset($data['error'])) {
                    $teamName = $data['name'] ?? $data['short_name'] ?? 'Unknown Team';
                }
            }

            try {
                FootballTeam::create([
                    'id' => $teamId,
                    'name' => $teamName ?? 'Unknown Team',
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to create team {$teamId}: " . $e->getMessage());
            }
        }
        $this->teamExistsCache[$teamId] = true;
    }

    /**
     * Đồng bộ danh sách giải đấu
     */
    public function syncLeagues()
    {
        $results = $this->getAll('leagues/', ['is_active' => true]);
        if (empty($results)) return [];

        foreach ($results as $item) {
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
        return $results;
    }

    /**
     * Đồng bộ danh sách mùa giải của một giải đấu
     */
    public function syncSeasons($leagueId)
    {
        $seasons = $this->getAll("leagues/{$leagueId}/seasons/");
        if (empty($seasons)) return [];

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
        $results = $this->getAll('teams/', ['league_id' => $leagueId]);
        if (empty($results)) return [];

        foreach ($results as $item) {
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
        return $results;
    }

    public function syncPlayersByTeam($teamId, $seasonId)
    {
        $results = $this->getAll('players/', [
            'team_id' => $teamId,
            'season_id' => $seasonId
        ]);

        if (empty($results)) return [];

        foreach ($results as $item) {
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
            $this->syncPlayerCareer($item['id'], $seasonId);
        }
        return $results;
    }

    public function syncPlayerMatches($playerId)
    {
        $player = FootballPlayer::query()->find($playerId);
        $params = ['limit' => 30];
        
        // If we want to focus on club matches for their current team
        if ($player && $player->current_team_id) {
            $params['team_id'] = $player->current_team_id;
        }

        // Use the correct player-specific stats endpoint from BSD v2 docs
        $data = $this->get("players/{$playerId}/stats/", $params);
        
        Log::info("Syncing player matches for {$playerId}. Data keys: " . ($data ? implode(',', array_keys($data)) : 'null'));

        $stats = $data['results'] ?? $data['player_stats'] ?? [];
        
        if (empty($stats)) {
            Log::warning("No player stats found for player {$playerId} at players/{$playerId}/stats/. Response: " . json_encode($data));
            return [];
        }

        Log::info("Found " . count($stats) . " stats records for player {$playerId}");
        $synced = [];

        foreach ($stats as $item) {
            // Ensure the match exists in our DB
            // The item has 'event_id'. We should probably fetch/save the match info if missing.
            $match = FootballMatch::query()->find($item['event_id']);
            if (!$match) {
                // Fetch basic match info to avoid broken relations
                $eventData = $this->get("events/{$item['event_id']}/");
                if ($eventData && !isset($eventData['error'])) {
                    $match = $this->saveMatch($eventData);
                }
            }

            if (!$match) continue;

            $synced[] = FootballPlayerMatchStat::updateOrCreate(
                [
                    'player_id' => $playerId,
                    'event_id' => $match->id,
                ],
                [
                    'team_id' => $item['team_id'] ?? $match->home_team_id,
                    'minutes_played' => $item['minutes_played'] ?? 0,
                    'rating' => $item['rating'] ?? 0,
                    'goals' => $item['goals'] ?? 0,
                    'goal_assist' => $item['goal_assist'] ?? 0,
                    'yellow_card' => $item['yellow_card'] ?? 0,
                    'red_card' => $item['red_card'] ?? 0,
                    'expected_goals' => $item['expected_goals'] ?? null,
                    'expected_assists' => $item['expected_assists'] ?? null,
                    'total_shots' => $item['total_shots'] ?? 0,
                    'shots_on_target' => $item['shots_on_target'] ?? 0,
                    'key_pass' => $item['key_pass'] ?? 0,
                    'total_tackle' => $item['total_tackle'] ?? 0,
                    'interception' => $item['interception'] ?? 0,
                ]
            );
        }
        
        Log::info("Synced " . count($synced) . " match stats for player {$playerId} using players/{id}/stats/ endpoint.");
        return $synced;
    }

    /**
     * Đồng bộ chỉ số sự nghiệp của một cầu thủ (toàn bộ hoặc theo mùa giải)
     */
    public function syncPlayerCareer($playerId, $seasonId = null)
    {
        // Đảm bảo Profile được sync trước
        $this->syncPlayerProfile($playerId);

        $data = $this->get("players/{$playerId}/career/");
        if (!$data || !isset($data['seasons'])) return [];

        $synced = [];
        foreach ($data['seasons'] as $s) {
            // Nếu có seasonId, chỉ sync season đó. Nếu không, sync tất cả.
            if ($seasonId && $s['season_id'] != $seasonId) continue;

            // Đảm bảo League, Season, Team tồn tại để tránh lỗi FK
            if (isset($s['league_id'])) {
                if (!FootballLeague::query()->where('id', $s['league_id'])->exists()) {
                    FootballLeague::create(['id' => $s['league_id'], 'name' => 'Unknown League']);
                }
            }
            if (isset($s['season_id'])) {
                if (!FootballSeason::query()->where('id', $s['season_id'])->exists()) {
                    FootballSeason::create(['id' => $s['season_id'], 'league_id' => $s['league_id'], 'name' => 'Unknown Season', 'year' => 2026]);
                }
            }
            if (isset($s['team_id'])) {
                $this->syncTeamIfNotExists($s['team_id'], null);
            }

            $stat = FootballPlayerCareerStat::updateOrCreate(
                [
                    'player_id' => $playerId,
                    'season_id' => $s['season_id'],
                    'league_id' => $s['league_id'] ?? null,
                    'team_id' => $s['team_id'] ?? null,
                ],
                [
                    'matches' => $s['matches'] ?? 0,
                    'minutes' => $s['minutes'] ?? 0,
                    'goals' => $s['goals'] ?? 0,
                    'assists' => $s['assists'] ?? 0,
                    'avg_rating' => $s['avg_rating'] ?? null,
                ]
            );
            $synced[] = $stat;
        }
        return $synced;
    }

    /**
     * Đồng bộ lịch sử chuyển nhượng của một cầu thủ
     */
    public function syncPlayerTransfers($playerId)
    {
        $data = $this->get("players/{$playerId}/transfers/");
        if (!$data || !isset($data['transfers'])) return [];

        FootballPlayerTransfer::query()->where('player_id', $playerId)->delete();

        $synced = [];
        foreach ($data['transfers'] as $t) {
            if (isset($t['from_team_id'])) $this->syncTeamIfNotExists($t['from_team_id'], $t['from_team_name'] ?? null);
            if (isset($t['to_team_id'])) $this->syncTeamIfNotExists($t['to_team_id'], $t['to_team_name'] ?? null);

            $synced[] = FootballPlayerTransfer::create([
                'player_id' => $playerId,
                'transfer_date' => $t['transfer_date'],
                'from_team_id' => $t['from_team_id'] ?? null,
                'to_team_id' => $t['to_team_id'] ?? null,
                'fee_eur' => $t['fee_eur'] ?? null,
                'fee_description' => $t['fee_description'] ?? null,
                'transfer_type' => $t['transfer_type'] ?? null,
            ]);
        }
        return $synced;
    }

    /**
     * Đồng bộ thông tin đội tuyển quốc gia của cầu thủ
     */
    public function syncPlayerNationalTeam($playerId)
    {
        $data = $this->get("players/{$playerId}/national-team/");
        if (!$data || !isset($data['national_team_id'])) return null;

        $this->syncTeamIfNotExists($data['national_team_id'], null);

        // Update player record with national team ID
        FootballPlayer::query()->where('id', $playerId)->update([
            'national_team_id' => $data['national_team_id']
        ]);

        return FootballPlayerNationalStat::updateOrCreate(
            ['player_id' => $playerId],
            [
                'national_team_id' => $data['national_team_id'],
                'caps' => $data['caps'] ?? 0,
                'goals' => $data['goals'] ?? 0,
                'last_appearance' => isset($data['last_appearance']) ? Carbon::parse($data['last_appearance']) : null,
            ]
        );
    }

    /**
     * Đồng bộ toàn bộ trận đấu của một mùa giải
     */
    public function syncSeasonMatches($seasonId)
    {
        $results = $this->getAll('events/', [
            'season_id' => $seasonId
        ]);

        if (empty($results)) return [];

        $syncedMatches = [];
        foreach ($results as $item) {
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

        // Đảm bảo Manager tồn tại
        if (isset($item['home_coach_id'])) {
            $this->syncManagerIfNotExists($item['home_coach_id']);
        }
        if (isset($item['away_coach_id'])) {
            $this->syncManagerIfNotExists($item['away_coach_id']);
        }

        return FootballMatch::updateOrCreate(
            ['id' => $item['id']],
            [
                'league_id' => $item['league_id'],
                'season_id' => $seasonId,
                'home_team_id' => $item['home_team_id'],
                'away_team_id' => $item['away_team_id'],
                'home_coach_id' => $item['home_coach_id'] ?? null,
                'away_coach_id' => $item['away_coach_id'] ?? null,
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
        set_time_limit(0);
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
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/odds/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/player-stats/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/prediction/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/broadcasts/"),
                $pool->withHeaders($this->getHeaders())->timeout(20)->withoutVerifying()->get($this->getBaseUrl() . "events/{$matchId}/social/"),
            ]);

            $eventData    = $responses[0]->successful() ? $responses[0]->json() : null;
            $statsData    = $responses[1]->successful() ? $responses[1]->json() : null;
            $lineupsData  = $responses[2]->successful() ? $responses[2]->json() : null;
            $incidentsData = $responses[3]->successful() ? $responses[3]->json() : null;
            $metaData     = $responses[4]->successful() ? $responses[4]->json() : null;
            $oddsData     = $responses[5]->successful() ? $responses[5]->json() : null;
            $playerStatsData = $responses[6]->successful() ? $responses[6]->json() : null;
            $predictionsData = $responses[7]->successful() ? $responses[7]->json() : null;
            $broadcastsData = $responses[8]->successful() ? $responses[8]->json() : null;
            $socialData     = $responses[9]->successful() ? $responses[9]->json() : null;

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
                    'home_coach_id' => $eventData['home_coach_id'] ?? $match->home_coach_id,
                    'away_coach_id' => $eventData['away_coach_id'] ?? $match->away_coach_id,
                    'weather_code' => $eventData['weather']['code'] ?? $match->weather_code,
                    'weather_description' => $eventData['weather']['description'] ?? $match->weather_description,
                    'weather_wind_speed' => $eventData['weather']['wind_speed'] ?? ($eventData['weather']['wind'] ?? $match->weather_wind_speed),
                    'weather_temperature_c' => $eventData['weather']['temperature_c'] ?? ($eventData['weather']['temp'] ?? $match->weather_temperature_c),
                    'attendance' => $eventData['attendance'] ?? $match->attendance,
                    'is_local_derby' => isset($eventData['is_local_derby']) ? (bool)$eventData['is_local_derby'] : $match->is_local_derby,
                    'is_neutral_ground' => isset($eventData['is_neutral_ground']) ? (bool)$eventData['is_neutral_ground'] : $match->is_neutral_ground,
                    'travel_distance_km' => $eventData['travel_distance_km'] ?? $match->travel_distance_km,
                    'pitch_condition' => $eventData['pitch_condition'] ?? $match->pitch_condition,
                    'last_updated' => now(),
                ]);

                // Sync Venue & Referee details if missing or placeholders
                if (isset($eventData['venue_id'])) {
                    $this->syncVenueIfNotExists($eventData['venue_id']);
                }
                if (isset($eventData['referee_id'])) {
                    $this->syncRefereeIfNotExists($eventData['referee_id']);
                }
                if (isset($eventData['home_coach_id'])) {
                    $this->syncManagerIfNotExists($eventData['home_coach_id']);
                }
                if (isset($eventData['away_coach_id'])) {
                    $this->syncManagerIfNotExists($eventData['away_coach_id']);
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
                            $this->syncPlayerProfile($pid, $p['name'] ?? null, $match->{$side . '_team_id'});
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

            // Average Positions
            if (isset($statsData['average_positions'])) {
                FootballAveragePosition::query()->where('event_id', $matchId)->delete();
                foreach (['home', 'away'] as $side) {
                    if (isset($statsData['average_positions'][$side])) {
                        foreach ($statsData['average_positions'][$side] as $p) {
                            $pid = $p['pid'] ?? $p['id'] ?? null;
                            if (!$pid) continue;
                            $this->syncPlayerProfile($pid, $p['name'] ?? null, $match->{$side . '_team_id'});
                            
                            // Kiểm tra sự tồn tại trước khi tạo để tránh lỗi FK
                            if (FootballPlayer::query()->where('id', $pid)->exists()) {
                                FootballAveragePosition::create([
                                    'event_id' => $matchId,
                                    'team_id' => $match->{$side . '_team_id'},
                                    'player_id' => $pid,
                                    'x' => $p['pos']['x'] ?? $p['x'] ?? 0,
                                    'y' => $p['pos']['y'] ?? $p['y'] ?? 0,
                                ]);
                            }
                        }
                    }
                }
            }

            // xG per minute
            if (isset($statsData['xg_per_minute'])) {
                FootballXgPerMinute::query()->where('event_id', $matchId)->delete();
                foreach ($statsData['xg_per_minute'] as $m) {
                    FootballXgPerMinute::create([
                        'event_id' => $matchId,
                        'minute' => $m['m'] ?? 0,
                        'xg_home' => $m['xg_home'] ?? 0,
                        'xg_away' => $m['xg_away'] ?? 0,
                        'cum_xg_home' => $m['cum_xg_home'] ?? 0,
                        'cum_xg_away' => $m['cum_xg_away'] ?? 0,
                    ]);
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
                    $this->syncPlayerProfile($pid, $s['player_name'] ?? null, $teamId);

                    if (FootballPlayer::query()->where('id', $pid)->exists()) {
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
                            $this->syncPlayerProfile($p['id'], $p['name'] ?? null, $match->{$side . '_team_id'});
                            
                            if (FootballPlayer::query()->where('id', $p['id'])->exists()) {
                                FootballLineupPlayer::create([
                                    'lineup_team_id' => $lineupTeam->id,
                                    'player_id' => $p['id'],
                                    'jersey_number' => $p['jersey_number'] ?? null,
                                    'position' => $p['position'] ?? null,
                                    'is_substitute' => $index >= count($teamData['players'] ?? []),
                                    'ai_score' => $p['ai_score'] ?? $p['rating'] ?? null,
                                ]);
                            }
                        }

                        // Update Coaches in the match record
                        if (isset($teamData['coach']['id'])) {
                            $coach = $teamData['coach'];
                            // Use Manager model or sync logic if needed, but for now just update match column
                            // Check if manager exists or create
                            FootballManager::updateOrCreate(
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
                            $this->syncPlayerProfile($p['id'], $p['name'] ?? null, $match->{$side . '_team_id'});
                            
                            if (FootballPlayer::query()->where('id', $p['id'])->exists()) {
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
            }

            // 3. Incidents
            if ($incidentsData && isset($incidentsData['incidents'])) {
                FootballIncident::query()->where('event_id', $matchId)->delete();
                foreach ($incidentsData['incidents'] as $e) {
                    if (!is_array($e)) continue;
                    $isHome = $e['is_home'] ?? true;
                    $teamId = $isHome ? $match->home_team_id : $match->away_team_id;
                    $this->syncPlayerProfile($e['player_id'] ?? null, $e['player'] ?? null, $teamId);
                    $this->syncPlayerProfile($e['player_in_id'] ?? null, $e['player_in'] ?? null, $teamId);
                    $this->syncPlayerProfile($e['player_out_id'] ?? null, $e['player_out'] ?? null, $teamId);

                    // Kiểm tra Player ID chính
                    if (!isset($e['player_id']) || FootballPlayer::query()->where('id', $e['player_id'])->exists()) {
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
            }

            // 4. Predictions & AI Insights
            if ($predictionsData) {
                $m = $predictionsData['markets'] ?? [];
                $r = $predictionsData['recommendations'] ?? [];
                
                FootballEventPrediction::updateOrCreate(
                    ['event_id' => $matchId],
                    [
                        'prob_home' => $m['match_result']['prob_home'] ?? null,
                        'prob_draw' => $m['match_result']['prob_draw'] ?? null,
                        'prob_away' => $m['match_result']['prob_away'] ?? null,
                        'predicted_result' => $m['match_result']['predicted'] ?? null,
                        'expected_home_goals' => $m['expected_goals']['home'] ?? null,
                        'expected_away_goals' => $m['expected_goals']['away'] ?? null,
                        'prob_over_15' => $m['over_under']['prob_over_15'] ?? null,
                        'prob_over_25' => $m['over_under']['prob_over_25'] ?? null,
                        'prob_over_35' => $m['over_under']['prob_over_35'] ?? null,
                        'prob_btts_yes' => $m['btts']['prob_yes'] ?? null,
                        'most_likely_score' => $m['score']['most_likely'] ?? null,
                        'favorite' => $r['favorite'] ?? null,
                        'favorite_prob' => $r['favorite_prob'] ?? null,
                        'bet_favorite' => $r['bet_favorite'] ?? false,
                        'over_15' => $r['over_15'] ?? false,
                        'over_25' => $r['over_25'] ?? false,
                        'over_35' => $r['over_35'] ?? false,
                        'btts' => $r['btts'] ?? false,
                        'winner' => $r['winner'] ?? false,
                        'confidence' => $predictionsData['model']['confidence'] ?? null,
                        'model_version' => $predictionsData['model']['version'] ?? null,
                    ]
                );
            }

            // 4.1 Odds
            if ($oddsData && isset($oddsData['odds'])) {
                Log::info("Odds Data for Event {$matchId}: " . json_encode($oddsData['odds']));
                FootballEventOddsConsensus::updateOrCreate(
                    ['event_id' => $matchId],
                    $oddsData['odds']
                );
            }

            // 4.2 Player Stats
            if ($playerStatsData && isset($playerStatsData['player_stats'])) {
                foreach ($playerStatsData['player_stats'] as $ps) {
                    $this->syncPlayerProfile($ps['player_id'], null, $ps['team_id']);
                    
                    if (FootballPlayer::query()->where('id', $ps['player_id'])->exists()) {
                        FootballPlayerMatchStat::updateOrCreate(
                            ['event_id' => $matchId, 'player_id' => $ps['player_id']],
                            $ps
                        );
                    }
                }
            }

            // 5. Metadata
            if ($metaData) {
                FootballEventMetadata::updateOrCreate(
                    ['event_id' => $matchId],
                    [
                        'jerseys' => $metaData['jerseys'] ?? null,
                        'ai_preview_text' => $metaData['ai_preview']['text'] ?? null,
                        'ai_preview_generated_at' => isset($metaData['ai_preview']['generated_at']) ? Carbon::parse($metaData['ai_preview']['generated_at']) : null,
                    ]
                );

                if (isset($metaData['funfacts'])) {
                    FootballFunfact::query()->where('event_id', $matchId)->delete();
                    foreach ($metaData['funfacts'] as $f) {
                        FootballFunfact::create([
                            'event_id' => $matchId,
                            'type_id' => $f['type_id'],
                            'sentence' => $f['sentence'],
                        ]);
                    }
                }

                if (isset($metaData['referee']) && !isset($eventData['referee_id'])) {
                    $this->syncRefereeIfNotExists($metaData['referee']['id']);
                }
            }

            // 5.1 Broadcasts
            if ($broadcastsData && isset($broadcastsData['results'])) {
                foreach ($broadcastsData['results'] as $b) {
                    FootballTvChannel::updateOrCreate(
                        ['id' => $b['channel_id']],
                        ['name' => $b['channel_name'], 'country_code' => $b['country_code']]
                    );

                    FootballBroadcast::updateOrCreate(
                        ['event_id' => $matchId, 'channel_id' => $b['channel_id']],
                        [
                            'country_code' => $b['country_code'],
                            'scheduled_start_time' => Carbon::parse($b['scheduled_start_time']),
                        ]
                    );
                }
            }

            // 5.2 Social Posts
            if ($socialData && isset($socialData['results'])) {
                foreach ($socialData['results'] as $s) {
                    $post = FootballSocialPost::updateOrCreate(
                        ['id' => $s['id']],
                        [
                            'type' => $s['type'],
                            'url' => $s['url'],
                            'text' => $s['text'] ?? null,
                            'title' => $s['title'] ?? null,
                            'thumbnail' => $s['thumbnail'] ?? null,
                            'media' => $s['media'] ?? null,
                            'account_handle' => $s['account']['handle'] ?? null,
                            'account_name' => $s['account']['name'] ?? null,
                            'account_verified' => $s['account']['verified'] ?? false,
                            'published_at' => Carbon::parse($s['published_at']),
                        ]
                    );

                    // Sync relations
                    if (isset($s['linked']['teams'])) {
                        $post->teams()->sync(collect($s['linked']['teams'])->pluck('id'));
                    }
                    if (isset($s['linked']['events'])) {
                        $post->events()->sync(collect($s['linked']['events'])->pluck('id'));
                    }
                    if (isset($s['linked']['players'])) {
                        $post->players()->sync(collect($s['linked']['players'])->pluck('id'));
                    }
                    if (isset($s['linked']['managers'])) {
                        $post->managers()->sync(collect($s['linked']['managers'])->pluck('id'));
                    }
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
                    'played'   => $s['played'],
                    'won'      => $s['won'],
                    'drawn'    => $s['drawn'],
                    'lost'     => $s['lost'],
                    'gf'       => $s['gf'],
                    'ga'       => $s['ga'],
                    'gd'       => $s['gd'],
                    'pts'      => $s['pts'],
                    'xgf'      => $s['xgf'] ?? null,
                    'xga'      => $s['xga'] ?? null,
                    'xgd'      => $s['xgd'] ?? null,
                    'form'     => $s['form'] ?? null,
                    'is_live'  => $s['live'] ?? false,
                    'description' => $s['description'] ?? $s['group_name'] ?? null,
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

    /**
     * Đồng bộ thông tin huấn luyện viên và sự nghiệp của họ
     */
    public function syncManager($managerId)
    {
        $data = $this->get("managers/{$managerId}/");
        if (!$data) return null;

        $manager = FootballManager::updateOrCreate(
            ['id' => $managerId],
            [
                'name' => $data['name'],
                'short_name' => $data['short_name'] ?? null,
                'country' => $data['country'] ?? null,
                'tactical_profile' => $data['tactical_profile'] ?? null,
                'preferred_formation' => $data['preferred_formation'] ?? null,
                'current_team_id' => $data['current_team_id'] ?? null,
                'matches_total' => $data['matches_total'] ?? 0,
                'wins' => $data['wins'] ?? 0,
                'draws' => $data['draws'] ?? 0,
                'losses' => $data['losses'] ?? 0,
                'win_pct' => $data['win_pct'] ?? 0,
                'avg_goals_scored' => $data['avg_goals_scored'] ?? 0,
                'avg_goals_conceded' => $data['avg_goals_conceded'] ?? 0,
                'avg_possession' => $data['avg_possession'] ?? 0,
                'clean_sheet_pct' => $data['clean_sheet_pct'] ?? 0,
                'btts_pct' => $data['btts_pct'] ?? 0,
                'over_25_pct' => $data['over_25_pct'] ?? 0,
                'stats_updated_at' => isset($data['stats_updated_at']) ? Carbon::parse($data['stats_updated_at']) : null,
            ]
        );

        // Đồng bộ sự nghiệp
        $this->syncManagerCareer($managerId);

        return $manager;
    }

    /**
     * Đồng bộ lịch sử nhiệm kỳ của một huấn luyện viên
     */
    public function syncManagerCareer($managerId)
    {
        $data = $this->get("managers/{$managerId}/career/");
        if (!$data || !isset($data['tenures'])) return [];

        foreach ($data['tenures'] as $tenure) {
            // Đảm bảo team tồn tại
            if (isset($tenure['team_id'])) {
                $this->syncTeamIfNotExists($tenure['team_id'], $tenure['team_name'] ?? null);
            }

            FootballManagerCareer::updateOrCreate(
                [
                    'manager_id' => $managerId,
                    'team_id' => $tenure['team_id'],
                    'date_from' => $tenure['date_from'],
                ],
                [
                    'date_to' => $tenure['date_to'],
                    'matches' => $tenure['matches'] ?? 0,
                    'wins' => $tenure['wins'] ?? 0,
                    'draws' => $tenure['draws'] ?? 0,
                    'losses' => $tenure['losses'] ?? 0,
                    'win_pct' => $tenure['win_pct'] ?? 0,
                ]
            );
        }

        return $data['tenures'];
    }

    /**
     * Tìm và đồng bộ huấn luyện viên hiện tại của một đội bóng
     */
    public function syncTeamManager($teamId)
    {
        $data = $this->get('managers/', ['team_id' => $teamId]);
        if (!$data || !isset($data['results']) || empty($data['results'])) return null;

        // API trả về danh sách các manager có "Active tenure" tại team này
        $managerData = $data['results'][0];
        return $this->syncManager($managerData['id']);
    }

    public function syncVenueIfNotExists($venueId)
    {
        if (!$venueId) return;
        $venue = FootballVenue::query()->where('id', $venueId)->first();
        // Cập nhật lại nếu chưa tồn tại hoặc đang là Unknown Venue
        if (!$venue || str_contains($venue->name, 'Unknown Venue')) {
            $venueData = $this->get("venues/{$venueId}/");
            if ($venueData && !isset($venueData['error']) && isset($venueData['name'])) {
                FootballVenue::updateOrCreate(
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
                    FootballVenue::updateOrCreate(['id' => $venueId], ['name' => 'Unknown Venue']);
                }
            }
        }
    }

    public function syncRefereeIfNotExists($refereeId)
    {
        if (!$refereeId) return;
        $referee = FootballReferee::query()->where('id', $refereeId)->first();
        
        // Cập nhật lại nếu chưa tồn tại hoặc đang là Unknown Referee
        if (!$referee || str_contains($referee->name, 'Unknown Referee')) {
            $refereeData = $this->get("referees/{$refereeId}/");
            if ($refereeData && !isset($refereeData['error']) && isset($refereeData['name'])) {
                FootballReferee::updateOrCreate(
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
                    FootballReferee::updateOrCreate(['id' => $refereeId], ['name' => 'Unknown Referee']);
                }
            }
        }
    }

    public function syncManagerIfNotExists($managerId)
    {
        if (!$managerId) return;
        $manager = FootballManager::query()->where('id', $managerId)->first();
        if (!$manager || str_contains($manager->name, 'Unknown Manager')) {
            $this->syncManager($managerId);
        }
    }
}
