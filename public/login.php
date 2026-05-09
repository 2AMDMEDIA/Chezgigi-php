<?php
require_once __DIR__ . '/src/auth.php';
require_once __DIR__ . '/src/helpers.php';

auth_start();

$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (auth_check_credentials($username, $password)) {
        auth_login($username);
        header('Location: ' . base_url('index.php'));
        exit;
    }
    $error = 'Identifiants incorrects';
}

if (auth_is_logged()) {
    header('Location: ' . base_url('index.php'));
    exit;
}
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — ChezGigi Planning</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>🏔️ ChezGigi</h1>
        <p class="subtitle">Planning de réservations</p>

        <?php if ($error): ?>
            <div class="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <label>
                Nom d'utilisateur
                <input type="text" name="username" required autofocus>
            </label>

            <label>
                Mot de passe
                <input type="password" name="password" required>
            </label>

            <button type="submit" class="btn btn-primary">Se connecter</button>
        </form>
    </div>
</body>
</html>
