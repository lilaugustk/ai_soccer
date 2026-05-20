<?php

namespace App\Jobs;

use App\Services\BsdSportsApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HydrateMatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Số lần retry tối đa nếu job thất bại
     */
    public int $tries = 2;

    /**
     * Timeout tối đa cho job (giây)
     */
    public int $timeout = 120;

    public function __construct(
        public readonly int $matchId,
        public readonly string $activeTab = 'lineups'
    ) {}

    public function handle(BsdSportsApiService $apiService): void
    {
        $lockKey = "hydrate_match_job_{$this->matchId}";

        // Tránh chạy song song 2 job cho cùng 1 trận
        if (!Cache::add($lockKey, true, now()->addSeconds(90))) {
            Log::info("[HydrateMatchJob] Match {$this->matchId} already being hydrated, skipping.");
            return;
        }

        try {
            Log::info("[HydrateMatchJob] Starting hydration for match {$this->matchId}, tab={$this->activeTab}");
            
            $apiService->hydrateMatch($this->matchId);

            // Xóa display cache cho tất cả tabs sau khi sync xong
            $validTabs = ['lineups', 'stats', 'h2h', 'standings', 'timeline', 'analysis', 'social'];
            foreach ($validTabs as $tab) {
                Cache::forget("match_display_v24_{$this->matchId}_{$tab}");
            }

            // Đánh dấu job đã hoàn thành để frontend biết có thể reload
            Cache::put("match_hydrated_{$this->matchId}", now()->toIso8601String(), now()->addMinutes(10));

            Log::info("[HydrateMatchJob] Completed hydration for match {$this->matchId}");
        } catch (\Exception $e) {
            Log::error("[HydrateMatchJob] Failed for match {$this->matchId}: " . $e->getMessage());
            throw $e; // Cho phép retry
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Xử lý khi job thất bại hoàn toàn (sau tất cả retry)
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("[HydrateMatchJob] Permanently failed for match {$this->matchId}: " . $exception->getMessage());
        // Xóa lock để tránh bị kẹt
        Cache::forget("hydrate_match_job_{$this->matchId}");
    }
}
