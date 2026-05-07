<?php

namespace App\Http\Controllers;

use App\Services\WorldCupService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorldCupController extends Controller
{
    protected $wcService;

    public function __construct(WorldCupService $wcService)
    {
        $this->wcService = $wcService;
    }

    public function index()
    {
        // Lấy toàn bộ dữ liệu cần thiết cho trang tổng quan
        $data = [
            'teams' => $this->wcService->getTeams(),
            'groups' => $this->wcService->getGroups(),
            'matches' => $this->wcService->getMatches(),
            'stadiums' => $this->wcService->getStadiums(),
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
     * API lấy danh sách trận đấu theo bộ lọc (Ajax/Inertia Partial)
     */
    public function getMatches(Request $request)
    {
        $params = $request->only(['group', 'round', 'status', 'team']);
        return response()->json($this->wcService->getMatches($params));
    }
}
