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
    protected $signature = 'supervision:send-hourly-error-report {email?} {--period=24hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoie un rapport d\'erreur par email avec les erreurs de la journée';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? config('supervision.admin_email', 'admin@example.com');
        $period = $this->option('period');
        
        // Déterminer la période (par défaut : 24 heures / 1 journée)
        $since = match($period) {
            '1hour' => Carbon::now()->subHour(),
            '6hours' => Carbon::now()->subHours(6),
            '12hours' => Carbon::now()->subHours(12),
            '24hours' => Carbon::now()->subDay(),
            '48hours' => Carbon::now()->subDays(2),
            '7days' => Carbon::now()->subDays(7),
            default => Carbon::now()->subDay(),
        };
        
        // Récupérer toutes les erreurs depuis la période spécifiée
        $errors = ErrorLog::where('created_at', '>=', $since)
            ->with('project')
            ->orderBy('created_at', 'desc') // Les plus récentes en premier
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
        
        // Pour chaque projet, on s'assure que les erreurs sont triées par date (les plus récentes en premier)
        foreach ($errorsByProject as $projectName => $projectErrors) {
            $errorsByProject[$projectName] = $projectErrors->sortByDesc('created_at');
        }
        
        // Compter le nombre total d'erreurs
        $totalErrors = $errors->count();
        $criticalErrors = $errors->where('level', 'error')->count();
        $warningErrors = $errors->where('level', 'warning')->count();
        
        // Période formatée pour le sujet de l'email
        $periodText = match($period) {
            '1hour' => 'dernière heure',
            '6hours' => '6 dernières heures',
            '12hours' => '12 dernières heures',
            '24hours' => 'dernière journée',
            '48hours' => '2 derniers jours',
            '7days' => '7 derniers jours',
            default => 'dernière journée',
        };
        
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
                    'periodText' => $periodText,
                ],
                function ($message) use ($email, $totalErrors, $periodText) {
                    $message->to($email)
                        ->subject("Rapport de supervision : {$totalErrors} erreurs détectées dans la {$periodText}");
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
