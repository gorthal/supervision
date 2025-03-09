# Guide d'installation

Ce document décrit les différentes façons d'installer et de configurer le système de supervision.

## Installation du serveur central

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL ou PostgreSQL
- Serveur web (Nginx, Apache)

### Installation manuelle

1. Clonez le dépôt GitHub :
   ```
   git clone https://github.com/gorthal/supervision.git
   cd supervision
   ```

2. Installez les dépendances :
   ```
   composer install --no-dev --optimize-autoloader
   ```

3. Configurez l'environnement :
   ```
   cp .env.example .env
   php artisan key:generate
   ```

4. Modifiez le fichier `.env` avec vos informations de base de données et de serveur SMTP.

5. Créez la base de données et lancez les migrations :
   ```
   php artisan migrate
   ```

6. Créez un utilisateur administrateur :
   ```
   php artisan make:filament-user
   ```

7. Optimisez l'application pour la production :
   ```
   php artisan optimize
   php artisan route:cache
   php artisan view:cache
   ```

8. Configurez le planificateur de tâches dans le crontab :
   ```
   * * * * * cd /chemin/vers/supervision && php artisan schedule:run >> /dev/null 2>&1
   ```

### Installation avec Docker

1. Clonez le dépôt GitHub :
   ```
   git clone https://github.com/gorthal/supervision.git
   cd supervision
   ```

2. Créez un fichier `.env` à partir du modèle :
   ```
   cp .env.example .env
   ```

3. Modifiez les variables d'environnement si nécessaire.

4. Lancez l'environnement Docker :
   ```
   docker-compose up -d
   ```

5. Exécutez les migrations et créez un utilisateur :
   ```
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan make:filament-user
   ```

6. Accédez à l'application à l'adresse `http://localhost:8000/admin`.

## Installation de l'agent de surveillance

L'agent de surveillance est un simple script PHP qui doit être installé sur chaque serveur contenant des projets Laravel à surveiller.

### Installation manuelle

1. Copiez le dossier `agent` sur votre serveur.

2. Configurez le fichier `config.ini` :
   ```ini
   [general]
   root_directory = "/var/www"  ; Répertoire racine de la recherche
   max_depth = 3                ; Profondeur maximale de recherche

   [server]
   api_url = "https://votre-serveur.com/api/logs"  ; URL de votre serveur central
   api_key = "VOTRE_CLE_API"    ; Clé API générée dans l'interface d'administration
   ```

3. Testez l'agent manuellement :
   ```
   php /chemin/vers/agent/agent.php
   ```

4. Ajoutez une tâche CRON pour l'exécuter régulièrement :
   ```
   * * * * * php /chemin/vers/agent/agent.php
   ```

## Configuration

### Configuration du serveur central

Après l'installation, vous devez créer au moins un projet dans l'interface d'administration. Pour chaque projet :

1. Connectez-vous à l'interface d'administration.
2. Allez dans "Projets" > "Ajouter un projet".
3. Remplissez les informations du projet (nom, slug).
4. Notez la clé API générée, vous en aurez besoin pour configurer l'agent.
5. Configurez les paramètres de notification si nécessaire.

### Configuration de l'agent

Modifiez le fichier `config.ini` de l'agent avec :

1. Le chemin vers les projets Laravel à surveiller.
2. L'URL de votre serveur central.
3. La clé API du projet.

## Dépannage

### Problèmes courants

- **Les erreurs ne sont pas détectées** : Vérifiez que l'agent est bien configuré et que la tâche CRON fonctionne.
- **Les notifications ne sont pas envoyées** : Vérifiez la configuration SMTP dans le fichier `.env`.
- **Erreur d'accès refusé** : Vérifiez les permissions des fichiers et dossiers.

### Logs

Les logs de l'application sont disponibles dans :
- `/var/www/supervision/storage/logs/laravel.log` pour le serveur central
- Les logs de l'agent sont affichés en sortie standard et peuvent être redirigés vers un fichier si nécessaire.

## Mise à jour

Pour mettre à jour le système :

1. Récupérez les dernières modifications :
   ```
   git pull origin main
   ```

2. Mettez à jour les dépendances :
   ```
   composer install --no-dev --optimize-autoloader
   ```

3. Exécutez les migrations si nécessaire :
   ```
   php artisan migrate
   ```

4. Optimisez l'application :
   ```
   php artisan optimize
   ```

5. Mettez à jour l'agent sur chaque serveur en copiant les nouveaux fichiers.
