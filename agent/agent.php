<?php
/**
 * Agent de surveillance pour Supervision
 * Détecte les projets Laravel et envoie les erreurs au serveur central
 */

// Chargement des configurations
$config = parse_ini_file(__DIR__ . '/config.ini', true);
if (!$config) {
    echo "Erreur: Impossible de charger le fichier config.ini\n";
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

// Fonction pour analyser les logs d'erreurs
function parseErrorLogs($logPath, $lastRunData) {
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
                // Détecter les lignes d'erreur (format Laravel)
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*) in (.*):(\d+)/', $line, $matches)) {
                    $timestamp = strtotime($matches[1]);
                    
                    // Ignorer les erreurs avant la dernière exécution
                    if ($timestamp > $lastChecked) {
                        $errors[] = [
                            'project_name' => basename(dirname($logPath)),
                            'environment' => basename($logFile, '.log'),
                            'error_message' => $matches[4],
                            'file' => $matches[5],
                            'line' => (int)$matches[6],
                            'level' => strtolower($matches[3]),
                            'timestamp' => date('c', $timestamp)
                        ];
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
    list($errors, $currentTime) = parseErrorLogs($project['log_path'], $lastRunData);
    
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
