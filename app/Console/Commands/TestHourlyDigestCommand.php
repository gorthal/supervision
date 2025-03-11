<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestHourlyDigestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervision:test-hourly-digests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending hourly error digests with debug output';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Starting test of hourly digest emails...');
        
        // Enable additional logging
        Log::info('Starting test of hourly digest emails command');
        
        try {
            $notificationService->sendHourlyDigests();
            $this->info('Hourly digest email test completed successfully!');
            $this->info('Check the logs for more information.');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            Log::error('Error in test-hourly-digests command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}
