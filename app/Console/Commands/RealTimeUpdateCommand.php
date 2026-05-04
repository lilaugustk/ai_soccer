<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchLiveMatchesJob;

class RealTimeUpdateCommand extends Command
{
    protected $signature = 'soccer:realtime';
    protected $description = 'Fetch live matches every 15 seconds';

    public function handle()
    {
        $this->info('Starting real-time soccer updates (every 15s)...');

        while (true) {
            $this->info('Fetching live data: ' . now()->toDateTimeString());
            
            // Dispatch the job synchronously to wait for it, or use the service directly
            dispatch(new FetchLiveMatchesJob());

            sleep(15);
        }
    }
}
