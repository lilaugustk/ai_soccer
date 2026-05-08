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
        // 1. Fetch Teams and group them by group_name
        $teams = DB::table('wc2026_teams')->get();
        $groups = [];
        foreach ($teams as $team) {
            $groups[$team->group_name][] = [
                'name' => $team->name,
                'flag' => $team->flag,
                'code' => $team->code
            ];
        }

        // 2. Fetch Stadiums
        $stadiums = DB::table('wc2026_stadiums')->get()->keyBy('id');

        // 3. Fetch Matches and format for frontend
        $rawMatches = DB::table('wc2026_matches')->get();
        $matches = $rawMatches->map(function ($m) use ($stadiums) {
            $stadium = $stadiums->get($m->stadium_id);
            return [
                'id' => $m->id,
                'home' => $m->home_team_name,
                'homeFlag' => DB::table('wc2026_teams')->where('id', $m->home_team_id)->value('flag'),
                'away' => $m->away_team_name,
                'awayFlag' => DB::table('wc2026_teams')->where('id', $m->away_team_id)->value('flag'),
                'date' => $m->kickoff_at ? Carbon::parse($m->kickoff_at)->format('d/m') : 'TBD',
                'time' => $m->kickoff_at ? Carbon::parse($m->kickoff_at)->format('H:i') : '00:00',
                'stadium' => $stadium ? $stadium->name : 'TBD',
                'group' => $m->group_name,
                'round' => $m->round,
                'status' => $m->status
            ];
        });

        $data = [
            'teams' => $teams,
            'groups' => $groups,
            'matches' => $matches,
            'stadiums' => $stadiums->values(),
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
