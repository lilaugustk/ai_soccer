<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballApiService;
use App\Models\FootballLeague;

class SyncUltraCommand extends Command
{
    protected $signature = 'soccer:sync-ultra {--season=2024 : Mùa giải muốn nạp}';
    protected $description = 'Tối ưu hóa 100 request API hàng ngày để nạp toàn bộ dữ liệu của Top 30 giải đấu hàng đầu';

    // Danh sách 30 giải đấu quan trọng nhất thế giới + Việt Nam
    protected $priorityLeagues = [
        39, 140, 135, 78, 61, // Top 5 Châu Âu (Anh, Tây Ban Nha, Ý, Đức, Pháp)
        2, 3, 848,            // Cúp Châu Âu (C1, C2, C3)
        40, 141, 136, 79, 62, // Giải hạng 2 các nước lớn
        94, 88, 144, 179, 203, // Bồ Đào Nha, Hà Lan, Bỉ, Scotland, Thổ Nhĩ Kỳ
        71, 128,              // Brazil, Argentina
        307, 291,             // Saudi Arabia, Việt Nam (V-League)
        1, 4, 9, 10, 11, 20, 21 // Các giải quốc tế quan trọng (World Cup, Euro, v.v.)
    ];

    public function handle(FootballApiService $apiService)
    {
        $season = $this->option('season');
        $this->info("🚀 BẮT ĐẦU CHIẾN DỊCH SIÊU NẠP DỮ LIỆU (DAILY MAX SYNC)");
        $this->warn("Giới hạn: 100 requests/ngày. Dự kiến sử dụng: ~91 requests.");
        
        $startTime = microtime(true);

        // Bước 1: Đồng bộ toàn bộ danh sách giải đấu (1 Request)
        $this->info("\n[1/100] Đang cập nhật danh sách 900+ giải đấu...");
        $this->call('soccer:sync-leagues');

        // Bước 2: Lặp qua các giải ưu tiên
        $totalPriority = count($this->priorityLeagues);
        foreach ($this->priorityLeagues as $index => $id) {
            $currentRequestCount = ($index * 3) + 2; // Ước tính số request đã dùng
            
            $this->info("\n--- [" . ($index + 1) . "/$totalPriority] Giải đấu ID: $id (Yêu cầu thứ $currentRequestCount) ---");

            // Lấy tên giải đấu từ DB (đã được sync-leagues nạp ở Bước 1)
            $league = FootballLeague::find($id);
            $leagueName = $league ? $league->name : "Giải đấu $id";
            $this->comment("Đang xử lý: $leagueName");

            // 1. Nạp Trận đấu (1 Request)
            $this->line("   + Nạp lịch thi đấu & kết quả...");
            $apiService->getFixturesByLeagueSeason($id, $season);

            // 2. Nạp BXH (1 Request)
            $this->line("   + Nạp bảng xếp hạng...");
            $apiService->getStandings($id, $season);

            // 3. Nạp Vua phá lưới (1 Request)
            $this->line("   + Nạp danh sách vua phá lưới...");
            $apiService->getTopScorers($id, $season);

            // Nghỉ 7 giây để tránh Rate Limit 10 requests/phút của gói Free
            if ($index < $totalPriority - 1) {
                $this->info("   (Chờ 7 giây để bảo vệ Rate Limit...)");
                sleep(7);
            }
        }

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("\n✅ HOÀN TẤT! Đã tối ưu hóa dữ liệu cho $totalPriority giải đấu.");
        $this->info("Thời gian thực hiện: $duration giây.");
        $this->warn("Lưu ý: Bạn đã sử dụng gần hết 100 request của ngày hôm nay.");
    }
}
