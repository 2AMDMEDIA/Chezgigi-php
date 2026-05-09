<?php
/**
 * API gestion des sources iCal.
 *
 * GET    /api/ical-sources.php          → liste les sources
 * POST   /api/ical-sources.php          → ajoute une source {name, url}
 * DELETE /api/ical-sources.php?id=X     → supprime la source X
 */

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

auth_require();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pdo    = db();

try {
    switch ($method) {
        case 'GET':
            $rows = $pdo->query(
                'SELECT id, name, url, last_sync_at AS lastSyncAt, created_at AS createdAt
                 FROM ical_source ORDER BY id ASC'
            )->fetchAll();
            foreach ($rows as &$r) $r['id'] = (int) $r['id'];
            json_response($rows);

        case 'POST':
            $data = json_input();
            $name = trim($data['name'] ?? '');
            $url  = trim($data['url']  ?? '');
            if ($name === '' || $url === '') {
                json_response(['error' => 'name et url requis'], 400);
            }
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                json_response(['error' => 'URL invalide'], 400);
            }
            $stmt = $pdo->prepare('INSERT INTO ical_source (name, url) VALUES (?, ?)');
            $stmt->execute([$name, $url]);
            json_response(['id' => (int) $pdo->lastInsertId()], 201);

        case 'DELETE':
            if (!$id) json_response(['error' => 'id requis'], 400);
            $pdo->prepare('DELETE FROM ical_source WHERE id = ?')->execute([$id]);
            json_response(['ok' => true]);

        default:
            json_response(['error' => 'Method not allowed'], 405);
    }
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
