<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Team;
use App\Models\Game;
use App\Models\League;
use App\Models\Season;
use App\Models\Player;
use App\Models\PlayerMatchStat;
use App\Models\PlayerSeasonStat;
use App\Models\TeamSeasonStat;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ImportSoccerData extends Command
{
    protected $signature = 'soccer:import 
                            {--all : Import everything in order}
                            {--leagues : Import league data}
                            {--teams : Import team/club data}
                            {--nations : Import master player data}
                            {--matches : Import match data and details}
                            {--squad-stats : Import team season metrics}
                            {--players : Import player season stats}
                            {--year=2026 : The year to import}
                            {--fresh-year : Delete existing matches for the year}';

    protected $description = 'One-shot absolute ID-based soccer data ingestion pipeline';

    protected $baseRawPath;
    protected $leaguesCollection;
    protected $seasonsCollection;
    protected $missingLeagues = [];

    public function __construct()
    {
        parent::__construct();
        $this->baseRawPath = base_path('Crawl_SoccerData/data/raw');
    }

    public function handle()
    {
        $all = $this->option('all');
        $year = $this->option('year');

        $this->info("=== SOCCER DATA PIPELINE STARTING (ID-ABSOLUTE LOGIC) ===");
        
        // 1. Foundation: Leagues & Seasons
        if ($all || $this->option('leagues')) {
            $this->importLeagues();
        }

        // 2. Master Data: Teams & Players (The "Móng")
        if ($all || $this->option('teams')) {
            $this->importTeams();
        }

        if ($all || $this->option('nations')) {
            $this->importPlayersFromNations();
        }

        // 3. Operational: Matches
        if ($all || $this->option('matches')) {
            if ($this->option('fresh-year')) {
                $this->warn("Truncating matches for year $year...");
                Game::whereYear('match_datetime', $year)->delete();
            }
            $this->importMatches($year);
        }

        // 4. Statistics
        if ($all || $this->option('squad-stats')) {
            $this->importSquadStats();
        }

        if ($all || $this->option('players')) {
            $this->importPlayers();
        }

        if (!empty($this->missingLeagues)) {
            $this->warn("\n--- MISSING LEAGUES REPORT ---");
            foreach ($this->missingLeagues as $id => $name) {
                $this->line("- ID: {$id} | Name: {$name}");
            }
        }

        $this->info("\n=== SOCCER DATA PIPELINE COMPLETED! ===");
        return 0;
    }

    protected function importLeagues()
    {
        $this->info('--- 1/6 Importing Leagues ---');
        $path = $this->baseRawPath . '/fbref_all_leagues.csv';
        if (!File::exists($path)) return;

        $data = $this->parseCsv($path);
        $bar = $this->output->createProgressBar(count($data));
        foreach ($data as $row) {
            League::updateOrCreate(
                ['source_id' => $row['source_id']],
                [
                    'name' => $row['name'],
                    'country' => $row['country'],
                    'country_code' => $row['country_code'],
                    'gender' => $row['gender'] ?? 'M',
                    'logo_url' => $row['logo_url'] ?? null,
                ]
            );
            $bar->advance();
        }
        $bar->finish();
        $this->leaguesCollection = League::all();
        $this->info("");
    }

    protected function importTeams()
    {
        $this->info('--- 2/6 Importing Team Master Data ---');
        $path = $this->baseRawPath . '/fbref_all_clubs_complete.csv';
        if (!File::exists($path)) return;

        $data = $this->parseCsv($path);
        $bar = $this->output->createProgressBar(count($data));
        foreach ($data as $row) {
            if (empty($row['clubid'])) continue;
            Team::updateOrCreate(
                ['id' => $row['clubid']],
                [
                    'name' => $row['clubname'],
                    'logo_url' => $row['logourl'] ?? null,
                    'country' => $row['country'] ?? null,
                    'gender' => $row['gender'] ?? 'M',
                ]
            );
            $bar->advance();
        }
        $bar->finish();
        $this->info("");
    }

    protected function importPlayersFromNations()
    {
        $this->info('--- 3/6 Importing Master Players (Nations) ---');
        $basePath = $this->baseRawPath . '/players/nations';
        if (!File::exists($basePath)) return;

        $jsonFiles = array_filter(File::allFiles($basePath), fn($f) => $f->getFilename() === 'players.json');
        $bar = $this->output->createProgressBar(count($jsonFiles));
        
        // Pre-load teams for faster club mapping
        $teamsCache = Team::all()->mapWithKeys(fn($t) => [strtolower($t->name) => $t->id])->toArray();

        foreach ($jsonFiles as $file) {
            $players = json_decode(File::get($file->getPathname()), true);
            if (!$players) continue;

            // Extract nationality from Folder Name (e.g., ".../nations/Vietnam/players.json" -> Vietnam)
            $nationality = basename($file->getPath());

            foreach ($players as $p) {
                if (empty($p['player_id'])) continue;

                $birthYear = null;
                if (!empty($p['born']) && preg_match('/(\d{4})/', $p['born'], $matches)) {
                    $birthYear = (int)$matches[1];
                }

                $clubName = strtolower(trim($p['current_club'] ?? ''));
                $teamId = $teamsCache[$clubName] ?? null;

                Player::updateOrCreate(
                    ['id' => $p['player_id']],
                    [
                        'name' => $p['name'],
                        'nationality' => $nationality,
                        'position' => $p['position'] ?? null,
                        'birth_year' => $birthYear,
                        'current_team_id' => $teamId,
                    ]
                );
            }
            $bar->advance();
        }
        $bar->finish();
        $this->info("");
    }

    protected function importMatches($year)
    {
        $this->info("--- 4/6 Importing matches & Stats for $year ---");
        $csvPath = $this->baseRawPath . "/fbref_all_matches/fbref_all_matches_$year.csv";
        $jsonlPath = $this->baseRawPath . "/fbref_match_details/details_$year.jsonl";
        if (!File::exists($csvPath)) return;

        $csvData = $this->parseCsv($csvPath);
        $csvMeta = [];
        foreach ($csvData as $row) {
            if (!empty($row['matchreporturl']) && preg_match('/\/matches\/([a-z0-9]+)\//', $row['matchreporturl'], $m)) {
                $csvMeta[$m[1]] = $row;
            }
        }

        $this->leaguesCollection = League::all();
        $this->seasonsCollection = Season::all();

        if (File::exists($jsonlPath)) {
            $handle = fopen($jsonlPath, 'r');
            while (($line = fgets($handle)) !== false) {
                $stats = json_decode($line, true);
                if ($stats && isset($stats['match_id'])) {
                    $this->processSingleMatch($stats['match_id'], $stats, $csvMeta[$stats['match_id']] ?? null);
                    $this->output->write('.');
                }
            }
            fclose($handle);
        }

        // Process remaining scheduled matches from CSV
        $this->info("\nProcessing remaining scheduled matches...");
        foreach ($csvMeta as $mid => $meta) {
            if (!Game::where('id', $mid)->exists()) {
                $this->processSingleMatch($mid, null, $meta);
            }
        }
    }

    protected function processSingleMatch($matchId, $stats, $meta)
    {
        DB::beginTransaction();
        try {
            $leagueId = null; $seasonId = null;
            if ($meta) {
                $league = $this->findLeague($meta['league_id'] ?? null, $meta['league'] ?? null);
                if (!$league) { DB::rollBack(); return; }
                
                $leagueId = $league->id;
                $date = $meta['matchdate'] ?? ($meta['date'] ?? null);
                $year = $date ? Carbon::parse($date)->year : date('Y');
                
                $season = Season::firstOrCreate(
                    ['league_id' => $leagueId, 'fbref_id' => (string)$year],
                    ['name' => (string)$year, 'is_current' => $year >= 2025]
                );
                $seasonId = $season->id;

                $homeId = $meta['home_id'] ?? ($stats['home_team_id'] ?? null);
                $awayId = $meta['away_id'] ?? ($stats['away_team_id'] ?? null);

                // Ensure Teams
                if ($homeId) Team::firstOrCreate(['id' => $homeId], ['name' => $meta['home_team'] ?? 'Unknown']);
                if ($awayId) Team::firstOrCreate(['id' => $awayId], ['name' => $meta['away_team'] ?? 'Unknown']);

                if (!$homeId || !$awayId) { DB::rollBack(); return; }

                // 3. Score & Status
                $hScore = isset($meta['home_score']) && $meta['home_score'] !== '' ? (int)$meta['home_score'] : ($stats['home_score'] ?? null);
                $aScore = isset($meta['away_score']) && $meta['away_score'] !== '' ? (int)$meta['away_score'] : ($stats['away_score'] ?? null);
                $time = (isset($meta['time']) && preg_match('/^\d{1,2}:\d{2}/', $meta['time'])) ? explode(' ', $meta['time'])[0] : '00:00';

                // Robust Round detection
                $round = $meta['wk'] ?? ($meta['round'] ?? ($meta['gameweek'] ?? ($stats['round'] ?? null)));

                Game::updateOrCreate(['id' => $matchId], [
                    'league_id' => $leagueId, 'season_id' => $seasonId,
                    'home_team_id' => $homeId, 'away_team_id' => $awayId,
                    'match_datetime' => "$date $time",
                    'home_score' => $hScore, 'away_score' => $aScore,
                    'round' => $round,
                    'status' => $hScore !== null ? 'finished' : 'scheduled',
                    'venue' => $meta['venue'] ?? null,
                    'match_events_json' => $stats['match_events'] ?? null,
                    'team_stats' => $stats['team_stats'] ?? null,
                ]);

                // Sync Players
                if (!empty($stats['player_stats'])) {
                    foreach ($stats['player_stats'] as $ps) {
                        if (empty($ps['player_id'])) continue;
                        $p = Player::find($ps['player_id']);
                        $name = (!empty($ps['player']) && $ps['player'] !== 'Unknown Player') ? $ps['player'] : ($p->name ?? 'Unknown');

                        Player::updateOrCreate(['id' => $ps['player_id']], [
                            'name' => $name, 'nationality' => $ps['nation'] ?? ($p->nationality ?? null)
                        ]);

                        $age = $ps['age'] ?? null;
                        if ($age && str_contains($age, '-')) $age = explode('-', $age)[0];

                        PlayerMatchStat::updateOrCreate(
                            ['match_id' => $matchId, 'player_id' => $ps['player_id']],
                            [
                                'team_id' => $ps['team_id'] ?? null, 'position' => $ps['position'] ?? null,
                                'minutes' => (int)($ps['minutes'] ?? 0), 'detailed_stats' => $ps, 'age' => $age
                            ]
                        );
                    }
                }
                DB::commit();
            } else { DB::rollBack(); }
        } catch (\Exception $e) { DB::rollBack(); }
    }

    protected function importSquadStats()
    {
        $this->info('--- 5/6 Importing Squad Stats ---');
        $basePath = $this->baseRawPath . '/squad_stats';
        if (!File::exists($basePath)) return;

        foreach (File::files($basePath) as $file) {
            $league = $this->findLeague(null, str_replace('_', ' ', $file->getFilenameWithoutExtension()));
            if (!$league) continue;

            $data = json_decode(File::get($file->getPathname()), true);
            foreach ($data as $sData) {
                $season = Season::firstOrCreate(['league_id' => $league->id, 'fbref_id' => $sData['season'] ?? '2026']);
                foreach ($sData['stats']['overall'] ?? [] as $t) {
                    $teamId = $t['squad_id'] ?? null;
                    if (!$teamId) continue;

                    TeamSeasonStat::updateOrCreate(
                        ['team_id' => $teamId, 'season_id' => $season->id, 'league_id' => $league->id],
                        ['points' => $t['points'] ?? 0, 'wins' => $t['wins'] ?? 0, 'detailed_stats' => $t]
                    );
                }
            }
        }
    }

    protected function importPlayers() { $this->info('--- 6/6 Player Season Stats (Skipped - Covered by Matches) ---'); }

    protected function findLeague($id, $name)
    {
        if ($id) return $this->leaguesCollection->firstWhere('source_id', $id);
        if ($name) {
            $clean = strtolower(trim(preg_replace('/^([a-z]{2,3})\s+/i', '', $name)));
            return $this->leaguesCollection->first(fn($l) => str_contains(strtolower($l->name), $clean) || str_contains($clean, strtolower($l->name)));
        }
        return null;
    }

    protected function parseCsv($path)
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $header = array_map(fn($h) => strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h))), fgetcsv($handle));
            while (($data = fgetcsv($handle)) !== false) {
                if (count($header) == count($data)) $rows[] = array_combine($header, $data);
            }
            fclose($handle);
        }
        return $rows;
    }
}