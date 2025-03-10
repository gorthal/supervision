<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration générale
    |--------------------------------------------------------------------------
    |
    | Configuration générale pour le système de supervision
    |
    */

    // Email de l'administrateur qui recevra les rapports d'erreurs
    'admin_email' => env('SUPERVISION_ADMIN_EMAIL', 'admin@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Configuration de rétention des erreurs
    |--------------------------------------------------------------------------
    |
    | Définit combien de temps les erreurs sont conservées
    |
    */

    // Nombre de jours avant de purger les erreurs résolues ou ignorées
    'retention_days' => env('SUPERVISION_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Configuration des notifications
    |--------------------------------------------------------------------------
    |
    | Configuration des options de notification
    |
    */

    // Niveaux d'erreurs à inclure dans les notifications
    'notification_levels' => [
        'error' => true,
        'warning' => true,
        'info' => false,
        'debug' => false,
    ],

    // Limites pour les rapports et notifications
    'limits' => [
        'max_errors_per_report' => 100,
        'max_errors_per_project' => 20,
    ],
];
