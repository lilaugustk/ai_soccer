<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Player;
use App\Models\League;
use Illuminate\Http\Request;

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

        \Log::info("Search query: " . $query);
        $teams = Team::where('name', 'LIKE', "%{$query}%")
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
