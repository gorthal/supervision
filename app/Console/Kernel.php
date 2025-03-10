<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Envoyer les digests horaires
        $schedule->command('supervision:send-hourly-digests')->hourly();
        
        // Envoyer les digests quotidiens à 9h du matin
        $schedule->command('supervision:send-daily-digests')->dailyAt('09:00');
        
        // Envoyer un rapport d'erreurs par email toutes les heures avec toutes les erreurs de la journée
        $schedule->command('supervision:send-hourly-error-report --period=24hours')
                ->hourly();
        
        // Maintenance - Purger les erreurs anciennes (plus de 30 jours) marquées comme résolues ou ignorées
        $schedule->command('supervision:purge-old-errors')->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
