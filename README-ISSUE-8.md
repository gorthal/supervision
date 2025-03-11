# Correction de l'issue #8 - Envoi des digests horaires

## Problème initial

La commande `php supervision:send-hourly-digests` n'envoyait pas de mail.

## Analyse des causes

Après analyse du code, plusieurs problèmes ont été identifiés :

1. La classe `ErrorDigestNotification` était utilisée dans `NotificationService` mais n'était pas importée
2. Le modèle `NotificationSetting` n'avait pas la méthode `shouldNotifyForLevel` utilisée dans `NotificationService`
3. Le nom de la colonne était incorrect dans la requête : `frequency` au lieu de `notification_frequency`
4. Aucune vérification de l'état actif des paramètres de notification (`is_active`)

## Corrections apportées

1. Ajout de la méthode `shouldNotifyForLevel` au modèle `NotificationSetting`
2. Ajout de l'import de la classe `ErrorDigestNotification` dans le service de notification
3. Correction du nom de colonne `notification_frequency` dans les requêtes SQL
4. Ajout d'une condition sur `is_active` pour s'assurer que seuls les paramètres actifs sont utilisés
5. Ajout de journalisation supplémentaire pour faciliter le débogage
6. Création d'une commande de test `supervision:test-hourly-digests` pour tester la fonctionnalité avec plus de détails

## Comment tester

Vous pouvez maintenant tester l'envoi de mails avec les commandes suivantes :

```bash
# Commande standard
php artisan supervision:send-hourly-digests

# Commande de test avec plus de détails
php artisan supervision:test-hourly-digests
```

## Conseils d'utilisation

1. Assurez-vous d'avoir au moins un paramètre de notification avec :
   - `notification_frequency` réglé sur "hourly" 
   - `is_active` réglé sur "true"
   - Au moins une des options `notify_on_error`, `notify_on_warning` ou `notify_on_info` activée
   - Un email valide

2. Vérifiez que votre configuration d'email Laravel est correctement configurée dans votre fichier `.env` :
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=your-smtp-server
   MAIL_PORT=587
   MAIL_USERNAME=your-username
   MAIL_PASSWORD=your-password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=supervision@example.com
   MAIL_FROM_NAME="Supervision"
   ```

3. Si vous n'avez pas de serveur SMTP réel, vous pouvez utiliser Mailpit ou Mailtrap pour tester :
   ```
   MAIL_MAILER=smtp
   MAIL_HOST=mailpit
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   ```
