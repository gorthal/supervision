<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'email',
        'notify_new',
        'notify_critical',
        'notify_error',
        'notify_warning',
        'frequency',
        'daily_time',
    ];

    protected $casts = [
        'notify_new' => 'boolean',
        'notify_critical' => 'boolean',
        'notify_error' => 'boolean',
        'notify_warning' => 'boolean',
        'daily_time' => 'datetime',
    ];

    /**
     * Get the project that owns the notification setting
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Check if should notify based on error level
     */
    public function shouldNotifyForLevel(string $level): bool
    {
        switch (strtolower($level)) {
            case 'critical':
            case 'alert':
            case 'emergency':
                return $this->notify_critical;
            
            case 'error':
                return $this->notify_error;
                
            case 'warning':
                return $this->notify_warning;
                
            default:
                return false;
        }
    }

    /**
     * Check if should notify for new errors
     */
    public function shouldNotifyForNewErrors(): bool
    {
        return $this->notify_new;
    }

    /**
     * Scope by realtime frequency
     */
    public function scopeRealtime($query)
    {
        return $query->where('frequency', 'realtime');
    }

    /**
     * Scope by hourly frequency
     */
    public function scopeHourly($query)
    {
        return $query->where('frequency', 'hourly');
    }

    /**
     * Scope by daily frequency
     */
    public function scopeDaily($query)
    {
        return $query->where('frequency', 'daily');
    }
}
