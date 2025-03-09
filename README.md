# Supervision - Système de suivi des erreurs pour Laravel

Une alternative légère et auto-hébergée à Sentry/Highlight.io pour centraliser et suivre les erreurs générées par plusieurs projets Laravel.

## Architecture du système

Le système est composé de deux parties :

1. **Agent de surveillance** - Script PHP déployé sur chaque serveur
   - Détecte automatiquement les projets Laravel
   - Analyse les logs d'erreurs
   - Envoie les nouvelles erreurs au serveur central

2. **Serveur central** - Application Laravel
   - Tableau de bord pour suivre les erreurs
   - Gestion des statuts (traité, ignoré, etc.)
   - Vue détaillée des erreurs par projet
   - Envoi d'alertes par email

## Installation

### Agent de surveillance

1. Copiez le dossier `agent` sur votre serveur 
2. Configurez le fichier `config.ini`
3. Ajoutez une tâche CRON pour l'exécuter régulièrement:
   ```
   * * * * * php /path/to/agent/agent.php
   ```

### Serveur central

1. Clonez ce dépôt
2. Installez les dépendances: `composer install`
3. Configurez le fichier `.env`
4. Lancez les migrations: `php artisan migrate`
5. Démarrez le serveur: `php artisan serve`

## Stack technique

- Agent: PHP 7.4+
- Serveur central: Laravel 11, MySQL/PostgreSQL
- Frontend: TailwindCSS, Livewire, PhpFilament
