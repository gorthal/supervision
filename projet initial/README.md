# Supervision - Système de suivi des erreurs pour Laravel

Une alternative légère et auto-hébergée à Sentry/Highlight.io pour centraliser et suivre les erreurs générées par plusieurs projets Laravel.

![Version](https://img.shields.io/badge/version-0.1.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)
![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)

## Fonctionnalités

- Détection automatique des projets Laravel sur un serveur
- Centralisation des erreurs de plusieurs projets
- Tableau de bord pour suivre les erreurs en temps réel
- Tri et filtrage des erreurs par niveau, projet, statut
- Gestion des statuts d'erreurs (nouveau, en cours, résolu, ignoré)
- Alertes par email (temps réel, résumé horaire ou quotidien)
- Interface moderne et réactive avec Filament

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

## Captures d'écran

*À venir...*

## Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL ou PostgreSQL
- Serveur web (Nginx, Apache)

## Installation rapide

### Agent de surveillance

1. Copiez le dossier `agent` sur votre serveur 
2. Configurez le fichier `config.ini`
3. Ajoutez une tâche CRON pour l'exécuter régulièrement:
   ```
   * * * * * php /path/to/agent/agent.php
   ```

### Serveur central

1. Clonez ce dépôt
   ```
   git clone https://github.com/gorthal/supervision.git
   cd supervision
   ```

2. Installez les dépendances
   ```
   composer install
   ```

3. Configurez le fichier `.env`
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Configurez votre base de données dans le fichier `.env`

5. Lancez les migrations
   ```
   php artisan migrate
   ```

6. Créez un utilisateur administrateur
   ```
   php artisan make:filament-user
   ```

7. Démarrez le serveur
   ```
   php artisan serve
   ```

8. Accédez à l'interface d'administration à l'adresse `http://localhost:8000/admin`

## Documentation

Consultez le [Wiki](https://github.com/gorthal/supervision/wiki) pour une documentation complète.

## Développement

### Stack technique

- Agent: PHP 7.4+
- Serveur central: Laravel 11, MySQL/PostgreSQL
- Frontend: TailwindCSS, Livewire, PhpFilament

### Contribution

Les contributions sont les bienvenues ! Consultez les [issues](https://github.com/gorthal/supervision/issues) pour voir les fonctionnalités à implémenter.

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## À venir

- Intégration avec Slack/Discord
- Graphiques d'erreurs par jour/semaine
- Système de priorisation des erreurs
- Détection automatique des projets via Composer
- Plus de fonctionnalités de filtrage
