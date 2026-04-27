<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class BackfillMatchesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soccer:backfill {league_id : ID của giải đấu (ví dụ EPL là 39)} {season=2025 : Mùa giải muốn nạp}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nạp toàn bộ dữ liệu (Trận đấu & BXH) của một giải đấu trong mùa giải';

    /**
     * Execute the console command.
     */
    public function handle(\App\Services\FootballApiService $apiService)
    {
        $leagueId = $this->argument('league_id');
        $season = $this->argument('season');

        $this->info("=== BẮT ĐẦU BACKFILL DỮ LIỆU [Giải: {$leagueId} | Mùa: {$season}] ===");

        // Bước 1: Nạp Trận đấu
        $this->comment("1. Đang nạp toàn bộ trận đấu của mùa giải...");
        $fixtures = $apiService->getFixturesByLeagueSeason($leagueId, $season);
        
        if (empty($fixtures)) {
            $this->error("X Không nạp được trận đấu. Có thể do hết hạn mức API hoặc ID giải đấu sai.");
        } else {
            $this->info("V Thành công! Đã nạp/cập nhật " . count($fixtures) . " trận đấu.");
        }

        // Bước 2: Nạp Bảng xếp hạng
        $this->comment("2. Đang nạp bảng xếp hạng hiện tại...");
        $standings = $apiService->getStandings($leagueId, $season);

        if (empty($standings)) {
            $this->error("X Không nạp được bảng xếp hạng.");
        } else {
            $this->info("V Thành công! Đã cập nhật bảng xếp hạng cho " . count($standings) . " đội.");
        }

        // Bước 3: Nạp Vua phá lưới
        $this->comment("3. Đang nạp danh sách vua phá lưới...");
        $scorers = $apiService->getTopScorers($leagueId, $season);

        if (empty($scorers)) {
            $this->error("X Không nạp được danh sách vua phá lưới.");
        } else {
            $this->info("V Thành công! Đã cập nhật " . count($scorers) . " sát thủ ghi bàn.");
        }

        $this->info("=== HOÀN TẤT QUÁ TRÌNH BACKFILL ===");
        $this->warn("Dữ liệu Trận đấu, BXH và Vua phá lưới đã sẵn sàng!");
    }
}
