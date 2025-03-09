<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
            
            if (empty($project->api_key)) {
                $project->api_key = Str::random(32);
            }
        });
    }

    /**
     * Get all errors for this project
     */
    public function errorLogs(): HasMany
    {
        return $this->hasMany(ErrorLog::class);
    }

    /**
     * Get notification settings for this project
     */
    public function notificationSettings(): HasMany
    {
        return $this->hasMany(NotificationSetting::class);
    }

    /**
     * Get error count by status
     */
    public function getErrorCountAttribute(): array
    {
        return [
            'new' => $this->errorLogs()->where('status', 'new')->count(),
            'in_progress' => $this->errorLogs()->where('status', 'in_progress')->count(),
            'resolved' => $this->errorLogs()->where('status', 'resolved')->count(),
            'ignored' => $this->errorLogs()->where('status', 'ignored')->count(),
        ];
    }
}
