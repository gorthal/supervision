<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\NotificationSetting;
use App\Notifications\ErrorOccurredNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Notify about a new error
     *
     * @param ErrorLog $errorLog
     * @return void
     */
    public function notifyNewError(ErrorLog $errorLog)
    {
        try {
            $settings = NotificationSetting::where('project_id', $errorLog->project_id)
                ->where('frequency', 'realtime')
                ->where('notify_new', true)
                ->get();

            if ($settings->isEmpty()) {
                return;
            }

            // Filter based on error level
            $recipients = $settings->filter(function ($setting) use ($errorLog) {
                return $setting->shouldNotifyForLevel($errorLog->level);
            })->pluck('email')->toArray();

            if (empty($recipients)) {
                return;
            }

            Notification::route('mail', $recipients)
                ->notify(new ErrorOccurredNotification($errorLog));

        } catch (\Exception $e) {
            Log::error('Failed to send error notification', [
                'error' => $e->getMessage(),
                'error_log_id' => $errorLog->id
            ]);
        }
    }

    /**
     * Send hourly digests
     *
     * @return void
     */
    public function sendHourlyDigests()
    {
        $settings = NotificationSetting::where('frequency', 'hourly')->get();

        foreach ($settings as $setting) {
            try {
                $project = $setting->project;
                
                if (!$project->is_active) {
                    continue;
                }

                $lastHour = now()->subHour();
                
                $errors = $project->errorLogs()
                    ->where('error_timestamp', '>=', $lastHour)
                    ->get();
                
                if ($errors->isEmpty()) {
                    continue;
                }

                // Filter errors based on settings
                $filteredErrors = $errors->filter(function ($error) use ($setting) {
                    if ($error->status === 'new' && !$setting->notify_new) {
                        return false;
                    }
                    
                    return $setting->shouldNotifyForLevel($error->level);
                });

                if ($filteredErrors->isEmpty()) {
                    continue;
                }

                // Send digest notification
                Notification::route('mail', $setting->email)
                    ->notify(new ErrorDigestNotification($project, $filteredErrors, 'hourly'));

            } catch (\Exception $e) {
                Log::error('Failed to send hourly digest', [
                    'error' => $e->getMessage(),
                    'setting_id' => $setting->id
                ]);
            }
        }
    }

    /**
     * Send daily digests
     *
     * @return void
     */
    public function sendDailyDigests()
    {
        $settings = NotificationSetting::where('frequency', 'daily')->get();

        foreach ($settings as $setting) {
            try {
                $project = $setting->project;
                
                if (!$project->is_active) {
                    continue;
                }

                $yesterday = now()->subDay();
                
                $errors = $project->errorLogs()
                    ->where('error_timestamp', '>=', $yesterday)
                    ->get();
                
                if ($errors->isEmpty()) {
                    continue;
                }

                // Filter errors based on settings
                $filteredErrors = $errors->filter(function ($error) use ($setting) {
                    if ($error->status === 'new' && !$setting->notify_new) {
                        return false;
                    }
                    
                    return $setting->shouldNotifyForLevel($error->level);
                });

                if ($filteredErrors->isEmpty()) {
                    continue;
                }

                // Send digest notification
                Notification::route('mail', $setting->email)
                    ->notify(new ErrorDigestNotification($project, $filteredErrors, 'daily'));

            } catch (\Exception $e) {
                Log::error('Failed to send daily digest', [
                    'error' => $e->getMessage(),
                    'setting_id' => $setting->id
                ]);
            }
        }
    }
}
