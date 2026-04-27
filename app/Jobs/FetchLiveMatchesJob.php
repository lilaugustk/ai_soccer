<?php

namespace App\Jobs;

use App\Services\FootballApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchLiveMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FootballApiService $apiService)
    {
        // 1. Gọi API lấy dữ liệu trận đang đá
        $liveMatches = $apiService->getLiveMatches();

        if (empty($liveMatches)) {
            return;
        }

        // 2. Cập nhật vào Database (Ví dụ dùng Upsert)
        foreach ($liveMatches as $match) {
            $apiMatchId = $match['fixture']['id'];
            $status = $match['fixture']['status']['short']; // 1H, 2H, HT...
            $homeScore = $match['goals']['home'];
            $awayScore = $match['goals']['away'];
            $elapsed = $match['fixture']['status']['elapsed']; // Phút thứ mấy

            // Cập nhật logic Database của bạn ở đây...
            // \App\Models\Match::where('api_id', $apiMatchId)->update([...]);
            
            // Xử lý bài toán Mapping ID nếu cần:
            // $dbMatch = \App\Models\MatchMapping::where('api_id', $apiMatchId)->first();
        }
    }
}