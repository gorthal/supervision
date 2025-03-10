# Système de Supervision

Un outil de supervision de logs basé sur des agents distants et un tableau de bord.

## Fonctionnalités

- Détection automatique des projets Laravel
- Analyse des fichiers de logs et détection des erreurs
- Tableau de bord centralisé pour visualiser toutes les erreurs
- Classification des erreurs par sévérité, projet, environnement
- Notifications par email pour les nouvelles erreurs
- Rapports horaires et quotidiens des erreurs

## Structure du projet

- **agent/** - Agent à installer sur les serveurs à surveiller
- **app/** - Application Laravel principale (tableau de bord)
- **config/** - Fichiers de configuration

## Installation du tableau de bord

1. Cloner le dépôt
2. Installer les dépendances
   ```bash
   composer install
   npm install
   ```
3. Configurer la base de données dans `.env`
4. Exécuter les migrations
   ```bash
   php artisan migrate
   ```
5. Compiler les assets
   ```bash
   npm run build
   ```

## Configuration de l'agent

1. Copier le dossier `agent/` sur le serveur à surveiller
2. Créer un fichier `.env` dans le dossier de l'agent avec ces paramètres :
   ```
   [general]
   root_directory=/chemin/vers/projets
   max_depth=3

   [server]
   api_url=https://votreserveur.com/api/logs
   api_key=VOTRE_API_KEY
   ```
3. Exécuter l'agent périodiquement via cron :
   ```
   */5 * * * * cd /chemin/vers/agent && php agent.php >> /var/log/supervision-agent.log 2>&1
   ```

## Configuration des rapports par email

### Configuration système

1. Assurez-vous que votre configuration email Laravel est correcte dans le fichier `.env`.
2. Configurez l'adresse email d'administration qui recevra les rapports :
   ```
   SUPERVISION_ADMIN_EMAIL=votre@email.com
   ```

### Installation automatique du cron

Un script d'installation automatique est inclus. Pour l'installer :

```bash
sudo chmod +x install-cron.sh
sudo ./install-cron.sh votre@email.com
```

Ce script configurera un cron pour envoyer un rapport d'erreurs toutes les heures à l'adresse email spécifiée.

### Installation manuelle du cron

Si vous préférez configurer manuellement le cron :

```bash
crontab -e
```

Puis ajoutez la ligne suivante :

```
0 * * * * cd /var/www/html && php artisan supervision:send-hourly-error-report votre@email.com >> /var/log/supervision-cron.log 2>&1
```

### Personnalisation des rapports

Vous pouvez spécifier la période du rapport avec l'option `--period` :

```bash
# Pour un rapport des 6 dernières heures
php artisan supervision:send-hourly-error-report votre@email.com --period=6hours

# Périodes disponibles: 1hour (défaut), 6hours, 12hours, 24hours
```

## Licences et contributions

Ce projet est sous licence MIT. Les contributions sont les bienvenues.
