<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use Illuminate\Console\Command;

class PurgeOldErrorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervision:purge-old-errors {--days=30 : Nombre de jours de rétention} {--dry-run : Exécution en mode simulation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge les anciennes erreurs résolues ou ignorées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Purge des erreurs résolues/ignorées plus anciennes que {$days} jours...");
        
        $cutoffDate = now()->subDays($days);
        
        $query = ErrorLog::where('error_timestamp', '<', $cutoffDate)
            ->whereIn('status', ['resolved', 'ignored']);
        
        $count = $query->count();
        
        if ($dryRun) {
            $this->info("Mode simulation: {$count} erreurs seraient supprimées.");
            return Command::SUCCESS;
        }
        
        if ($count === 0) {
            $this->info("Aucune erreur à purger.");
            return Command::SUCCESS;
        }
        
        $query->delete();
        
        $this->info("{$count} anciennes erreurs ont été purgées avec succès.");
        
        return Command::SUCCESS;
    }
}
