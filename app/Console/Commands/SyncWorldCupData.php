<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WorldCupService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncWorldCupData extends Command
{
    protected $signature = 'wc:sync';
    protected $description = 'Sync World Cup 2026 data from API to database';

    protected function getFlagUrl($code, $fallback = null)
    {
        if ($fallback) return $fallback;
        
        $mapping = [
            'AFG' => 'af', 'ALB' => 'al', 'DZA' => 'dz', 'AND' => 'ad', 'AGO' => 'ao', 'ATG' => 'ag', 'ARG' => 'ar', 'ARM' => 'am', 'AUS' => 'au', 'AUT' => 'at', 'AZE' => 'az',
            'BHS' => 'bs', 'BHR' => 'bh', 'BGD' => 'bd', 'BRB' => 'bb', 'BLR' => 'by', 'BEL' => 'be', 'BLZ' => 'bz', 'BEN' => 'bj', 'BTN' => 'bt', 'BOL' => 'bo', 'BIH' => 'ba', 'BWA' => 'bw', 'BRA' => 'br', 'BRN' => 'bn', 'BGR' => 'bg', 'BFA' => 'bf', 'BDI' => 'bi',
            'CPV' => 'cv', 'KHM' => 'kh', 'CMR' => 'cm', 'CAN' => 'ca', 'CAF' => 'cf', 'TCD' => 'td', 'CHL' => 'cl', 'CHN' => 'cn', 'COL' => 'co', 'COM' => 'km', 'COG' => 'cg', 'COD' => 'cd', 'COK' => 'ck', 'CRI' => 'cr', 'CIV' => 'ci', 'HRV' => 'hr', 'CRO' => 'hr', 'CUB' => 'cu', 'CYP' => 'cy', 'CZE' => 'cz',
            'DNK' => 'dk', 'DJI' => 'dj', 'DMA' => 'dm', 'DOM' => 'do', 'ECU' => 'ec', 'EGY' => 'eg', 'SLV' => 'sv', 'GNQ' => 'gq', 'ERI' => 'er', 'EST' => 'ee', 'ETH' => 'et',
            'FJI' => 'fj', 'FIN' => 'fi', 'FRA' => 'fr', 'GAB' => 'ga', 'GMB' => 'gm', 'GEO' => 'ge', 'DEU' => 'de', 'GER' => 'de', 'GHA' => 'gh', 'GRC' => 'gr', 'GRD' => 'gd', 'GTM' => 'gt', 'GIN' => 'gn', 'GNB' => 'gw', 'GUY' => 'gy',
            'HTI' => 'ht', 'HAI' => 'ht', 'HND' => 'hn', 'HUN' => 'hu', 'ISL' => 'is', 'IND' => 'in', 'IDN' => 'id', 'IRN' => 'ir', 'IRQ' => 'iq', 'IRL' => 'ie', 'ISR' => 'il', 'ITA' => 'it',
            'JAM' => 'jm', 'JPN' => 'jp', 'JOR' => 'jo', 'KAZ' => 'kz', 'KEN' => 'ke', 'KIR' => 'ki', 'PRK' => 'kp', 'KOR' => 'kr', 'KWT' => 'kw', 'KGZ' => 'kg', 'LAO' => 'la', 'LVA' => 'lv', 'LBN' => 'lb', 'LSO' => 'ls', 'LBR' => 'lr', 'LBY' => 'ly', 'LIE' => 'li', 'LTU' => 'lt', 'LUX' => 'lu',
            'MKD' => 'mk', 'MDG' => 'mg', 'MWI' => 'mw', 'MYS' => 'my', 'MDV' => 'mv', 'MLI' => 'ml', 'MLT' => 'mt', 'MHL' => 'mh', 'MRT' => 'mr', 'MUS' => 'mu', 'MEX' => 'mx', 'FSM' => 'fm', 'MDA' => 'md', 'MCO' => 'mc', 'MNG' => 'mn', 'MNE' => 'me', 'MAR' => 'ma', 'MOZ' => 'mz', 'MMR' => 'mm',
            'NAM' => 'na', 'NRU' => 'nr', 'NPL' => 'np', 'NLD' => 'nl', 'NED' => 'nl', 'NZL' => 'nz', 'NIC' => 'ni', 'NER' => 'ne', 'NGA' => 'ng', 'NIU' => 'nu', 'NOR' => 'no',
            'OMN' => 'om', 'PAK' => 'pk', 'PLW' => 'pw', 'PAN' => 'pa', 'PNG' => 'pg', 'PRY' => 'py', 'PAR' => 'py', 'PER' => 'pe', 'PHL' => 'ph', 'POL' => 'pl', 'PRT' => 'pt', 'POR' => 'pt',
            'QAT' => 'qa', 'ROU' => 'ro', 'RUS' => 'ru', 'RWA' => 'rw', 'KNA' => 'kn', 'LCA' => 'lc', 'VCG' => 'vc', 'WSM' => 'ws', 'SMR' => 'sm', 'STP' => 'st', 'SAU' => 'sa', 'KSA' => 'sa', 'SEN' => 'sn', 'SRB' => 'rs', 'SYC' => 'sc', 'SLE' => 'sl', 'SGP' => 'sg', 'SVK' => 'sk', 'SVN' => 'si', 'SLB' => 'sb', 'SOM' => 'so', 'ZAF' => 'za', 'RSA' => 'za', 'ESP' => 'es', 'LKA' => 'lk', 'SDN' => 'sd', 'SUR' => 'sr', 'SWZ' => 'sz', 'SWE' => 'se', 'CHE' => 'ch', 'SUI' => 'ch', 'SYR' => 'sy',
            'TWN' => 'tw', 'TJK' => 'tj', 'TZA' => 'tz', 'THA' => 'th', 'TLS' => 'tl', 'TGO' => 'tg', 'TON' => 'to', 'TTO' => 'tt', 'TUN' => 'tn', 'TUR' => 'tr', 'TKM' => 'tm', 'TUV' => 'tv',
            'UGA' => 'ug', 'UKR' => 'ua', 'ARE' => 'ae', 'GBR' => 'gb', 'USA' => 'us', 'URY' => 'uy', 'URU' => 'uy', 'UZB' => 'uz', 'VUT' => 'vu', 'VEN' => 've', 'VNM' => 'vn', 'YEM' => 'ye', 'ZMB' => 'zm', 'ZWE' => 'zw',
            'ENG' => 'gb-eng', 'WAL' => 'gb-wls', 'SCO' => 'gb-sct', 'NIR' => 'gb-nir', 'CUR' => 'cw', 'CUW' => 'cw', 'ALG' => 'dz', 'DZA' => 'dz',
        ];

        $code = strtoupper($code);
        $iso2 = $mapping[$code] ?? strtolower($code);
        return "https://flagcdn.com/{$iso2}.svg";
    }

    public function handle(WorldCupService $service)
    {
        $this->info('Starting World Cup 2026 data sync...');

        // 1. Sync Matches (and extract Teams/Stadiums if needed)
        $matches = $service->getMatches();
        
        if (empty($matches)) {
            $this->error('No matches found from API.');
            return 1;
        }

        $this->info('Syncing ' . count($matches) . ' matches...');

        foreach ($matches as $match) {
            // Ensure home team exists
            if (isset($match['home_team_id'])) {
                $homeFlag = $this->getFlagUrl($match['home_team_code'] ?? '', $match['home_team_flag']);
                DB::table('wc2026_teams')->updateOrInsert(
                    ['id' => $match['home_team_id']],
                    [
                        'name' => $match['home_team'],
                        'code' => $match['home_team_code'] ?? null,
                        'flag' => $homeFlag,
                        'group_name' => $match['group_name'] ?? null,
                        'updated_at' => now(),
                    ]
                );
            }

            // Ensure away team exists
            if (isset($match['away_team_id'])) {
                $awayFlag = $this->getFlagUrl($match['away_team_code'] ?? '', $match['away_team_flag']);
                DB::table('wc2026_teams')->updateOrInsert(
                    ['id' => $match['away_team_id']],
                    [
                        'name' => $match['away_team'],
                        'code' => $match['away_team_code'] ?? null,
                        'flag' => $awayFlag,
                        'group_name' => $match['group_name'] ?? null,
                        'updated_at' => now(),
                    ]
                );
            }

            // Sync Match
            DB::table('wc2026_matches')->updateOrInsert(
                ['id' => $match['id']],
                [
                    'match_number' => $match['match_number'] ?? null,
                    'round' => $match['round'] ?? null,
                    'group_name' => $match['group_name'] ?? null,
                    'home_team_id' => $match['home_team_id'] ?? null,
                    'away_team_id' => $match['away_team_id'] ?? null,
                    'home_team_name' => $match['home_team'] ?? null,
                    'away_team_name' => $match['away_team'] ?? null,
                    'home_score' => $match['home_score'] ?? null,
                    'away_score' => $match['away_score'] ?? null,
                    'stadium_id' => $match['stadium_id'] ?? null,
                    'kickoff_at' => isset($match['kickoff_utc']) ? Carbon::parse($match['kickoff_utc']) : null,
                    'status' => $match['status'] ?? 'scheduled',
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Sync Standings
        $this->info('Syncing standings...');
        $standings = $service->getStandings();
        foreach ($standings as $standing) {
            DB::table('wc2026_standings')->updateOrInsert(
                ['group_letter' => $standing['group'], 'team_name' => $standing['team']],
                [
                    'team_flag' => $this->getFlagUrl($standing['team_code'] ?? '', $standing['flag'] ?? null),
                    'played' => $standing['played'] ?? 0,
                    'won' => $standing['won'] ?? 0,
                    'drawn' => $standing['drawn'] ?? 0,
                    'lost' => $standing['lost'] ?? 0,
                    'gf' => $standing['gf'] ?? 0,
                    'ga' => $standing['ga'] ?? 0,
                    'gd' => $standing['gd'] ?? 0,
                    'pts' => $standing['pts'] ?? 0,
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('World Cup 2026 data sync completed successfully!');
        return 0;
    }
}
