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
use App\Models\FootballHeatmap;
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

    private function get($endpoint, $params = [])
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
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
        return DB::transaction(function () use ($matchId) {
            $match = FootballMatch::query()->find($matchId);
            if (!$match) return null;

        // 1. Statistics
        $statsData = $this->get("events/{$matchId}/stats/");
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
                            'pass_accuracy_pct' => $s['pass_accuracy_pct'] ?? 0,
                            'xg_actual' => $s['xg']['actual'] ?? 0,
                        ]
                    );
                }
            }
        }

        // 2. Lineups
        $lineupsData = $this->get("events/{$matchId}/lineups/");
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

                    // Save Players
                    FootballLineupPlayer::query()->where('lineup_team_id', $lineupTeam->id)->delete();
                    
                    // Start XI
                    foreach ($teamData['players'] ?? [] as $p) {
                        try {
                            if (isset($p['id']) && !FootballPlayer::query()->where('id', $p['id'])->exists()) {
                                FootballPlayer::create([
                                    'id' => $p['id'],
                                    'name' => $p['name'] ?? 'Unknown Player',
                                    'current_team_id' => $match->{$side . '_team_id'},
                                ]);
                            }
                            FootballLineupPlayer::create([
                                'lineup_team_id' => $lineupTeam->id,
                                'player_id' => $p['id'],
                                'jersey_number' => $p['jersey_number'] ?? null,
                                'position' => $p['position'] ?? null,
                                'grid' => $p['grid'] ?? null,
                                'is_substitute' => false,
                                'ai_score' => $p['ai_score'] ?? null,
                            ]);
                        } catch (\Exception $e) {
                            Log::error("Error saving lineup player (XI): " . $e->getMessage(), ['player' => $p]);
                        }
                    }

                    // Substitutes
                    foreach ($teamData['substitutes'] ?? [] as $p) {
                        try {
                            if (isset($p['id']) && !FootballPlayer::query()->where('id', $p['id'])->exists()) {
                                FootballPlayer::create([
                                    'id' => $p['id'],
                                    'name' => $p['name'] ?? 'Unknown Player',
                                    'current_team_id' => $match->{$side . '_team_id'},
                                ]);
                            }
                            FootballLineupPlayer::create([
                                'lineup_team_id' => $lineupTeam->id,
                                'player_id' => $p['id'],
                                'jersey_number' => $p['jersey_number'] ?? null,
                                'position' => $p['position'] ?? null,
                                'grid' => $p['grid'] ?? null,
                                'is_substitute' => true,
                                'ai_score' => $p['ai_score'] ?? null,
                            ]);
                        } catch (\Exception $e) {
                            Log::error("Error saving lineup player (Sub): " . $e->getMessage(), ['player' => $p]);
                        }
                    }
                }
            }

            // Save Unavailable Players
            if (isset($lineupsData['unavailable_players'])) {
                FootballUnavailablePlayer::query()->where('lineup_id', $lineup->id)->delete();
                foreach (['home', 'away'] as $side) {
                    if (isset($lineupsData['unavailable_players'][$side])) {
                        foreach ($lineupsData['unavailable_players'][$side] as $p) {
                            if (!isset($p['id'])) continue;
                            try {
                                if (!FootballPlayer::query()->where('id', $p['id'])->exists()) {
                                    FootballPlayer::create([
                                        'id' => $p['id'],
                                        'name' => $p['name'] ?? 'Unknown Player',
                                        'current_team_id' => $match->{$side . '_team_id'},
                                    ]);
                                }
                                FootballUnavailablePlayer::create([
                                    'lineup_id' => $lineup->id,
                                    'player_id' => $p['id'],
                                    'status' => $p['status'] ?? null,
                                    'reason' => $p['reason'] ?? null,
                                ]);
                            } catch (\Exception $e) {
                                Log::error("Error saving unavailable player: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        // 3. Incidents
        $incidentsData = $this->get("events/{$matchId}/incidents/");
        if ($incidentsData && isset($incidentsData['incidents'])) {
            $data = $incidentsData['incidents'];
            FootballIncident::query()->where('event_id', $matchId)->delete();
            foreach ($data as $e) {
                if (!is_array($e)) continue;

                // Ensure players exist
                foreach (['player_id' => 'player', 'player_in_id' => 'player_in', 'player_out_id' => 'player_out'] as $idKey => $nameKey) {
                    if (isset($e[$idKey]) && !empty($e[$idKey])) {
                        if (!FootballPlayer::query()->where('id', $e[$idKey])->exists()) {
                            try {
                                FootballPlayer::create([
                                    'id' => $e[$idKey],
                                    'name' => $e[$nameKey] ?? 'Unknown Player',
                                    'current_team_id' => $e['is_home'] ? $match->home_team_id : $match->away_team_id,
                                ]);
                            } catch (\Exception $ex) {
                                Log::error("Error creating player from incident: " . $ex->getMessage());
                            }
                        }
                    }
                }

                try {
                    FootballIncident::create([
                        'event_id' => $matchId,
                        'type' => $e['type'] ?? 'unknown',
                        'minute' => $e['minute'] ?? 0,
                        'is_home' => $e['is_home'] ?? true,
                        'player_id' => $e['player_id'] ?? null,
                        'player_in_id' => $e['player_in_id'] ?? null,
                        'player_out_id' => $e['player_out_id'] ?? null,
                        'card_type' => $e['card_type'] ?? null,
                        'period_label' => $e['period_label'] ?? null,
                        'confirmed' => $e['confirmed'] ?? true,
                        'payload' => $e,
                    ]);
                } catch (\Exception $ex) {
                    Log::error("Error saving incident: " . $ex->getMessage());
                }
            }
        }

        // 4. Shotmap
        if (isset($statsData['shotmap'])) {
            $data = $statsData['shotmap'];
            FootballShotmap::query()->where('event_id', $matchId)->delete();
            foreach ($data as $s) {
                if (!is_array($s) || !isset($s['player_id'])) continue;
                try {
                    FootballShotmap::create([
                        'event_id' => $matchId,
                        'team_id' => $s['team_id'] ?? $match->home_team_id,
                        'player_id' => $s['player_id'],
                        'minute' => $s['minute'] ?? 0,
                        'x' => $s['x'] ?? 0,
                        'y' => $s['y'] ?? 0,
                        'xg' => $s['xg'] ?? null,
                        'body_part' => $s['body_part'] ?? null,
                        'situation' => $s['situation'] ?? null,
                        'is_goal' => $s['is_goal'] ?? false,
                    ]);
                } catch (\Exception $ex) {
                    Log::error("Error saving shotmap entry: " . $ex->getMessage());
                }
            }
        }

        // 4.5 Heatmap
        if (isset($statsData['heatmap'])) {
            $data = $statsData['heatmap'];
            FootballHeatmap::query()->where('event_id', $matchId)->delete();
            foreach ($data as $h) {
                if (!is_array($h)) continue;
                try {
                    FootballHeatmap::create([
                        'event_id' => $matchId,
                        'team_id' => $h['team_id'] ?? null,
                        'player_id' => $h['player_id'] ?? null,
                        'x' => $h['x'] ?? 0,
                        'y' => $h['y'] ?? 0,
                        'value' => $h['value'] ?? 1,
                    ]);
                } catch (\Exception $ex) {
                    Log::error("Error saving heatmap entry: " . $ex->getMessage());
                }
            }
        }

        // 5. Metadata
        try {
            $meta = $this->get("events/{$matchId}/metadata/");
            if ($meta) {
                $venueData = $meta['venue'] ?? null;
                if ($venueData) {
                    FootballVenue::updateOrCreate(['id' => $venueData['id']], [
                        'name' => $venueData['name'],
                        'city' => $venueData['city'] ?? null,
                    ]);
                    $match->venue_id = $venueData['id'];
                }
                $refData = $meta['referee'] ?? null;
                if ($refData) {
                    FootballReferee::updateOrCreate(['id' => $refData['id']], [
                        'name' => $refData['name'],
                    ]);
                    $match->referee_id = $refData['id'];
                }
            }
        } catch (\Exception $ex) {
            Log::error("Error saving metadata: " . $ex->getMessage());
        }

            $match->save();
            return $match;
        });
    }

    /**
     * Đồng bộ bảng xếp hạng của một giải đấu
     */
    public function syncStandings($leagueId, $seasonId)
    {
        $data = $this->get("leagues/{$leagueId}/standings/", ['season_id' => $seasonId]);
        if (!$data) return [];

        $standings = isset($data['standings']) ? $data['standings'] : [];
        
        foreach ($standings as $s) {
            // Đảm bảo Team tồn tại
            if (!FootballTeam::query()->where('id', $s['team_id'])->exists()) {
                FootballTeam::create(['id' => $s['team_id'], 'name' => $s['team_name'] ?? 'Unknown Team']);
            }

            FootballStanding::updateOrCreate(
                [
                    'league_id' => $leagueId,
                    'season_id' => $seasonId,
                    'team_id' => $s['team_id']
                ],
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
