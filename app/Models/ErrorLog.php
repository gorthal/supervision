<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'environment',
        'error_message',
        'file_path',
        'line',
        'level',
        'error_timestamp',
        'occurrences',
        'status',
        'notes',
        'comment',
    ];

    protected $casts = [
        'line' => 'integer',
        'occurrences' => 'integer',
        'error_timestamp' => 'datetime',
    ];

    /**
     * Get the project that owns the error log
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope errors by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope errors by severity level
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Scope errors by environment
     */
    public function scopeByEnvironment($query, $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Scope pour trouver des erreurs similaires (même fichier, même ligne, message similaire)
     */
    public function scopeFindSimilar($query, ErrorLog $errorLog)
    {
        return $query->where('project_id', $errorLog->project_id)
            ->where('file_path', $errorLog->file_path)
            ->where('line', $errorLog->line)
            ->where('id', '!=', $errorLog->id)
            ->where(function ($q) use ($errorLog) {
                // Trouve les erreurs avec le même message ou un message très similaire
                $q->where('error_message', $errorLog->error_message)
                  ->orWhere('error_message', 'like', '%' . substr($errorLog->error_message, 0, 100) . '%');
            });
    }

    /**
     * Normalise un message d'erreur en remplaçant les identifiants uniques par des placeholders
     */
    public static function normalizeMessage(string $message): string
    {
        // Remplacer les identifiants d'utilisateur
        $normalized = preg_replace('/\"userId\":\\s*\\d+/', '\"userId\":\"[ID]\"', $message);
        
        // Remplacer les IDs numériques dans les chemins de fichiers
        $normalized = preg_replace('/\/\d+\//', '/[ID]/', $normalized);
        
        return $normalized;
    }

    /**
     * Mark the error as in progress
     */
    public function markAsInProgress(?string $notes = null): self
    {
        $this->status = 'in_progress';
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
        
        return $this;
    }

    /**
     * Mark the error as resolved
     */
    public function markAsResolved(?string $notes = null): self
    {
        $this->status = 'resolved';
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
        
        return $this;
    }

    /**
     * Mark the error as ignored
     */
    public function markAsIgnored(?string $notes = null): self
    {
        $this->status = 'ignored';
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
        
        return $this;
    }

    /**
     * Increment occurrences count
     */
    public function incrementOccurrences(int $count = 1): self
    {
        $this->occurrences += $count;
        $this->save();
        
        return $this;
    }
}
