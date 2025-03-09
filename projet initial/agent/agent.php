<?php
/**
 * Agent de surveillance pour Supervision
 * Détecte les projets Laravel et envoie les erreurs au serveur central
 */

// Chargement des configurations
if (file_exists(__DIR__ . '/.env')) {
    $config = parse_ini_file(__DIR__ . '/.env', true);
} else {
    // Rétrocompatibilité avec l'ancien fichier de configuration
    $config = parse_ini_file(__DIR__ . '/config.ini', true);
}

if (!$config) {
    echo "Erreur: Impossible de charger le fichier de configuration (.env ou config.ini)\n";
    exit(1);
}

// Vérification des configurations obligatoires
if (!isset($config['general']['root_directory']) || !isset($config['server']['api_url']) || !isset($config['server']['api_key'])) {
    echo "Erreur: Configuration incomplète\n";
    exit(1);
}

$rootDirectory = $config['general']['root_directory'];
$maxDepth = $config['general']['max_depth'] ?? 3;
$apiUrl = $config['server']['api_url'];
$apiKey = $config['server']['api_key'];
$lastRunFile = __DIR__ . '/last_run.json';

// Récupération des patterns d'erreur et des erreurs à ignorer
$errorPatterns = $config['error_patterns'] ?? [];
$ignoreErrors = [];
if (isset($config['ignore_errors']['patterns']) && is_array($config['ignore_errors']['patterns'])) {
    $ignoreErrors = $config['ignore_errors']['patterns'];
}

// Pattern par défaut pour Laravel si aucun pattern spécifique n'est fourni
if (empty($errorPatterns)) {
    $errorPatterns['laravel_error'] = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*) in (.*):(\d+)/';
}

// Fonction pour trouver tous les projets Laravel
function findLaravelProjects($directory, $maxDepth, $currentDepth = 0) {
    $projects = [];
    
    if ($currentDepth > $maxDepth) {
        return $projects;
    }
    
    $items = scandir($directory);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $path = $directory . '/' . $item;
        
        // Vérifier si c'est un projet Laravel
        if (is_dir($path) && 
            file_exists($path . '/artisan') && 
            is_dir($path . '/app') && 
            is_dir($path . '/storage/logs')) {
            $projects[] = [
                'name' => basename($path),
                'path' => $path,
                'log_path' => $path . '/storage/logs'
            ];
        } elseif (is_dir($path)) {
            // Recherche récursive
            $subProjects = findLaravelProjects($path, $maxDepth, $currentDepth + 1);
            $projects = array_merge($projects, $subProjects);
        }
    }
    
    return $projects;
}

// Fonction pour analyser les logs d'erreurs avec les patterns configurés
function parseErrorLogs($logPath, $lastRunData, $errorPatterns, $ignoreErrors) {
    $errors = [];
    $lastChecked = $lastRunData[$logPath] ?? 0;
    $currentTime = time();
    
    $logFiles = glob($logPath . '/*.log');
    foreach ($logFiles as $logFile) {
        // Vérifier si le fichier a été modifié depuis la dernière vérification
        if (filemtime($logFile) > $lastChecked) {
            $content = file_get_contents($logFile);
            $lines = explode("\n", $content);
            
            foreach ($lines as $line) {
                // Ignorer les lignes vides
                if (empty(trim($line))) {
                    continue;
                }
                
                $errorFound = false;
                $errorType = '';
                $errorData = [];
                
                // Tester chaque pattern d'erreur
                foreach ($errorPatterns as $type => $pattern) {
                    if (preg_match($pattern, $line, $matches)) {
                        // Vérifier si le type d'erreur doit être ignoré
                        if (in_array(strtolower($type), array_map('strtolower', $ignoreErrors))) {
                            continue;
                        }
                        
                        $errorFound = true;
                        $errorType = $type;
                        $errorData = $matches;
                        break;
                    }
                }
                
                if ($errorFound) {
                    // Format standard Laravel - priorité si ce pattern est détecté
                    if ($errorType === 'laravel_error' && isset($errorData[1], $errorData[2], $errorData[3], $errorData[4])) {
                        $timestamp = strtotime($errorData[1]);
                        
                        // Vérifier si l'erreur est survenue après la dernière vérification
                        if ($timestamp > $lastChecked) {
                            // Extraire le fichier et la ligne si disponible dans le message
                            $filePath = '';
                            $lineNumber = 0;
                            
                            if (preg_match('/in (.*):(\d+)$/', $errorData[4], $fileMatches)) {
                                $filePath = $fileMatches[1];
                                $lineNumber = (int)$fileMatches[2];
                                $errorMessage = trim(str_replace(" in {$filePath}:{$lineNumber}", '', $errorData[4]));
                            } else {
                                $errorMessage = $errorData[4];
                            }
                            
                            $errors[] = [
                                'project_name' => basename(dirname($logPath)),
                                'environment' => basename($logFile, '.log'),
                                'error_message' => $errorMessage,
                                'file' => $filePath,
                                'line' => $lineNumber,
                                'level' => strtolower($errorData[3]),
                                'error_type' => $errorType,
                                'timestamp' => date('c', $timestamp)
                            ];
                        }
                    } 
                    // Autres types d'erreurs
                    else {
                        // Extraire les informations d'erreur selon le type
                        $timestamp = time(); // Par défaut, utiliser l'heure actuelle
                        $errorMessage = $line;
                        $filePath = '';
                        $lineNumber = 0;
                        $level = 'error'; // Niveau par défaut
                        
                        // Extraire l'heure si elle apparaît dans le format standard
                        if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $timeMatches)) {
                            $timestamp = strtotime($timeMatches[1]);
                        }
                        
                        // Extraire le fichier et la ligne pour les erreurs PHP standard
                        if (preg_match('/in (.+) on line (\d+)/', $line, $fileMatches)) {
                            $filePath = $fileMatches[1];
                            $lineNumber = (int)$fileMatches[2];
                        }
                        
                        // Vérifier si l'erreur est survenue après la dernière vérification
                        if ($timestamp > $lastChecked) {
                            $errors[] = [
                                'project_name' => basename(dirname($logPath)),
                                'environment' => basename($logFile, '.log'),
                                'error_message' => $errorMessage,
                                'file' => $filePath,
                                'line' => $lineNumber,
                                'level' => $level,
                                'error_type' => $errorType,
                                'timestamp' => date('c', $timestamp)
                            ];
                        }
                    }
                }
            }
        }
    }
    
    return [$errors, $currentTime];
}

// Fonction pour envoyer les erreurs au serveur central
function sendErrorsToServer($errors, $apiUrl, $apiKey) {
    if (empty($errors)) {
        return true;
    }
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($errors));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

// Charger les données de la dernière exécution
$lastRunData = [];
if (file_exists($lastRunFile)) {
    $lastRunData = json_decode(file_get_contents($lastRunFile), true) ?: [];
}

// Trouver tous les projets Laravel
$projects = findLaravelProjects($rootDirectory, $maxDepth);
echo "Projets Laravel trouvés: " . count($projects) . "\n";

// Analyser les logs et envoyer les erreurs
$newLastRunData = [];
$allErrors = [];

foreach ($projects as $project) {
    echo "Analyse des logs pour {$project['name']}...\n";
    list($errors, $currentTime) = parseErrorLogs($project['log_path'], $lastRunData, $errorPatterns, $ignoreErrors);
    
    if (!empty($errors)) {
        echo "  " . count($errors) . " nouvelles erreurs trouvées\n";
        $allErrors = array_merge($allErrors, $errors);
    }
    
    $newLastRunData[$project['log_path']] = $currentTime;
}

// Envoyer les erreurs au serveur central
if (!empty($allErrors)) {
    echo "Envoi de " . count($allErrors) . " erreurs au serveur central...\n";
    $success = sendErrorsToServer($allErrors, $apiUrl, $apiKey);
    echo $success ? "Envoi réussi\n" : "Erreur lors de l'envoi\n";
}

// Sauvegarder les données de la dernière exécution
file_put_contents($lastRunFile, json_encode($newLastRunData));
echo "Terminé\n";
