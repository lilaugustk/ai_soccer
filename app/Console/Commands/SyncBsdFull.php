<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BsdSportsApiService;
use App\Models\FootballLeague;
use App\Models\FootballSeason;
use App\Models\FootballTeam;
use App\Models\FootballMatch;
use Illuminate\Support\Facades\Log;

class SyncBsdFull extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'bsd:sync-all 
                            {--league= : ID của giải đấu cụ thể} 
                            {--season= : Năm của mùa giải (ví dụ 2023)} 
                            {--active-only : Chỉ đồng bộ các giải đang hoạt động}
                            {--squad-only : Chỉ đồng bộ đội hình và cầu thủ, bỏ qua chi tiết trận đấu}
                            {--hydrate-only : Chỉ bơm dữ liệu chi tiết cho các trận chưa có stats}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Đồng bộ toàn bộ dữ liệu từ BSD Sports API (Leagues, Seasons, Teams, Matches, Stats, Players)';

    /**
     * Execute the console command.
     */
    public function handle(BsdSportsApiService $api)
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', 0);
        set_time_limit(0);

        $this->info("Bắt đầu tiến trình đồng bộ tổng lực từ BSD Sports API...");

        if ($this->option('hydrate-only')) {
            $this->hydrateMissingMatches($api);
            return 0;
        }

        // 1. Đồng bộ Giải đấu
        $this->info("1. Đang đồng bộ danh sách giải đấu...");
        $api->syncLeagues();
        $this->info("Đã đồng bộ danh sách giải đấu.");

        // Lọc giải đấu theo tham số
        $leagueQuery = FootballLeague::query();
        if ($this->option('league')) {
            $leagueQuery->where('id', $this->option('league'));
        }
        if ($this->option('active-only')) {
            $leagueQuery->where('is_active', true);
        }

        $leagues = $leagueQuery->get();

        foreach ($leagues as $league) {
            $this->warn("\n--- Đang xử lý Giải: {$league->name} (ID: {$league->id}) ---");

            // 2. Đồng bộ Seasons
            $this->info("   -> Đồng bộ các mùa giải...");
            $seasonsData = $api->syncSeasons($league->id);
            
            // 3. Đồng bộ Teams (Lấy teams của mùa giải hiện tại hoặc mùa được chọn)
            $this->info("   -> Đồng bộ danh sách đội bóng...");
            $api->syncTeamsByLeague($league->id);

            foreach ($seasonsData as $s) {
                if ($this->option('season') && $s['year'] != $this->option('season')) {
                    continue;
                }

                $seasonId = $s['id'];
                $this->info("      [*] Mùa giải: {$s['name']} (Year: {$s['year']}, ID: {$seasonId})");

                // 4. Đồng bộ Standings
                $this->info("      -> Cập nhật bảng xếp hạng...");
                $api->syncStandings($league->id, $seasonId);

                // 5. Đồng bộ Trận đấu (Events)
                $this->info("      -> Đang lấy danh sách trận đấu...");
                $matches = $api->syncSeasonMatches($seasonId);
                $this->info("       Đã đồng bộ " . count($matches) . " trận đấu.");

                // 6. Đồng bộ Players & Manager cho từng đội trong mùa giải này (Làm TRƯỚC để có ID chuẩn)
                $this->syncTeamsAndPlayers($api, $league->id, $seasonId);

                // 7. Hydrate Match Details
                if ($this->option('squad-only')) {
                    $this->info("      -> Đã bật --squad-only. Bỏ qua nạp chi tiết trận đấu.");
                    continue;
                }

                $this->info("      -> Đang nạp dữ liệu chi tiết (Stats, Lineups, Incidents, Odds)...");
                $bar = $this->output->createProgressBar(count($matches));
                $bar->start();

                foreach ($matches as $match) {
                    // Reset timeout cho mỗi trận đấu
                    set_time_limit(120);

                    // Nếu đã có stats thì có thể bỏ qua để tiết kiệm thời gian (tùy chọn)
                    if ($match->stats()->exists() && !$this->option('league')) {
                        $bar->advance();
                        continue;
                    }

                    try {
                        $api->hydrateMatch($match->id);
                    } catch (\Exception $e) {
                        Log::error("Failed to hydrate match {$match->id}: " . $e->getMessage());
                    }
                    $bar->advance();
                }
                $bar->finish();
                $this->info("");
            }
        }

        $this->info("\n HOÀN TẤT TOÀN BỘ TIẾN TRÌNH ĐỒNG BỘ!");
        return 0;
    }

    private function syncTeamsAndPlayers(BsdSportsApiService $api, $leagueId, $seasonId)
    {
        $this->info("      -> Đang đồng bộ Cầu thủ và Huấn luyện viên của các đội...");
        
        // Lấy danh sách các đội tham gia mùa giải này (dựa trên các trận đấu vừa sync)
        $teamIds = FootballMatch::query()->where('season_id', $seasonId)
            ->select('home_team_id')
            ->distinct()
            ->pluck('home_team_id');

        foreach ($teamIds as $teamId) {
            $team = FootballTeam::query()->find($teamId);
            if (!$team) continue;

            $this->output->write("         - Đội: {$team->name} ");
            
            // Reset timeout cho mỗi đội bóng
            set_time_limit(120);

            try {
                // Sync Manager
                $api->syncTeamManager($teamId);
                
                // Sync Players
                $players = $api->syncPlayersByTeam($teamId, $seasonId);
                $this->output->writeln("(" . count($players) . " cầu thủ)");
            } catch (\Exception $e) {
                $this->output->writeln("(Lỗi: " . $e->getMessage() . ")");
                Log::error("Failed to sync team {$teamId} players: " . $e->getMessage());
            }
        }
    }

    private function hydrateMissingMatches(BsdSportsApiService $api)
    {
        $matches = FootballMatch::whereDoesntHave('stats')
            ->where('status', 'finished')
            ->limit(500)
            ->get();

        $this->info("Đang hydrate " . $matches->count() . " trận đấu còn thiếu dữ liệu...");
        
        $bar = $this->output->createProgressBar($matches->count());
        $bar->start();

        foreach ($matches as $match) {
            $api->hydrateMatch($match->id);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n Hoàn tất hydrate.");
    }
}
