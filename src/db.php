<?php
/**
 * Connexion PDO à MySQL — singleton
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    // Cherche config.php d'abord à côté de src/, sinon dans /public/ (structure dev locale)
    $candidates = [__DIR__ . '/../config.php', __DIR__ . '/../public/config.php'];
    $configFile = null;
    foreach ($candidates as $c) {
        if (file_exists($c)) { $configFile = $c; break; }
    }
    if (!$configFile) {
        http_response_code(500);
        die('Erreur : config.php manquant. Copiez config.example.php en config.php et configurez-le.');
    }
    $config = require $configFile;
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        $db['port'] ?? 3306,
        $db['name'],
        $db['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die('Erreur de connexion à la base : ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}

function config(): array
{
    static $config = null;
    if ($config === null) {
        $candidates = [__DIR__ . '/../config.php', __DIR__ . '/../public/config.php'];
        foreach ($candidates as $c) {
            if (file_exists($c)) { $config = require $c; return $config; }
        }
    }
    return $config ?? [];
}
