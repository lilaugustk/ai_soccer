<?php

namespace App\Http\Controllers;

use App\Services\WorldCupService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorldCupController extends Controller
{
    protected $wcService;

    public function __construct(WorldCupService $wcService)
    {
        $this->wcService = $wcService;
    }

    public function index()
    {
        // 1. Fetch Standings and group them by group_letter
        $standings = DB::table('wc2026_standings')->orderBy('group_letter')->orderByDesc('pts')->orderByDesc('gd')->get();
        $groups = [];
        foreach ($standings as $standing) {
            $groups[$standing->group_letter][] = [
                'name' => $standing->team_name,
                'flag' => $standing->team_flag,
                'played' => $standing->played,
                'won' => $standing->won,
                'drawn' => $standing->drawn,
                'lost' => $standing->lost,
                'gf' => $standing->gf,
                'ga' => $standing->ga,
                'gd' => $standing->gd,
                'pts' => $standing->pts,
            ];
        }

        // 2. Fetch Matches with joins to get flags and stadium names efficiently
        $matches = DB::table('wc2026_matches')
            ->leftJoin('wc2026_teams as home_teams', 'wc2026_matches.home_team_id', '=', 'home_teams.id')
            ->leftJoin('wc2026_teams as away_teams', 'wc2026_matches.away_team_id', '=', 'away_teams.id')
            ->leftJoin('wc2026_stadiums', 'wc2026_matches.stadium_id', '=', 'wc2026_stadiums.id')
            ->select(
                'wc2026_matches.*',
                'home_teams.flag as home_flag',
                'home_teams.code as home_team_code',
                'away_teams.flag as away_flag',
                'away_teams.code as away_team_code',
                'wc2026_stadiums.name as stadium_name'
            )
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'home' => $m->home_team_name,
                    'homeFlag' => $m->home_flag,
                    'home_code' => $m->home_team_code,
                    'away' => $m->away_team_name,
                    'awayFlag' => $m->away_flag,
                    'away_code' => $m->away_team_code,
                    'date' => $m->kickoff_at ? Carbon::parse($m->kickoff_at)->setTimezone('Asia/Ho_Chi_Minh')->format('d/m') : 'TBD',
                    'time' => $m->kickoff_at ? Carbon::parse($m->kickoff_at)->setTimezone('Asia/Ho_Chi_Minh')->format('H:i') : '00:00',
                    'kickoff_utc' => $m->kickoff_at ? Carbon::parse($m->kickoff_at)->setTimezone('Asia/Ho_Chi_Minh')->toIso8601String() : null,
                    'stadium' => $m->stadium_name ?: 'TBD',
                    'stadium_id' => $m->stadium_id,
                    'group_name' => $m->group_name ? str_replace('Group ', 'Bảng ', $m->group_name) : null,
                    'round' => $m->round,
                    'status' => $m->status
                ];
            });

        $data = [
            'teams' => DB::table('wc2026_teams')->get(),
            'groups' => $groups,
            'matches' => $matches,
            'stadiums' => DB::table('wc2026_stadiums')->get(),
            'liveTest' => $this->wcService->getLiveSandbox(),
        ];

        return Inertia::render('WorldCup', $data);
    }

    /**
     * API dành cho việc cập nhật Real-time Sandbox Match
     */
    public function getLiveMatch()
    {
        return response()->json($this->wcService->getLiveSandbox());
    }

    /**
     * API lấy danh sách trận đấu từ Database
     */
    public function getMatches(Request $request)
    {
        $matches = DB::table('wc2026_matches')->get();
        return response()->json($matches);
    }
}
