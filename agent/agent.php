<?php
/**
 * Agent de surveillance pour Supervision
 * Détecte les projets Laravel et envoie les erreurs au serveur central
 */

// Chargement des configurations
if (file_exists(__DIR__ . '/.env')) {
    $config = parse_ini_file(__DIR__ . '/.env', true);
}
else {
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
$maxDepth      = $config['general']['max_depth'] ?? 3;
$apiUrl        = $config['server']['api_url'];
$apiKey        = $config['server']['api_key'];
$lastRunFile   = __DIR__ . '/last_run.json';

// Récupération des patterns d'erreur et des erreurs à ignorer
$errorPatterns = $config['error_patterns'] ?? [];
$ignoreErrors  = [];
if (isset($config['ignore_errors']['patterns']) && is_array($config['ignore_errors']['patterns'])) {
    $ignoreErrors = $config['ignore_errors']['patterns'];
}

// Patterns d'erreurs non pertinentes à filtrer
$nonEssentialPatterns = [
    '/^\s*View \#\d+ \/var\/www\/html\/.*\/artisan\(\d+\)\: .*->handle\(\)$/',
    '/^\s*View \#\d+ \{main\}$/',
    '/Memcached::getMulti\(\): Server .* failed with: Connection refused/',
    '/Resource temporarily unavailable/'
];

// Ajout des patterns non essentiels au tableau d'erreurs à ignorer
if (isset($config['ignore_errors']['patterns']) && is_array($config['ignore_errors']['patterns'])) {
    $ignoreErrors = array_merge($ignoreErrors, $nonEssentialPatterns);
} else {
    $ignoreErrors = $nonEssentialPatterns;
}

// Pattern par défaut pour Laravel si aucun pattern spécifique n'est fourni
if (empty($errorPatterns)) {
    $errorPatterns['laravel_error'] = '/\\[(\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2})\\] (\\w+)\\.(\\w+): (.*) in (.*):(\\d+)/';
}

// Fonction pour trouver tous les projets Laravel
function findLaravelProjects($directory, $maxDepth, $currentDepth = 0)
{
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
        if (
            is_dir($path) &&
            file_exists($path . '/artisan') &&
            is_dir($path . '/app') &&
            is_dir($path . '/storage/logs')
        ) {
            $projects[] = [
                'name'     => basename($path),
                'path'     => $path,
                'log_path' => $path . '/storage/logs'
            ];
        }
        elseif (is_dir($path)) {
            // Recherche récursive
            $subProjects = findLaravelProjects($path, $maxDepth, $currentDepth + 1);
            $projects    = array_merge($projects, $subProjects);
        }
    }

    return $projects;
}

/**
 * Normaliser le message d'erreur pour regrouper les erreurs similaires
 * @param string $errorMessage
 * @return string
 */
function normalizeErrorMessage($errorMessage)
{
    // Normaliser les identifiants d'utilisateur
    $normalized = preg_replace('/"userId":\s*\d+/', '"userId":"[ID]"', $errorMessage);
    
    // Normaliser les IDs numériques dans les chemins de fichiers
    $normalized = preg_replace('/\/\d+\//', '/[ID]/', $normalized);
    
    return $normalized;
}

/**
 * Vérifie si un message d'erreur doit être ignoré
 * @param string $errorMessage
 * @param array $ignorePatterns
 * @return bool
 */
function shouldIgnoreError($errorMessage, $ignorePatterns)
{
    foreach ($ignorePatterns as $pattern) {
        if (preg_match($pattern, $errorMessage)) {
            return true;
        }
    }
    
    return false;
}

// Fonction pour analyser les logs d'erreurs avec les patterns configurés
function parseErrorLogs($logPath, $lastRunData, $errorPatterns, $ignoreErrors)
{
    $errors      = [];
    $lastChecked = $lastRunData[$logPath] ?? 0;
    $currentTime = time();
    $ignoredCount = 0;

    $logFiles = glob($logPath . '/*.log');
    foreach ($logFiles as $logFile) {
        // Vérifier si le fichier a été modifié depuis la dernière vérification
        if (filemtime($logFile) > $lastChecked) {
            $content = file_get_contents($logFile);
            $lines   = explode("\n", $content);

            foreach ($lines as $line) {
                // Ignorer les lignes vides
                if (empty(trim($line))) {
                    continue;
                }

                // Vérifier si le message correspond à un pattern à ignorer
                if (shouldIgnoreError($line, $ignoreErrors)) {
                    $ignoredCount++;
                    continue;
                }

                $errorFound = false;
                $errorType  = '';
                $errorData  = [];

                // Tester chaque pattern d'erreur
                foreach ($errorPatterns as $type => $pattern) {
                    if (preg_match($pattern, $line, $matches)) {
                        // Vérifier si le type d'erreur doit être ignoré
                        if (in_array(strtolower($type), array_map('strtolower', $ignoreErrors))) {
                            continue;
                        }

                        $errorFound = true;
                        $errorType  = $type;
                        $errorData  = $matches;
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
                            $filePath   = '';
                            $lineNumber = 0;

                            if (preg_match('/in (.*):(\\d+)$/', $errorData[4], $fileMatches)) {
                                $filePath     = $fileMatches[1];
                                $lineNumber   = (int) $fileMatches[2];
                                $errorMessage = trim(str_replace(" in {$filePath}:{$lineNumber}", '', $errorData[4]));
                            }
                            else {
                                $errorMessage = $errorData[4];
                            }
                            
                            // Normaliser le message d'erreur pour regrouper des erreurs similaires
                            $errorMessage = normalizeErrorMessage($errorMessage);

                            $errors[] = [
                                'project_name'  => basename(dirname($logPath)),
                                'environment'   => basename($logFile, '.log'),
                                'error_message' => $errorMessage,
                                'file'          => $filePath,
                                'line'          => $lineNumber,
                                'level'         => strtolower($errorData[3]),
                                'error_type'    => $errorType,
                                'timestamp'     => date('c', $timestamp)
                            ];
                        }
                    }
                    // Autres types d'erreurs
                    else {
                        // Extraire les informations d'erreur selon le type
                        $timestamp    = time(); // Par défaut, utiliser l'heure actuelle
                        $errorMessage = $line;
                        $filePath     = '';
                        $lineNumber   = 0;
                        $level        = 'error'; // Niveau par défaut

                        // Extraire l'heure si elle apparaît dans le format standard
                        if (preg_match('/\\[(\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2})\\]/', $line, $timeMatches)) {
                            $timestamp = strtotime($timeMatches[1]);
                        }

                        // Extraire le fichier et la ligne pour les erreurs PHP standard
                        if (preg_match('/in (.+) on line (\\d+)/', $line, $fileMatches)) {
                            $filePath   = $fileMatches[1];
                            $lineNumber = (int) $fileMatches[2];
                        }
                        
                        // Normaliser le message d'erreur
                        $errorMessage = normalizeErrorMessage($errorMessage);

                        // Vérifier si l'erreur est survenue après la dernière vérification
                        if ($timestamp > $lastChecked) {
                            $errors[] = [
                                'project_name'  => basename(dirname($logPath)),
                                'environment'   => basename($logFile, '.log'),
                                'error_message' => $errorMessage,
                                'file'          => $filePath,
                                'line'          => $lineNumber,
                                'level'         => $level,
                                'error_type'    => $errorType,
                                'timestamp'     => date('c', $timestamp)
                            ];
                        }
                    }
                }
            }
        }
    }
    
    if ($ignoredCount > 0) {
        echo "  $ignoredCount lignes de log filtrées comme non pertinentes\n";
    }

    return [$errors, $currentTime, $ignoredCount];
}

// Fonction pour envoyer les erreurs au serveur central
function sendErrorsToServer($errors, $apiUrl, $apiKey)
{
    if (empty($errors)) {
        return true;
    }

    // Vérification préliminaire que l'URL est valide
    if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
        echo "Erreur: URL invalide: {$apiUrl}\n";
        return false;
    }

    // Essayer différentes variations d'URL
    $urlVariations = [
        $apiUrl, // URL originale fournie dans la config
    ];

    // 1. Essayer avec /api/logs
    if (!preg_match('#/api/logs$#', $apiUrl)) {
        if (substr($apiUrl, -1) === '/') {
            $urlVariations[] = $apiUrl . 'api/logs';
        }
        else {
            $urlVariations[] = $apiUrl . '/api/logs';
        }
    }

    // 2. Essayer sans /api (au cas où le préfixe est déjà inclus dans l'URL de base)
    if (preg_match('#/api/logs$#', $apiUrl)) {
        $urlVariations[] = preg_replace('#/api/logs$#', '/logs', $apiUrl);
    }
    else if (!preg_match('#/logs$#', $apiUrl)) {
        if (substr($apiUrl, -1) === '/') {
            $urlVariations[] = $apiUrl . 'logs';
        }
        else {
            $urlVariations[] = $apiUrl . '/logs';
        }
    }

    // 3. Essayer l'URL de base pure sans aucun chemin
    $parsedUrl = parse_url($apiUrl);
    $baseUrl   = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    if (isset($parsedUrl['port'])) {
        $baseUrl .= ':' . $parsedUrl['port'];
    }
    $urlVariations[] = $baseUrl . '/api/logs';

    // Supprimer les doublons
    $urlVariations = array_unique($urlVariations);

    // Préparation du payload JSON et vérification
    $jsonPayload = json_encode($errors);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Erreur: Impossible d'encoder les données en JSON: " . json_last_error_msg() . "\n";
        return false;
    }

    // Vérifier les données à envoyer
    foreach ($errors as &$error) {
        // S'assurer que les champs requis sont présents
        if (empty($error['file'])) {
            $error['file'] = 'unknown';
        }
        if (empty($error['line']) || !is_numeric($error['line'])) {
            $error['line'] = 0;
        }
    }

    // Essayer chaque variation d'URL
    $successFound = false;

    echo "Tentative d'envoi avec plusieurs variantes d'URL...\n";

    foreach ($urlVariations as $testUrl) {
        echo "Test avec l'URL: {$testUrl}\n";

        // Initialisation de CURL
        $ch = curl_init($testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'Content-Length: ' . strlen($jsonPayload),
            'Accept: application/json'
        ]);

        // Options additionnelles
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        // Exécuter la requête
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Vérifier s'il y a une erreur CURL
        if ($response === false) {
            $curlError   = curl_error($ch);
            $curlErrorNo = curl_errno($ch);
            echo "  Erreur CURL: [{$curlErrorNo}] {$curlError}\n";
            curl_close($ch);
            continue; // Essayer la prochaine URL
        }

        // Vérifier le code HTTP
        if ($httpCode < 200 || $httpCode >= 300) {
            echo "  Erreur HTTP: Code {$httpCode}\n";
            if ($httpCode == 404) {
                echo "  URL non trouvée, essai de la prochaine variante...\n";
                curl_close($ch);
                continue; // Essayer la prochaine URL
            }
            echo "  Réponse du serveur: " . substr($response, 0, 500) . "\n";
            curl_close($ch);
            continue; // Essayer la prochaine URL
        }

        // Succès ! Tenter de décoder la réponse JSON
        $decodedResponse = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($decodedResponse['status'])) {
            echo "  Statut de la réponse: {$decodedResponse['status']}\n";
            if (isset($decodedResponse['message'])) {
                echo "  Message: {$decodedResponse['message']}\n";
            }
        }
        else {
            echo "  Réponse brute du serveur: " . substr($response, 0, 500) . "\n";
        }

        curl_close($ch);
        $successFound = true;

        // Sauvegarder l'URL qui fonctionne pour les prochaines exécutions
        if ($testUrl !== $apiUrl) {
            echo "URL fonctionnelle trouvée! Mise à jour recommandée du fichier de configuration.\n";
            echo "Remplacez l'URL actuelle par: {$testUrl}\n";
        }

        break; // Sortir de la boucle, nous avons trouvé une URL qui fonctionne
    }

    return $successFound;
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
$allErrors      = [];
$totalIgnoredCount = 0;

foreach ($projects as $project) {
    echo "Analyse des logs pour {$project['name']}...\n";
    list($errors, $currentTime, $ignoredCount) = parseErrorLogs($project['log_path'], $lastRunData, $errorPatterns, $ignoreErrors);
    
    $totalIgnoredCount += $ignoredCount;

    if (!empty($errors)) {
        echo "  " . count($errors) . " nouvelles erreurs trouvées\n";
        $allErrors = array_merge($allErrors, $errors);
    }

    $newLastRunData[$project['log_path']] = $currentTime;
}

// Afficher le total des erreurs ignorées
if ($totalIgnoredCount > 0) {
    echo "Total des messages d'erreur filtrés: $totalIgnoredCount\n";
}

// Envoyer les erreurs au serveur central
if (!empty($allErrors)) {
    echo "Envoi de " . count($allErrors) . " erreurs au serveur central...\n";
    echo "URL API configurée: " . $apiUrl . "\n";

    // Afficher un aperçu des données à envoyer
    $firstError = reset($allErrors);
    echo "Exemple d'erreur à envoyer: Projet={$firstError['project_name']}, Message={$firstError['error_message']}\n";

    $success = sendErrorsToServer($allErrors, $apiUrl, $apiKey);
    echo $success ? "Envoi réussi\n" : "Erreur lors de l'envoi avec toutes les variantes d'URL\n";
}

// Sauvegarder les données de la dernière exécution
file_put_contents($lastRunFile, json_encode($newLastRunData));
echo "Terminé\n";
