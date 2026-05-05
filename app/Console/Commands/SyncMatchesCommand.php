<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballApiService;
use Carbon\Carbon;

class SyncMatchesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soccer:sync-daily {--days=1 : Số ngày muốn nạp} {--past : Nạp về quá khứ thay vì tương lai}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ dữ liệu trận đấu từ API vào Database (mặc định nạp từ hôm nay trở đi)';

    /**
     * Execute the console command.
     */
    public function handle(FootballApiService $apiService)
    {
        $days = (int) $this->option('days');
        $isPast = $this->option('past');
        
        $direction = $isPast ? 'về quá khứ' : 'tới tương lai';
        $this->info("Bắt đầu đồng bộ dữ liệu cho {$days} ngày {$direction}...");

        for ($i = 0; $i < $days; $i++) {
            $date = $isPast 
                ? Carbon::today()->subDays($i)->toDateString()
                : Carbon::today()->addDays($i)->toDateString();
                
            $this->comment("Đang nạp dữ liệu ngày: {$date}...");
            
            $fixtures = $apiService->getFixturesByDate($date);
            
            if (empty($fixtures)) {
                $this->error("Không lấy được dữ liệu cho ngày {$date}.");
                $this->comment("Gợi ý: Kiểm tra file log tại storage/logs/laravel.log để xem chi tiết lỗi API.");
            } else {
                $this->info("Đã nạp " . count($fixtures) . " trận đấu cho ngày {$date}.");
            }

            // Nghỉ 2 giây để tránh bị API chặn do gọi quá nhanh (Rate Limit)
            if ($i < $days - 1) {
                $this->comment("Chờ 2 giây...");
                sleep(2);
            }
        }

        $this->info('Đồng bộ hoàn tất!');
    }
}
