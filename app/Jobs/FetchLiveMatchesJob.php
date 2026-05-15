<?php
namespace App\Jobs;

use App\Services\BsdSportsApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchLiveMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(BsdSportsApiService $apiService): void
    {
        try {
            Log::info('Running FetchLiveMatchesJob...');
            $apiService->getLiveEvents();
            Log::info('FetchLiveMatchesJob completed successfully.');
        } catch (\Exception $e) {
            Log::error('FetchLiveMatchesJob failed: ' . $e->getMessage());
        }
    }
}
