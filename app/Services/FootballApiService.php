<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\FootballLeague;
use App\Models\FootballTeam;
use App\Models\FootballMatch;
use App\Models\FootballPlayer;
use App\Models\PlayerMatchStat;
use App\Models\FootballScorer;
use App\Models\PlayerSeasonStat;
use App\Models\FootballStanding;
use Illuminate\Support\Carbon;

class FootballApiService
{
    private function getBaseUrl()
    {
        return config('services.football_api.base_url');
    }

    private function getHeaders()
    {
        return [
            'x-apisports-key' => config('services.football_api.key'),
        ];
    }

    /**
     * Đồng bộ mảng kết quả từ API vào Database
     */
    public function syncFixtures(array $fixtures)
    {
        foreach ($fixtures as $item) {
            // 1. Đồng bộ Giải đấu
            $leagueData = $item['league'];
            FootballLeague::updateOrCreate(
                ['id' => $leagueData['id']],
                [
                    'name' => $leagueData['name'],
                    'type' => $leagueData['type'] ?? null,
                    'logo' => $leagueData['logo'] ?? null,
                    'country_name' => $item['league']['country'] ?? null,
                    'country_code' => $item['league']['flag'] ?? null,
                ]
            );

            // 2. Đồng bộ Đội bóng (Home & Away)
            foreach (['home', 'away'] as $side) {
                $teamData = $item['teams'][$side];
                FootballTeam::updateOrCreate(
                    ['id' => $teamData['id']],
                    [
                        'name' => $teamData['name'],
                        'logo' => $teamData['logo'] ?? null,
                    ]
                );
            }

            // 3. Đồng bộ Trận đấu
            $fixtureData = $item['fixture'];
            $goalsData = $item['goals'];

            $matchData = [
                'league_id' => $leagueData['id'],
                'home_team_id' => $item['teams']['home']['id'],
                'away_team_id' => $item['teams']['away']['id'],
                'match_at' => Carbon::parse($fixtureData['date'])->setTimezone('UTC')->toDateTimeString(),
                'status' => $fixtureData['status']['short'] ?? 'NS',
                'round' => $leagueData['round'] ?? null,
                'referee' => $fixtureData['referee'] ?? null,
                'venue_name' => $fixtureData['venue']['name'] ?? null,
                'venue_city' => $fixtureData['venue']['city'] ?? null,
                'attendance' => $fixtureData['attendance'] ?? null,
                'home_score' => $goalsData['home'] ?? null,
                'away_score' => $goalsData['away'] ?? null,
                'season' => $leagueData['season'] ?? ((Carbon::parse($fixtureData['date'])->month >= 7) ? Carbon::parse($fixtureData['date'])->year : \Illuminate\Support\Carbon::parse($fixtureData['date'])->year - 1),
            ];

            // Chỉ cập nhật các trường chi tiết nếu có dữ liệu từ API
            if (isset($item['events'])) $matchData['events'] = $item['events'];
            if (isset($item['lineups'])) $matchData['lineups'] = $item['lineups'];
            if (isset($item['statistics'])) $matchData['statistics'] = $item['statistics'];
            if (isset($item['players'])) $matchData['players'] = $item['players'];

            $match = FootballMatch::updateOrCreate(
                ['id' => $fixtureData['id']],
                $matchData
            );

            // 4. Đồng bộ Cầu thủ & Chỉ số trận đấu (nếu có dữ liệu chi tiết)
            if (isset($item['players'])) {
                foreach ($item['players'] as $teamData) {
                    $teamId = $teamData['team']['id'];
                    foreach ($teamData['players'] as $playerData) {
                        $p = $playerData['player'];
                        $stats = $playerData['statistics'][0] ?? [];

                        // Cập nhật hồ sơ cầu thủ
                        FootballPlayer::updateOrCreate(
                            ['id' => $p['id']],
                            [
                                'name' => $p['name'],
                                'photo' => $p['photo'] ?? null,
                                'current_team_id' => $teamId
                            ]
                        );

                        // Lưu chỉ số trận này
                        PlayerMatchStat::updateOrCreate(
                            [
                                'match_id' => $match->id,
                                'player_id' => $p['id']
                            ],
                            [
                                'team_id' => $teamId,
                                'position' => $stats['games']['position'] ?? null,
                                'age' => null, // Có thể lấy từ API khác nếu cần
                                'minutes' => $stats['games']['minutes'] ?? 0,
                                'detailed_stats' => $stats
                            ]
                        );
                    }
                }
            }
            // Nếu không có 'players' nhưng có 'lineups', ít nhất hãy tạo hồ sơ cầu thủ
            elseif (isset($item['lineups'])) {
                foreach ($item['lineups'] as $lineup) {
                    $teamId = $lineup['team']['id'];
                    $allPlayers = array_merge($lineup['startXI'] ?? [], $lineup['substitutes'] ?? []);
                    foreach ($allPlayers as $pData) {
                        $p = $pData['player'];
                        FootballPlayer::updateOrCreate(
                            ['id' => $p['id']],
                            [
                                'name' => $p['name'],
                                'current_team_id' => $teamId
                            ]
                        );
                    }
                }
            }
        }
    }

    public function getLiveMatches()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures', [
                    'live' => 'all' 
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'];
                $this->syncFixtures($data); // Lưu vào DB
                return $data;
            }

            Log::error('API Error (Live): ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Live): ' . $e->getMessage());
            return [];
        }
    }

    public function getLeagues()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'leagues');

            if ($response->successful()) {
                return $response->json()['response'];
            }

            Log::error('API Error (Leagues): ' . $response->status() . ' - ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Leagues): ' . $e->getMessage());
            return [];
        }
    }

    public function getLeagueSeasons($id)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'leagues', [
                    'id' => $id
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'][0] ?? null;
                if ($data && isset($data['seasons'])) {
                    // Trả về danh sách năm (ví dụ: [2024, 2023, 2022...])
                    return collect($data['seasons'])->pluck('year')->reverse()->values()->all();
                }
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (LeagueSeasons): ' . $e->getMessage());
            return [];
        }
    }

    public function getFixtureDetails($id)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures', [
                    'id' => $id
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'][0] ?? null;
                if ($data) {
                    $this->syncFixtures([$data]); // Lưu chi tiết vào DB
                    
                    // Nếu trận đấu đã bắt đầu hoặc kết thúc, lấy thêm stats hiệp 1/2
                    $status = $data['fixture']['status']['short'] ?? '';
                    if (in_array($status, ['1H', 'HT', '2H', 'ET', 'P', 'FT', 'AET', 'PEN'])) {
                        $this->getFixtureStatistics($id, '1st');
                        if (in_array($status, ['2H', 'ET', 'P', 'FT', 'AET', 'PEN'])) {
                            $this->getFixtureStatistics($id, '2nd');
                        }
                    }

                    // Lấy thông tin chấn thương/vắng mặt
                    $this->getFixtureInjuries($id);

                    // Lấy thông tin đội hình (Lineups) chuyên biệt
                    $this->getFixtureLineups($id);
                }
                return $data;
            }

            Log::error('API Error (FixtureDetails): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('API Exception (FixtureDetails): ' . $e->getMessage());
            return null;
        }
    }

    public function getFixtureLineups($fixtureId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures/lineups', [
                    'fixture' => $fixtureId
                ]);

            if ($response->successful()) {
                $lineups = $response->json()['response'] ?? [];
                if (!empty($lineups)) {
                    FootballMatch::query()->where('id', $fixtureId)->update([
                        'lineups' => $lineups
                    ]);
                }
                return $lineups;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Lineups): ' . $e->getMessage());
            return [];
        }
    }

    public function getFixtureInjuries($fixtureId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures/injuries', [
                    'fixture' => $fixtureId
                ]);

            if ($response->successful()) {
                $injuries = $response->json()['response'] ?? [];
                
                // Cập nhật trực tiếp vào match
                $match = FootballMatch::query()->find($fixtureId);
                if ($match) {
                    $match->update(['injuries' => $injuries]);
                }
                
                return $injuries;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Injuries): ' . $e->getMessage());
            return [];
        }
    }

    public function getFixtureStatistics($fixtureId, $period = null)
    {
        try {
            $params = ['fixture' => $fixtureId];
            if ($period) $params['period'] = $period;

            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures/statistics', $params);

            if ($response->successful()) {
                $stats = $response->json()['response'] ?? [];
                if (!empty($stats)) {
                    $column = $period === '1st' ? 'stats_1h' : ($period === '2nd' ? 'stats_2h' : 'statistics');
                    FootballMatch::query()->where('id', $fixtureId)->update([
                        $column => $stats
                    ]);
                }
                return $stats;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (FixtureStatistics): ' . $e->getMessage());
            return [];
        }
    }

    public function getH2H($team1, $team2)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures/headtohead', [
                    'h2h' => "{$team1}-{$team2}",
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'] ?? [];
                if (!empty($data)) {
                    $this->syncFixtures($data);
                }
                return $data;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (H2H): ' . $e->getMessage());
            return [];
        }
    }

    public function getPredictions($fixtureId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'predictions', [
                    'fixture' => $fixtureId
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'][0] ?? null;
                if ($data) {
                    // Cập nhật predictions vào bảng matches
                    FootballMatch::query()->where('id', $fixtureId)->update([
                        'predictions' => $data
                    ]);
                }
                return $data;
            }

            Log::error('API Error (Predictions): ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('API Exception (Predictions): ' . $e->getMessage());
            return null;
        }
    }

    public function getFixturesByDate($date)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures', [
                    'date' => $date
                ]);

            if ($response->successful()) {
                $json = $response->json();
                
                // Kiểm tra lỗi nghiệp vụ từ API (ngay cả khi HTTP 200)
                if (!empty($json['errors'])) {
                    Log::error("API Business Error (Date $date): " . json_encode($json['errors']));
                    return [];
                }

                $data = $json['response'] ?? [];
                if (!empty($data)) {
                    $this->syncFixtures($data); // Lưu hàng loạt vào DB
                }
                return $data;
            }

            Log::error('API HTTP Error (Date): ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Date): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Nạp toàn bộ trận đấu của một giải đấu trong một mùa giải (Tiết kiệm Request nhất)
     */
    public function getFixturesByLeagueSeason($leagueId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures', [
                    'league' => $leagueId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'];
                $this->syncFixtures($data);
                return $data;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (LeagueSeason): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy toàn bộ trận đấu của một đội bóng trong một mùa giải
     */
    public function getFixturesByTeam($teamId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'fixtures', [
                    'team' => $teamId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'];
                $this->syncFixtures($data);
                return $data;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (FixturesByTeam): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy bảng xếp hạng của giải đấu
     */
    public function getStandings($leagueId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'standings', [
                    'league' => $leagueId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $json = $response->json();
                Log::info("API Standings Response for league={$leagueId} season={$season}: " . json_encode($json));
                $standings = $json['response'][0]['league']['standings'][0] ?? [];
                if (!empty($standings)) {
                    $this->syncStandings($standings, $leagueId, $season);
                }
                return $standings;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Standings): ' . $e->getMessage());
            return [];
        }
    }

    private function syncStandings(array $standings, $leagueId, $season)
    {
        foreach ($standings as $item) {
            // Đồng bộ Team trước
            $teamData = $item['team'];
            FootballTeam::updateOrCreate(
                ['id' => $teamData['id']],
                [
                    'name' => $teamData['name'],
                    'logo' => $teamData['logo'] ?? null,
                ]
            );

            FootballStanding::updateOrCreate(
                [
                    'league_id' => $leagueId,
                    'team_id' => $item['team']['id'],
                    'season' => $season,
                ],
                [
                    'rank' => $item['rank'],
                    'points' => $item['points'],
                    'played' => $item['all']['played'] ?? 0,
                    'win' => $item['all']['win'] ?? 0,
                    'draw' => $item['all']['draw'] ?? 0,
                    'lose' => $item['all']['lose'] ?? 0,
                    'goals_for' => $item['all']['goals']['for'] ?? 0,
                    'goals_against' => $item['all']['goals']['against'] ?? 0,
                    'form' => $item['form'] ?? null,
                    'group' => $item['group'] ?? null,
                    'description' => $item['description'] ?? null,
                ]
            );
        }
    }

    /**
     * Lấy danh sách vua phá lưới
     */
    public function getTopScorers($leagueId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players/topscorers', [
                    'league' => $leagueId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $scorers = $response->json()['response'] ?? [];
                if (!empty($scorers)) {
                    $this->syncTopScorers($scorers, $leagueId, $season);
                }
                return $scorers;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (TopScorers): ' . $e->getMessage());
            return [];
        }
    }

    public function getTopAssists($leagueId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players/topassists', [
                    'league' => $leagueId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $scorers = $response->json()['response'] ?? [];
                if (!empty($scorers)) {
                    $this->syncTopScorers($scorers, $leagueId, $season);
                }
                return $scorers;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (TopAssists): ' . $e->getMessage());
            return [];
        }
    }

    public function getTopYellowCards($leagueId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players/topyellowcards', [
                    'league' => $leagueId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $scorers = $response->json()['response'] ?? [];
                if (!empty($scorers)) {
                    $this->syncTopScorers($scorers, $leagueId, $season);
                }
                return $scorers;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (TopYellowCards): ' . $e->getMessage());
            return [];
        }
    }

    public function getTopRedCards($leagueId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players/topredcards', [
                    'league' => $leagueId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $scorers = $response->json()['response'] ?? [];
                if (!empty($scorers)) {
                    $this->syncTopScorers($scorers, $leagueId, $season);
                }
                return $scorers;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (TopRedCards): ' . $e->getMessage());
            return [];
        }
    }

    private function syncTopScorers(array $scorers, $leagueId, $season)
    {
        foreach ($scorers as $item) {
            $playerData = $item['player'];
            $stats = $item['statistics'][0];

            // 1. Đồng bộ vào bảng football_players (Hồ sơ cầu thủ)
            FootballPlayer::updateOrCreate(
                ['id' => $playerData['id']],
                [
                    'name' => $playerData['name'],
                    'nationality' => $playerData['nationality'] ?? null,
                    'position' => $stats['games']['position'] ?? null,
                    'birth_year' => isset($playerData['birth']['date']) ? date('Y', strtotime($playerData['birth']['date'])) : null,
                    'current_team_id' => $stats['team']['id'],
                    'photo' => $playerData['photo'] ?? null,
                ]
            );

            // 2. Đồng bộ vào bảng football_scorers (Bảng xếp hạng ghi bàn)
            FootballScorer::updateOrCreate(
                [
                    'player_id' => $playerData['id'],
                    'league_id' => $leagueId,
                    'season' => $season,
                ],
                [
                    'player_name' => $playerData['name'],
                    'team_id' => $stats['team']['id'],
                    'goals' => $stats['goals']['total'] ?? 0,
                    'assists' => $stats['goals']['assists'] ?? 0,
                    'yellow_cards' => $stats['cards']['yellow'] ?? 0,
                    'red_cards' => $stats['cards']['red'] ?? 0,
                    'photo' => $playerData['photo'] ?? null,
                ]
            );
        }
    }

    /**
     * Lấy thông tin chi tiết và thống kê mùa giải của cầu thủ
     */
    public function getPlayerStats($playerId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players', [
                    'id' => $playerId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'][0] ?? null;
                if ($data) {
                    $this->syncPlayerStats($data, $season);
                }
                return $data;
            }
            return null;
        } catch (\Exception $e) {
            Log::error('API Exception (PlayerStats): ' . $e->getMessage());
            return null;
        }
    }

    public function syncPlayerIfNotFound($playerId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players/seasons', [
                    'player' => $playerId
                ]);

            if ($response->successful()) {
                $seasons = $response->json()['response'] ?? [];
                if (!empty($seasons)) {
                    FootballPlayer::updateOrCreate(
                        ['id' => $playerId],
                        ['available_seasons' => $seasons]
                    );
                    $latestSeason = max($seasons);
                    $this->getPlayerStats($playerId, $latestSeason);
                }
            }
        } catch (\Exception $e) {
            Log::error('API Exception (SyncPlayer): ' . $e->getMessage());
        }
    }

    public function getPlayerTransfers($playerId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'transfers', [
                    'player' => $playerId
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'][0]['transfers'] ?? [];
                $player = FootballPlayer::query()->find($playerId);
                if ($player) {
                    $player->transfers = $data;
                    $player->save();
                }
                return $data;
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (PlayerTransfers): ' . $e->getMessage());
            return [];
        }
    }

    public function getPlayerTrophies($playerId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'trophies', [
                    'player' => $playerId
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'] ?? [];
                Log::info("Trophies for player {$playerId}: " . count($data) . " items found.");
                $player = FootballPlayer::query()->find($playerId);
                if ($player) {
                    $player->trophies = $data;
                    $player->save();
                }
                return $data;
            }
            Log::error("Trophy API failed for {$playerId}: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (PlayerTrophies): ' . $e->getMessage());
            return [];
        }
    }

    public function getPlayerSidelined($playerId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'sidelined', [
                    'player' => $playerId
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'] ?? [];
                Log::info("Sidelined history for player {$playerId}: " . count($data) . " items found.");
                $player = FootballPlayer::query()->find($playerId);
                if ($player) {
                    $player->sidelined_history = $data;
                    $player->save();
                }
                return $data;
            }
            Log::error("Sidelined API failed for {$playerId}: " . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (PlayerSidelined): ' . $e->getMessage());
            return [];
        }
    }

    private function syncPlayerStats(array $data, $season)
    {
        $p = $data['player'];
        
        // 1. Cập nhật Profile cầu thủ
        FootballPlayer::updateOrCreate(
            ['id' => $p['id']],
            [
                'name' => $p['name'],
                'firstname' => $p['firstname'] ?? null,
                'lastname' => $p['lastname'] ?? null,
                'nationality' => $p['nationality'] ?? null,
                'birth_year' => isset($p['birth']['date']) ? date('Y', strtotime($p['birth']['date'])) : null,
                'birth_date' => $p['birth']['date'] ?? null,
                'birth_place' => $p['birth']['place'] ?? null,
                'birth_country' => $p['birth']['country'] ?? null,
                'height' => $p['height'] ?? null,
                'weight' => $p['weight'] ?? null,
                'injured' => $p['injured'] ?? false,
                'number' => $data['statistics'][0]['games']['number'] ?? null, // Số áo nằm trong games.number
                'photo' => $p['photo'] ?? null,
                'position' => $data['statistics'][0]['games']['position'] ?? null,
            ]
        );

        // 2. Cập nhật Thống kê từng giải đấu
        foreach ($data['statistics'] as $stat) {
            $leagueId = $stat['league']['id'];
            $teamId = $stat['team']['id'];

            if ($leagueId) {
                FootballLeague::updateOrCreate(
                    ['id' => $leagueId],
                    [
                        'name' => $stat['league']['name'] ?? 'Unknown',
                        'logo' => $stat['league']['logo'] ?? null,
                        'country_name' => $stat['league']['country'] ?? null,
                    ]
                );
            }

            if ($teamId) {
                FootballTeam::updateOrCreate(
                    ['id' => $teamId],
                    [
                        'name' => $stat['team']['name'] ?? 'Unknown',
                        'logo' => $stat['team']['logo'] ?? null,
                    ]
                );
            }

            if ($leagueId && $teamId) {
                PlayerSeasonStat::updateOrCreate(
                    [
                        'player_id' => $p['id'],
                        'league_id' => $leagueId,
                        'season' => $season, 
                    ],
                    [
                        'team_id' => $teamId,
                        'games' => $stat['games']['appearences'] ?? 0,
                    'games_starts' => $stat['games']['lineups'] ?? 0,
                    'minutes' => $stat['games']['minutes'] ?? 0,
                    'goals' => $stat['goals']['total'] ?? 0,
                    'assists' => $stat['goals']['assists'] ?? 0,
                    'cards_yellow' => $stat['cards']['yellow'] ?? 0,
                    'cards_red' => $stat['cards']['red'] ?? 0,
                    'detailed_stats' => $stat, // Lưu toàn bộ JSON để trích xuất chỉ số nâng cao
                ]
            );
            }
        }
    }

    public function getSquad($teamId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players/squads', [
                    'team' => $teamId
                ]);

            if ($response->successful()) {
                $data = $response->json()['response'][0]['players'] ?? [];
                if (!empty($data)) {
                    $this->syncSquad($data, $teamId);
                }
                return $data;
            }

            Log::error('API Error (Squad): ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Squad): ' . $e->getMessage());
            return [];
        }
    }

    private function syncSquad(array $players, $teamId)
    {
        foreach ($players as $p) {
            FootballPlayer::updateOrCreate(
                ['id' => $p['id']],
                [
                    'name' => $p['name'],
                    'number' => $p['number'] ?? null,
                    'position' => $p['position'] ?? null,
                    'photo' => $p['photo'] ?? null,
                    'current_team_id' => $teamId
                ]
            );
        }
    }

    /**
     * Lấy toàn bộ cầu thủ và thống kê của một đội bóng trong một mùa giải
     */
    public function getPlayersByTeam($teamId, $season)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'players', [
                    'team' => $teamId,
                    'season' => $season
                ]);

            if ($response->successful()) {
                $results = $response->json();
                $data = $results['response'] ?? [];
                $totalPages = $results['paging']['total'] ?? 1;

                // Sync trang đầu
                $this->syncPlayersByTeam($data, $season);

                // Nếu có nhiều trang, lấy hết (API Sports trả về tối đa 20/trang)
                for ($page = 2; $page <= $totalPages; $page++) {
                    $res = Http::withHeaders($this->getHeaders())
                        ->withoutVerifying()
                        ->get($this->getBaseUrl() . 'players', [
                            'team' => $teamId,
                            'season' => $season,
                            'page' => $page
                        ]);
                    if ($res->successful()) {
                        $this->syncPlayersByTeam($res->json()['response'] ?? [], $season);
                    }
                }
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('API Exception (PlayersByTeam): ' . $e->getMessage());
            return false;
        }
    }

    private function syncPlayersByTeam(array $players, $season)
    {
        foreach ($players as $item) {
            $this->syncPlayerStats($item, $season);
        }
    }

    public function getOdds($fixtureId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'odds', [
                    'fixture' => $fixtureId
                ]);

            if ($response->successful()) {
                return $response->json()['response'][0]['bookmakers'] ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Odds): ' . $e->getMessage());
            return [];
        }
    }

    public function getCoaches($teamId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withoutVerifying()
                ->get($this->getBaseUrl() . 'coachs', [
                    'team' => $teamId
                ]);

            if ($response->successful()) {
                return $response->json()['response'] ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('API Exception (Coaches): ' . $e->getMessage());
            return [];
        }
    }
}