<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ErrorDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $project;
    protected $errors;
    protected $frequency;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, Collection $errors, string $frequency)
    {
        $this->project = $project;
        $this->errors = $errors;
        $this->frequency = $frequency;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $period = $this->frequency === 'hourly' ? 'la dernière heure' : 'les dernières 24 heures';
        $url = route('filament.resources.projects.view', $this->project->id);

        $mail = (new MailMessage)
            ->subject("[{$this->project->name}] Résumé des erreurs - " . ucfirst($this->frequency))
            ->greeting("Résumé des erreurs pour {$this->project->name}")
            ->line("Voici le résumé des erreurs détectées pendant {$period}.")
            ->line("**Nombre total d'erreurs:** " . $this->errors->count());

        // Regrouper les erreurs par niveau
        $errorsByLevel = $this->errors->groupBy('level');
        foreach ($errorsByLevel as $level => $errors) {
            $mail->line("**{$errors->count()} erreurs de niveau \"{$level}\"**");
        }

        // Liste des 5 erreurs les plus récentes
        $mail->line('');
        $mail->line('**5 erreurs les plus récentes:**');
        
        $recentErrors = $this->errors->sortByDesc('error_timestamp')->take(5);
        
        foreach ($recentErrors as $error) {
            $mail->line("- {$error->level}: {$error->error_message} ({$error->file_path}:{$error->line})");
        }

        $mail->action('Voir toutes les erreurs', $url)
            ->line('Merci d\'utiliser notre système de supervision!');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'errors_count' => $this->errors->count(),
            'frequency' => $this->frequency,
        ];
    }
}
