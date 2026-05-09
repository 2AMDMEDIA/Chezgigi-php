<?php
require_once __DIR__ . '/src/auth.php';
auth_require();
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ChezGigi — Paramètres</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">🏔️ Mon Planning</div>
        <div class="nav-links">
            <a href="index.php">Calendrier</a>
            <a href="settings.php" class="active">Paramètres</a>
            <a href="logout.php" class="logout">Déconnexion</a>
        </div>
    </nav>

    <main class="container">
        <h1>Paramètres</h1>
        <h2>Sources iCal</h2>
        <p class="hint">
            Ajoute les liens iCal de tes annonces Airbnb et Booking pour synchroniser
            automatiquement les réservations.
        </p>

        <div class="card">
            <h3>Ajouter une source</h3>
            <div class="form-grid-2">
                <label>Nom (Airbnb, Booking, …)
                    <input type="text" id="f-name" placeholder="Airbnb">
                </label>
                <label>URL iCal
                    <input type="url" id="f-url" placeholder="https://...">
                </label>
            </div>
            <button id="btn-add" class="btn btn-primary">Ajouter</button>
        </div>

        <div class="card">
            <h3>Sources existantes</h3>
            <div id="sources-list">Chargement…</div>
            <button id="btn-sync" class="btn btn-dark">Synchroniser tout</button>
        </div>
    </main>

    <script src="assets/js/settings.js"></script>
</body>
</html>
