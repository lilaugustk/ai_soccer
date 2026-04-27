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
    protected $signature = 'soccer:sync-daily {--days=1 : Số ngày muốn nạp kể từ hôm nay}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đồng bộ dữ liệu trận đấu từ API vào Database';

    /**
     * Execute the console command.
     */
    public function handle(FootballApiService $apiService)
    {
        $days = (int) $this->option('days');
        $this->info("Bắt đầu đồng bộ dữ liệu cho {$days} ngày tới...");

        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::today()->addDays($i)->toDateString();
            $this->comment("Đang nạp dữ liệu ngày: {$date}...");
            
            $fixtures = $apiService->getFixturesByDate($date);
            $this->info("Đã nạp " . count($fixtures) . " trận đấu cho ngày {$date}.");
        }

        $this->info('Đồng bộ hoàn tất!');
    }
}
