<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendHourlyErrorReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'supervision:send-hourly-error-report {email?} {--period=1hour}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie un rapport d\'erreur par email avec les erreurs des dernières heures';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? config('supervision.admin_email', 'admin@example.com');
        $period = $this->option('period');
        
        // Déterminer la période (par défaut : 1 heure)
        $since = match($period) {
            '1hour' => Carbon::now()->subHour(),
            '6hours' => Carbon::now()->subHours(6),
            '12hours' => Carbon::now()->subHours(12),
            '24hours' => Carbon::now()->subDay(),
            default => Carbon::now()->subHour(),
        };
        
        // Récupérer toutes les erreurs depuis la période spécifiée
        $errors = ErrorLog::where('created_at', '>=', $since)
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // S'il n'y a pas d'erreurs, on peut terminer
        if ($errors->isEmpty()) {
            $this->info('Aucune erreur trouvée pour la période spécifiée');
            return 0;
        }
        
        // Regrouper les erreurs par projet
        $errorsByProject = $errors->groupBy(function ($error) {
            return $error->project->name ?? 'Sans projet';
        });
        
        // Compter le nombre total d'erreurs
        $totalErrors = $errors->count();
        $criticalErrors = $errors->where('level', 'error')->count();
        $warningErrors = $errors->where('level', 'warning')->count();
        
        // Envoyer l'email
        try {
            Mail::send(
                'emails.error-report', 
                [
                    'errors' => $errors,
                    'errorsByProject' => $errorsByProject,
                    'totalErrors' => $totalErrors,
                    'criticalErrors' => $criticalErrors,
                    'warningErrors' => $warningErrors,
                    'since' => $since->format('d/m/Y H:i'),
                    'period' => $period,
                ],
                function ($message) use ($email, $totalErrors) {
                    $message->to($email)
                        ->subject("Rapport de supervision : {$totalErrors} erreurs détectées");
                }
            );
            
            $this->info("Rapport envoyé à {$email} avec {$totalErrors} erreurs");
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du rapport d\'erreur', [
                'error' => $e->getMessage(),
            ]);
            $this->error("Erreur lors de l'envoi du rapport: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
