<?php
/**
 * API CRUD réservations.
 *
 * GET    /api/reservations.php           → liste toutes les réservations
 * POST   /api/reservations.php           → crée une réservation
 * PUT    /api/reservations.php?id=X      → modifie la réservation X
 * DELETE /api/reservations.php?id=X      → supprime la réservation X
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
                'SELECT id, guest_name AS guestName, start_date AS startDate,
                        end_date AS endDate, source, ical_uid AS icalUid,
                        notes, price, is_menage AS isMenage, created_at AS createdAt
                 FROM reservation
                 ORDER BY start_date ASC'
            )->fetchAll();
            // Cast types pour le JSON
            foreach ($rows as &$r) {
                $r['id']       = (int) $r['id'];
                $r['price']    = $r['price'] !== null ? (float) $r['price'] : null;
                $r['isMenage'] = (bool) $r['isMenage'];
            }
            json_response($rows);

        case 'POST':
            $data = json_input();
            $stmt = $pdo->prepare(
                'INSERT INTO reservation (guest_name, start_date, end_date, source, notes, price, is_menage)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                trim($data['guestName'] ?? ''),
                substr($data['startDate'] ?? '', 0, 10),
                substr($data['endDate']   ?? '', 0, 10),
                $data['source'] ?? 'MANUAL',
                $data['notes']  ?? null,
                isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
                !empty($data['isMenage']) ? 1 : 0,
            ]);
            json_response(['id' => (int) $pdo->lastInsertId()], 201);

        case 'PUT':
            if (!$id) json_response(['error' => 'id requis'], 400);
            $data = json_input();

            // Préserve le nom existant si l'utilisateur n'a pas spécifié (sécurité)
            $stmt = $pdo->prepare(
                'UPDATE reservation
                    SET guest_name = ?, notes = ?, price = ?, is_menage = ?
                  WHERE id = ?'
            );
            // Pour les réservations iCal, on ne change PAS les dates ni la source
            // (cohérent avec le comportement Next.js).
            $check = $pdo->prepare('SELECT ical_uid FROM reservation WHERE id = ?');
            $check->execute([$id]);
            $row = $check->fetch();
            if (!$row) json_response(['error' => 'introuvable'], 404);

            $isFromIcal = !empty($row['ical_uid']);

            if ($isFromIcal) {
                $stmt->execute([
                    trim($data['guestName'] ?? ''),
                    $data['notes'] ?? null,
                    isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
                    !empty($data['isMenage']) ? 1 : 0,
                    $id,
                ]);
            } else {
                // Réservation manuelle : on peut tout modifier
                $stmt2 = $pdo->prepare(
                    'UPDATE reservation
                        SET guest_name = ?, start_date = ?, end_date = ?,
                            source = ?, notes = ?, price = ?, is_menage = ?
                      WHERE id = ?'
                );
                $stmt2->execute([
                    trim($data['guestName'] ?? ''),
                    substr($data['startDate'] ?? '', 0, 10),
                    substr($data['endDate']   ?? '', 0, 10),
                    $data['source'] ?? 'MANUAL',
                    $data['notes']  ?? null,
                    isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
                    !empty($data['isMenage']) ? 1 : 0,
                    $id,
                ]);
            }
            json_response(['ok' => true]);

        case 'DELETE':
            if (!$id) json_response(['error' => 'id requis'], 400);
            $pdo->prepare('DELETE FROM reservation WHERE id = ?')->execute([$id]);
            json_response(['ok' => true]);

        default:
            json_response(['error' => 'Method not allowed'], 405);
    }
} catch (Throwable $e) {
    json_response(['error' => $e->getMessage()], 500);
}
