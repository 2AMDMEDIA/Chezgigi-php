<?php
/**
 * Configuration ChezGigi
 *
 * Copiez ce fichier en `config.php` et adaptez les valeurs.
 * Le fichier `config.php` est ignoré par git pour ne pas committer les credentials.
 */

return [
    // Base de données MySQL
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'chezgigi',          // Nom de la base
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    // Identifiants de connexion à l'application
    'auth' => [
        'username' => 'gigi',
        'password' => 'tintin',
    ],

    // Fuseau horaire (pour les dates)
    'timezone' => 'Europe/Paris',

    // Mode debug (affiche les erreurs PHP) - METTRE FALSE EN PRODUCTION
    'debug' => false,
];
