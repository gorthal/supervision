<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LogController extends Controller
{
    protected $notificationService;
    
    // Patterns d'erreurs à ignorer
    protected $ignorePatterns = [
        '/^\s*View \#\d+ \/var\/www\/html\/.*\/artisan\(\d+\)\: .*->handle\(\)$/',
        '/^\s*View \#\d+ \{main\}$/',
    ];

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
            $filteredCount = 0;

            foreach ($errors as $errorData) {
                $validator = Validator::make($errorData, [
                    'project_name' => 'string',
                ]);

                if ($validator->fails()) {
                    Log::warning('Invalid error log data', ['errors' => $validator->errors(), 'data' => $errorData]);
                    continue;
                }

                // Filtrer les erreurs selon les patterns à ignorer
                if ($this->shouldIgnoreError($errorData['error_message'])) {
                    $filteredCount++;
                    continue;
                }

                // Normaliser le message d'erreur pour regrouper les erreurs similaires
                $normalizedMessage = $this->normalizeErrorMessage($errorData['error_message']);
                
                // Vérifier si cette erreur existe déjà (même message normalisé, fichier et ligne)
                $existingError = ErrorLog::where('project_id', $project->id)
                    ->where('file_path', $errorData['file'])
                    ->where('line', $errorData['line'])
                    ->where(function ($query) use ($normalizedMessage, $errorData) {
                        // Chercher soit le message normalisé, soit le message original
                        $query->where('error_message', $normalizedMessage)
                            ->orWhere('error_message', $errorData['error_message']);
                    })
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
                    // Create new error log with normalized message
                    $errorLog = new ErrorLog([
                        'project_id'      => $project->id,
                        'environment'     => $errorData['environment'],
                        'error_message'   => $normalizedMessage,
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
                'message' => "Processed $processedCount error logs, filtered $filteredCount non-essential logs"
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing logs', ['exception' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while processing logs'
            ], 500);
        }
    }
    
    /**
     * Vérifie si un message d'erreur doit être ignoré
     * 
     * @param string $errorMessage
     * @return bool
     */
    protected function shouldIgnoreError(string $errorMessage): bool
    {
        foreach ($this->ignorePatterns as $pattern) {
            if (preg_match($pattern, $errorMessage)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Normalise un message d'erreur pour regrouper les erreurs similaires
     * - Remplace les identifiants utilisateur (userId) par une constante
     * - Remplace d'autres valeurs variables comme les IDs dans les paths
     * 
     * @param string $errorMessage
     * @return string
     */
    protected function normalizeErrorMessage(string $errorMessage): string
    {
        // Normaliser les identifiants d'utilisateur
        $normalized = preg_replace('/"userId":\s*\d+/', '"userId":"[ID]"', $errorMessage);
        
        // Normaliser les IDs numériques dans les chemins de fichiers
        $normalized = preg_replace('/\/\d+\//', '/[ID]/', $normalized);
        
        // Normaliser les autres valeurs numériques spécifiques si nécessaire
        // $normalized = preg_replace(...);
        
        return $normalized;
    }
}
