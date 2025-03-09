<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendHourlyDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervision:send-hourly-digests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send hourly error digests to configured emails';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Sending hourly error digests...');
        
        $notificationService->sendHourlyDigests();
        
        $this->info('Hourly error digests sent successfully!');
        
        return Command::SUCCESS;
    }
}
