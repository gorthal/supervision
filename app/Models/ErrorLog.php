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
     * Scopre errors by status
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
