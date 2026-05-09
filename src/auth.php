<?php
/**
 * Authentification basée sur les sessions PHP.
 */

require_once __DIR__ . '/db.php';

function auth_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Cookie session 30 jours, sécurisé en HTTPS
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function auth_check_credentials(string $username, string $password): bool
{
    $config = config();
    $expectedUser = $config['auth']['username'] ?? 'gigi';
    $expectedPass = $config['auth']['password'] ?? 'tintin';

    return hash_equals($expectedUser, $username)
        && hash_equals($expectedPass, $password);
}

function auth_login(string $username): void
{
    auth_start();
    session_regenerate_id(true);
    $_SESSION['user']    = $username;
    $_SESSION['logged']  = true;
    $_SESSION['since']   = time();
}

function auth_logout(): void
{
    auth_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function auth_is_logged(): bool
{
    auth_start();
    return !empty($_SESSION['logged']);
}

/**
 * Protège une page : redirige vers /login.php si non connecté.
 */
function auth_require(): void
{
    if (!auth_is_logged()) {
        // Pour les API, renvoie 401 JSON
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function base_url(string $path = ''): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // /public/api/foo.php -> /public/
    $base = preg_replace('#/(api/|)[^/]*$#', '/', $scriptName);
    if ($path === '') return $base;
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
