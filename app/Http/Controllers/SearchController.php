<?php

namespace App\Http\Controllers;

use App\Models\FootballTeam;
use App\Models\Player;
use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query || strlen($query) < 2) {
            return response()->json([
                'teams' => [],
                'players' => [],
                'leagues' => []
            ]);
        }

        Log::info("Search query: " . $query);
        $teams = FootballTeam::where('name', 'LIKE', "%{$query}%")
            ->orWhere('code', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        $players = Player::where('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        $leagues = League::where('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        return response()->json([
            'teams' => $teams,
            'players' => $players,
            'leagues' => $leagues
        ]);
    }
}
