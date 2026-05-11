<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballApiService;
use App\Models\FootballLeague;

class SyncFootballLeague extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'football:sync {league} {season}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Sync all data for a specific league and season (Matches, Standings, Scorers)';

    /**
     * Execute the console command.
     */
    public function handle(FootballApiService $apiService)
    {
        $leagueId = $this->argument('league');
        $season = $this->argument('season');

        $this->info("=== Bắt đầu đồng bộ Giải: {$leagueId} | Mùa: {$season} ===");

        // 1. Đồng bộ toàn bộ Trận đấu (Tiết kiệm Request nhất - 1 call)
        $this->info("1. Đang lấy danh sách trận đấu...");
        $fixtures = $apiService->getFixturesByLeagueSeason($leagueId, $season);
        $this->info("   -> Đã đồng bộ " . count($fixtures) . " trận đấu.");

        // 2. Đồng bộ Bảng xếp hạng (1 call)
        $this->info("2. Đang cập nhật bảng xếp hạng...");
        $standings = $apiService->getStandings($leagueId, $season);
        $this->info("   -> Đã cập nhật bảng xếp hạng.");

        // 3. Đồng bộ Vua phá lưới (1 call)
        $this->info("3. Đang cập nhật danh sách ghi bàn...");
        $apiService->getTopScorers($leagueId, $season);
        $this->info("   -> Đã cập nhật danh sách ghi bàn.");

        // 4. Đồng bộ Vua kiến tạo (1 call)
        $this->info("4. Đang cập nhật danh sách kiến tạo...");
        $apiService->getTopAssists($leagueId, $season);
        $this->info("   -> Đã cập nhật danh sách kiến tạo.");

        $this->success("=== Hoàn tất đồng bộ dữ liệu mùa giải! ===");
        
        return 0;
    }

    private function success($message)
    {
        $this->line("<fg=green;options=bold>{$message}</>");
    }
}
