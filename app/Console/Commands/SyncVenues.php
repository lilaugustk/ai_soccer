<?php

namespace App\Console\Commands;

use App\Models\FootballReferee;
use App\Models\FootballVenue;
use App\Services\BsdSportsApiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

class SyncVenues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bsd:sync-metadata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync unknown venues and referees from BSD Sports API';

    /**
     * Execute the console command.
     */
    public function handle(BsdSportsApiService $apiService)
    {
        // 1. Sync Venues
        $this->info("Fetching unknown venues from database...");
        $venues = FootballVenue::query()->where('name', 'Unknown Venue')->orWhere('name', 'like', '%Unknown%')->get();
        if ($venues->isNotEmpty()) {
            $this->info("Found " . $venues->count() . " unknown venues. Syncing...");
            $bar = $this->output->createProgressBar($venues->count());
            $bar->start();
            foreach ($venues as $venue) {
                $apiService->syncVenueIfNotExists($venue->id);
                $bar->advance();
                usleep(100000);
            }
            $bar->finish();
            $this->newLine();
        } else {
            $this->info("No unknown venues found.");
        }

        // 2. Sync Referees
        $this->info("Fetching unknown referees from database...");
        $referees = FootballReferee::query()->where('name', 'Unknown Referee')->orWhere('name', 'like', '%Unknown%')->get();
        if ($referees->isNotEmpty()) {
            $this->info("Found " . $referees->count() . " unknown referees. Syncing...");
            $bar = $this->output->createProgressBar($referees->count());
            $bar->start();
            foreach ($referees as $ref) {
                $apiService->syncRefereeIfNotExists($ref->id);
                $bar->advance();
                usleep(100000);
            }
            $bar->finish();
            $this->newLine();
        } else {
            $this->info("No unknown referees found.");
        }

        $this->info("Finished syncing metadata!");
    }
}
