<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendDailyDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervision:send-daily-digests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily error digests to configured emails';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Sending daily error digests...');
        
        $notificationService->sendDailyDigests();
        
        $this->info('Daily error digests sent successfully!');
        
        return Command::SUCCESS;
    }
}
