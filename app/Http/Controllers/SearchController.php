<?php

namespace App\Http\Controllers;

use App\Models\FootballTeam;
use App\Models\FootballPlayer;
use App\Models\FootballLeague;


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
        $teams = FootballTeam::query()->where('name', 'LIKE', "%{$query}%")
            ->orWhere('short_name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        $players = FootballPlayer::query()->where('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();

        $leagues = FootballLeague::query()->where('name', 'LIKE', "%{$query}%")
            ->limit(5)
            ->get();


        return response()->json([
            'teams' => $teams,
            'players' => $players,
            'leagues' => $leagues
        ]);
    }
}
