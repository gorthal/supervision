# Configuration avancée de l'agent

Ce document détaille les options de configuration avancées disponibles pour l'agent de surveillance.

## Configuration de base

Le fichier de configuration `.env` de l'agent contient les sections suivantes :

```ini
[general]
root_directory = "/var/www"  ; Répertoire racine de la recherche
max_depth = 3                ; Profondeur maximale de recherche

[server]
api_url = "https://supervision.example.com/api/logs"  ; URL du programme central
api_key = "VOTRE_CLE_API"   ; Clé d'authentification
```

## Patterns de détection d'erreurs

L'agent peut être configuré pour détecter différents types d'erreurs en utilisant des expressions régulières. Par défaut, il détecte le format standard des erreurs Laravel, mais vous pouvez ajouter d'autres patterns pour une détection plus précise.

```ini
[error_patterns]
; Patterns regex pour la détection d'erreurs
exception = "/exception \'([^\']+)\' with message \'([^\']+)\'/i"
fatal_error = "/PHP Fatal error:(.+)in (.+) on line (\d+)/i"
parse_error = "/PHP Parse error:(.+)in (.+) on line (\d+)/i"
warning = "/PHP Warning:(.+)in (.+) on line (\d+)/i"
laravel_error = "/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/i"
sql_error = "/SQLSTATE\[(.+)\](.+)/i"
```

### Patterns recommandés

Voici une liste complète des patterns que vous pouvez utiliser :

| Type | Description | Pattern |
|------|-------------|---------|
| exception | Exceptions PHP standard | `/exception \'([^\']+)\' with message \'([^\']+)\'/i` |
| fatal_error | Erreurs fatales PHP | `/PHP Fatal error:(.+)in (.+) on line (\d+)/i` |
| parse_error | Erreurs de syntaxe PHP | `/PHP Parse error:(.+)in (.+) on line (\d+)/i` |
| warning | Avertissements PHP | `/PHP Warning:(.+)in (.+) on line (\d+)/i` |
| deprecated | Fonctionnalités dépréciées | `/PHP Deprecated:(.+)in (.+) on line (\d+)/i` |
| stack_trace | Traces de la pile d'appels | `/\[stacktrace\]|\[previous exception\]|\#\d+ /i` |
| laravel_error | Erreurs au format Laravel | `/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/i` |
| sql_error | Erreurs SQL | `/SQLSTATE\[(.+)\](.+)/i` |
| uncaught_exception | Exceptions non interceptées | `/Uncaught exception: (.+)/i` |
| composer_error | Erreurs Composer | `/Composer (error|exception): (.+)/i` |
| artisan_error | Erreurs des commandes Artisan | `/Artisan command failed: (.+)/i` |
| route_error | Erreurs de routes Laravel | `/Route (\[.*\]) not defined/i` |
| cron_failure | Échecs des tâches Cron | `/Cron job failed: (.+)/i` |
| disk_space | Alertes d'espace disque | `/Disk space is running low|Not enough disk space/i` |
| mail_failure | Échecs d'envoi d'emails | `/Failed to send email: (.+)/i` |
| cache_error | Erreurs de cache | `/Cache (write|read) failure: (.+)/i` |
| gateway_timeout | Timeouts de passerelle | `/Gateway timeout|504 Gateway Timeout/i` |
| memory_limit | Limites de mémoire atteintes | `/Allowed memory size of (\d+) bytes exhausted/i` |
| permission_denied | Erreurs de permission | `/Permission denied|Access denied/i` |

## Ignorer des types d'erreurs

Vous pouvez configurer l'agent pour ignorer certains types d'erreurs. Par exemple, vous pourriez vouloir ignorer les notices PHP ou les avertissements de dépréciation :

```ini
[ignore_errors]
; Types d'erreurs à ignorer
patterns[] = "notice"
patterns[] = "deprecated"
```

## Personnalisation

Vous pouvez ajouter vos propres patterns personnalisés en fonction des erreurs spécifiques que vous souhaitez détecter dans vos applications. Par exemple, pour détecter des erreurs dans un module personnalisé :

```ini
[error_patterns]
; Autres patterns...
custom_module_error = "/MyCustomModule Error: (.+)/i"
```

## Priorité de détection

L'agent essaie chaque pattern dans l'ordre où ils sont définis dans le fichier de configuration. Dès qu'un pattern correspond, il classifie l'erreur selon ce type. Vous pouvez donc influencer la priorité de détection en réorganisant l'ordre des patterns dans le fichier de configuration.
