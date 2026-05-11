<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Notifications\GeneralNotification;
use App\Models\FootballTeam;


class FavoriteTeamController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @param mixed $id
     */
    public function toggle(Request $request, $id)
    {
        $user = $request->user();
        $user->favoriteTeams()->toggle($id);

        $team = FootballTeam::query()->where('id', $id)->first();

        if ($user->favoriteTeams()->where('team_id', $id)->exists()) {
            $user->notify(new GeneralNotification("Bạn đã thêm đội bóng {$team->name} vào danh sách yêu thích!"));
        }

        return back();
    }
}
