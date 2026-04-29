<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FootballApiService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class SyncLeaguesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soccer:sync-leagues';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lấy danh sách các giải đấu từ API và lưu vào file tĩnh để tối ưu sidebar';

    /**
     * Execute the console command.
     */
    public function handle(FootballApiService $apiService)
    {
        $this->info('Bắt đầu đồng bộ danh sách giải đấu...');

        try {
            $leaguesData = $apiService->getLeagues();
            
            if (empty($leaguesData)) {
                $this->error('Không nhận được dữ liệu từ API.');
                return 1;
            }

            $groupedLeagues = collect($leaguesData)
                ->filter(fn($l) => isset($l['country']['name']) && $l['country']['name'] !== '')
                ->groupBy('country.name')
                ->map(function ($leagues, $country) {
                    $firstLeague = $leagues->first();
                    
                    // Đồng bộ từng giải đấu vào Database để tránh lỗi 404 khi click xem chi tiết
                    foreach ($leagues as $l) {
                        \App\Models\FootballLeague::updateOrCreate(
                            ['id' => $l['league']['id']],
                            [
                                'name' => $l['league']['name'],
                                'type' => $l['league']['type'] ?? null,
                                'logo' => $l['league']['logo'] ?? null,
                                'country_name' => $l['country']['name'] ?? null,
                                'country_code' => $l['country']['flag'] ?? null,
                            ]
                        );
                    }

                    return [
                        'country_name' => $country,
                        'country_code' => $firstLeague['country']['code'] ?? null,
                        'leagues' => $leagues->map(fn($l) => [
                            'id' => $l['league']['id'] ?? null,
                            'name' => $l['league']['name'] ?? 'Unknown',
                            'logo_url' => $l['league']['logo'] ?? null,
                        ])->filter(fn($l) => $l['id'] !== null)->take(10)
                    ];
                })
                ->values()
                ->toArray();

            // Lưu vào storage/app/public/leagues_sidebar.json
            Storage::disk('public')->put('leagues_sidebar.json', json_encode($groupedLeagues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $this->info('Đồng bộ thành công! Dữ liệu đã được lưu vào storage/app/public/leagues_sidebar.json');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Lỗi khi đồng bộ: ' . $e->getMessage());
            Log::error('SyncLeaguesCommand Error: ' . $e->getMessage());
            return 1;
        }
    }
}
