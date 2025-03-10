<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'slack_webhook_url',
        'is_active',
        'notify_on_error',
        'notify_on_warning',
        'notify_on_info',
        'notification_frequency',
        'project_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'notify_on_error' => 'boolean',
        'notify_on_warning' => 'boolean',
        'notify_on_info' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
