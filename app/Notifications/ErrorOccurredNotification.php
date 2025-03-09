<?php

namespace App\Notifications;

use App\Models\ErrorLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ErrorOccurredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $errorLog;

    /**
     * Create a new notification instance.
     */
    public function __construct(ErrorLog $errorLog)
    {
        $this->errorLog = $errorLog;
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
        $project = $this->errorLog->project;
        $url = route('filament.resources.error-logs.view', $this->errorLog->id);

        return (new MailMessage)
            ->subject("[{$project->name}] Nouvelle erreur détectée: {$this->errorLog->level}")
            ->greeting("Erreur détectée dans {$project->name}")
            ->line("Une erreur de niveau **{$this->errorLog->level}** a été détectée.")
            ->line("**Message d'erreur:** {$this->errorLog->error_message}")
            ->line("**Fichier:** {$this->errorLog->file_path}:{$this->errorLog->line}")
            ->line("**Environnement:** {$this->errorLog->environment}")
            ->line("**Date:** " . $this->errorLog->error_timestamp->format('d/m/Y H:i:s'))
            ->action('Voir les détails', $url)
            ->line('Merci d\'utiliser notre système de supervision!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'error_log_id' => $this->errorLog->id,
            'project_id' => $this->errorLog->project_id,
            'level' => $this->errorLog->level,
            'message' => $this->errorLog->error_message,
        ];
    }
}
