<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Notifications\GeneralNotification;
use App\Models\Team;

class FavoriteTeamController extends Controller
{
    public function toggle(Request $request, $teamId)
    {
        $user = $request->user();
        $user->favoriteTeams()->toggle($teamId);

        // Send notification
        $team = Team::find($teamId);
        if ($user->favoriteTeams()->where('team_id', $teamId)->exists()) {
            $user->notify(new GeneralNotification("Bạn đã thêm đội bóng {$team->name} vào danh sách yêu thích!"));
        }

        return back();
    }
}
