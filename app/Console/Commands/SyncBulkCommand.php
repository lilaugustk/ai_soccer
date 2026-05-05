<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballApiService;

class SyncBulkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soccer:sync-bulk {--season=2024 : Mùa giải muốn nạp} {--leagues=39,140,135,78,61,2 : Danh sách ID giải đấu cách nhau bởi dấu phẩy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nạp dữ liệu toàn diện (Trận đấu, BXH, Vua phá lưới) cho các giải đấu ưu tiên với tối ưu Request';

    /**
     * Execute the console command.
     */
    public function handle(FootballApiService $apiService)
    {
        $season = $this->option('season');
        $leaguesInput = $this->option('leagues');
        $leagueIds = explode(',', $leaguesInput);

        $this->info("=== BẮT ĐẦU ĐỒNG BỘ DỮ LIỆU TỔNG THỂ [Mùa giải: $season] ===");
        $this->warn("Danh sách giải đấu: " . $leaguesInput);
        $this->warn("Cơ chế an toàn: Nghỉ 7 giây giữa mỗi giải đấu để không vượt quá 10 requests/phút (Free Plan).");
        
        $startTime = microtime(true);

        foreach ($leagueIds as $index => $id) {
            $id = trim($id);
            if (empty($id)) continue;

            $this->info("\n[" . ($index + 1) . "/" . count($leagueIds) . "] Đang xử lý Giải đấu ID: $id");

            // 1. Lấy toàn bộ trận đấu (1 Request)
            $this->comment("   + Đang nạp toàn bộ lịch thi đấu & kết quả...");
            $fixtures = $apiService->getFixturesByLeagueSeason($id, $season);
            if (empty($fixtures)) {
                $this->error("     X Không lấy được trận đấu. Có thể ID sai hoặc mùa giải không được hỗ trợ.");
            } else {
                $this->line("     V Đã nạp/cập nhật " . count($fixtures) . " trận đấu.");
            }
            sleep(6);

            // 2. Lấy BXH (1 Request)
            $this->comment("   + Đang cập nhật bảng xếp hạng...");
            $standings = $apiService->getStandings($id, $season);
            if (empty($standings)) {
                $this->error("     X Không lấy được bảng xếp hạng.");
            } else {
                $this->line("     V Đã cập nhật BXH cho " . count($standings) . " đội.");
            }
            sleep(6);

            // 3. Lấy Vua phá lưới (1 Request)
            $this->comment("   + Đang cập nhật danh sách vua phá lưới...");
            $scorers = $apiService->getTopScorers($id, $season);
            if (empty($scorers)) {
                $this->error("     X Không lấy được danh sách ghi bàn.");
            } else {
                $this->line("     V Đã cập nhật " . count($scorers) . " cầu thủ ghi bàn.");
            }
            sleep(6);

            // 4. Lấy Kiến tạo (1 Request)
            $this->comment("   + Đang cập nhật danh sách kiến tạo...");
            $assists = $apiService->getTopAssists($id, $season);
            if (empty($assists)) {
                $this->error("     X Không lấy được danh sách kiến tạo.");
            } else {
                $this->line("     V Đã cập nhật " . count($assists) . " cầu thủ kiến tạo.");
            }
            sleep(6);

            // 5. Lấy Thẻ vàng (1 Request)
            $this->comment("   + Đang cập nhật danh sách thẻ vàng...");
            $yellowCards = $apiService->getTopYellowCards($id, $season);
            if (empty($yellowCards)) {
                $this->error("     X Không lấy được danh sách thẻ vàng.");
            } else {
                $this->line("     V Đã cập nhật " . count($yellowCards) . " cầu thủ nhận thẻ vàng.");
            }
            sleep(6);

            // 6. Lấy Thẻ đỏ (1 Request)
            $this->comment("   + Đang cập nhật danh sách thẻ đỏ...");
            $redCards = $apiService->getTopRedCards($id, $season);
            if (empty($redCards)) {
                $this->error("     X Không lấy được danh sách thẻ đỏ.");
            } else {
                $this->line("     V Đã cập nhật " . count($redCards) . " cầu thủ nhận thẻ đỏ.");
            }
            sleep(6);


        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("\n=== HOÀN TẤT QUÁ TRÌNH NẠP DỮ LIỆU ===");
        $this->info("Tổng thời gian: $duration giây.");
        $this->warn("Dữ liệu đã sẵn sàng trên giao diện website!");
        
        return 0;
    }
}
