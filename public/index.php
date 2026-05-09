<?php
require_once __DIR__ . '/src/auth.php';
auth_require();
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ChezGigi — Planning</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand">🏔️ Mon Planning</div>
        <div class="nav-links">
            <a href="index.php" class="active">Calendrier</a>
            <a href="settings.php">Paramètres</a>
            <a href="logout.php" class="logout">Déconnexion</a>
        </div>
    </nav>

    <main class="container">
        <div class="toolbar">
            <div class="legend">
                <span><span class="dot" style="background:#FF5A5F"></span> Airbnb</span>
                <span><span class="dot" style="background:#003580"></span> Booking</span>
                <span><span class="dot" style="background:#10B981"></span> Direct</span>
                <span><span class="dot" style="background:rgb(195,198,203)"></span> Ménage</span>
            </div>
            <button id="btn-sync" class="btn btn-dark">Synchroniser iCal</button>
        </div>

        <div id="stats" class="stats-grid"></div>

        <div class="calendar-card">
            <div id="calendar"></div>
        </div>
    </main>

    <!-- Modale réservation -->
    <div id="modal" class="modal-overlay" hidden>
        <div class="modal">
            <h2 id="modal-title">Nouvelle réservation</h2>

            <div class="form-row">
                <label>Nom du voyageur
                    <input type="text" id="f-name" placeholder="Ex: Jean Dupont">
                </label>
            </div>

            <div class="form-grid-2">
                <label>Arrivée
                    <input type="date" id="f-start">
                </label>
                <label>Départ
                    <input type="date" id="f-end">
                </label>
            </div>

            <div id="ical-info" class="info-box" hidden>
                Réservation importée — dates non modifiables
            </div>

            <div class="form-row" id="source-row">
                <label>Source
                    <select id="f-source">
                        <option value="MANUAL">Réservation directe</option>
                        <option value="MENAGE">Ménage / Entretien</option>
                        <option value="AIRBNB">Airbnb</option>
                        <option value="BOOKING">Booking</option>
                    </select>
                </label>
            </div>

            <div class="form-row">
                <label>Prix (€)
                    <input type="number" id="f-price" min="0" step="0.01" placeholder="Ex: 750">
                </label>
            </div>

            <div class="form-row checkbox-row">
                <label>
                    <input type="checkbox" id="f-menage">
                    Ménage <span class="hint">— jour réservé pour l'entretien</span>
                </label>
            </div>

            <div class="form-row">
                <label>Notes
                    <textarea id="f-notes" rows="2" placeholder="Notes optionnelles..."></textarea>
                </label>
            </div>

            <div class="modal-actions">
                <button id="btn-delete" class="btn btn-danger" hidden>Supprimer</button>
                <div class="spacer"></div>
                <button id="btn-cancel" class="btn btn-ghost">Fermer</button>
                <button id="btn-save" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/fr.global.min.js"></script>
    <script src="assets/js/calendar.js"></script>
</body>
</html>
