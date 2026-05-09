<?php
/**
 * API synchronisation iCal — POST déclenche la sync de toutes les sources.
 */

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/ical-parser.php';

auth_require();
require_post();

$pdo  = db();
$rows = $pdo->query('SELECT id, name FROM ical_source ORDER BY id ASC')->fetchAll();

$results = [];
foreach ($rows as $row) {
    try {
        $count = sync_ical_source((int) $row['id']);
        $results[] = ['source' => $row['name'], 'synced' => $count];
    } catch (Throwable $e) {
        $results[] = ['source' => $row['name'], 'error' => $e->getMessage()];
    }
}

json_response(['results' => $results]);
