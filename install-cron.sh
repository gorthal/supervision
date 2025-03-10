#!/bin/bash

# Script pour installer le cron de rapport d'erreurs horaire
# Usage: ./install-cron.sh [email]

# Configuration
PROJECT_PATH="/var/www/html"
LOG_FILE="/var/log/supervision-cron.log"
PHP_PATH=$(which php)
EMAIL=${1:-"admin@example.com"}

# Vérifier que le script est exécuté en tant que root ou avec sudo
if [ "$EUID" -ne 0 ]; then
  echo "Ce script doit être exécuté en tant que root ou avec sudo"
  exit 1
fi

# Créer le fichier log s'il n'existe pas
touch $LOG_FILE
chown www-data:www-data $LOG_FILE
chmod 644 $LOG_FILE

# Créer l'entrée crontab
CRON_ENTRY="0 * * * * cd $PROJECT_PATH && $PHP_PATH artisan supervision:send-hourly-error-report $EMAIL --period=24hours >> $LOG_FILE 2>&1"

# Installer le cron pour www-data
(crontab -u www-data -l 2>/dev/null || echo "") | grep -v "supervision:send-hourly-error-report" | (cat; echo "$CRON_ENTRY") | crontab -u www-data -

echo "==========================================="
echo "Installation du cron terminée !"
echo "Le rapport d'erreurs sera envoyé à : $EMAIL"
echo "Le cron s'exécutera toutes les heures à 0 minutes"
echo "Le rapport contiendra toutes les erreurs des dernières 24 heures"
echo "Les erreurs de la dernière heure seront mises en évidence"
echo "Les logs seront écrits dans : $LOG_FILE"
echo ""
echo "Pour tester immédiatement, exécutez :"
echo "sudo -u www-data $PHP_PATH $PROJECT_PATH/artisan supervision:send-hourly-error-report $EMAIL --period=24hours"
echo "==========================================="
