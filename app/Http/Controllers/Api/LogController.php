<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Store error logs from agent
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate API key
            $apiKey = $request->bearerToken();
            if (!$apiKey) {
                return response()->json(['status' => 'error', 'message' => 'API key is required'], 401);
            }

            $project = Project::where('api_key', $apiKey)->where('is_active', true)->first();
            if (!$project) {
                return response()->json(['status' => 'error', 'message' => 'Invalid API key'], 401);
            }

            // Handle single error or array of errors
            $data   = $request->all();
            $errors = is_array($data) && isset($data[0]) ? $data : [$data];

            $processedCount = 0;

            foreach ($errors as $errorData) {
                $validator = Validator::make($errorData, [
                    'project_name' => 'string',

                ]);

                if ($validator->fails()) {
                    Log::warning('Invalid error log data', ['errors' => $validator->errors(), 'data' => $errorData]);
                    continue;
                }

                // Check if this error already exists (same message, file and line)
                $existingError = ErrorLog::where('project_id', $project->id)
                    ->where('file_path', $errorData['file'])
                    ->where('line', $errorData['line'])
                    ->where('error_message', $errorData['error_message'])
                    ->first();

                if ($existingError) {
                    // Increment occurrences count
                    $existingError->incrementOccurrences();

                    // Only update timestamp if newer
                    $newTimestamp = new \DateTime($errorData['timestamp']);
                    if ($newTimestamp > $existingError->error_timestamp) {
                        $existingError->error_timestamp = $newTimestamp;
                        $existingError->save();
                    }
                }
                else {
                    // Create new error log
                    $errorLog = new ErrorLog([
                        'project_id'      => $project->id,
                        'environment'     => $errorData['environment'],
                        'error_message'   => $errorData['error_message'],
                        'file_path'       => $errorData['file'],
                        'line'            => $errorData['line'],
                        'level'           => strtolower($errorData['level']),
                        'error_timestamp' => $errorData['timestamp'],
                        'status'          => 'new',
                    ]);

                    $errorLog->save();

                    // Send notifications for new errors
                    $this->notificationService->notifyNewError($errorLog);
                }

                $processedCount++;
            }

            return response()->json([
                'status'  => 'success',
                'message' => "Processed $processedCount error logs"
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing logs', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while processing logs'
            ], 500);
        }
    }
}
