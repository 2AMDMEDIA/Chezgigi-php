<?php
/**
 * Parser iCal (RFC 5545) en pur PHP — sans dépendance externe.
 * Gère les flux Airbnb et Booking.com.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Parse un contenu iCal (.ics) et retourne un tableau d'événements.
 *
 * @return array<int,array{uid:string,summary:string,start_date:string,end_date:string}>
 */
function parse_ical(string $content): array
{
    // Normalise les fins de ligne et gère les "line folding" (RFC 5545 §3.1)
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    // Supprime les retours-chariot suivis d'espace ou tab (folding)
    $content = preg_replace('/\n[ \t]/', '', $content);

    $events  = [];
    $current = null;

    foreach (explode("\n", $content) as $line) {
        if ($line === 'BEGIN:VEVENT') {
            $current = [];
            continue;
        }
        if ($line === 'END:VEVENT') {
            if (
                $current !== null
                && isset($current['UID'], $current['DTSTART'], $current['DTEND'])
            ) {
                $events[] = [
                    'uid'        => $current['UID'],
                    'summary'    => $current['SUMMARY'] ?? 'Réservé',
                    'start_date' => parse_ical_date($current['DTSTART']),
                    'end_date'   => parse_ical_date($current['DTEND']),
                ];
            }
            $current = null;
            continue;
        }
        if ($current === null) continue;

        // Sépare clé;params:valeur
        $colon = strpos($line, ':');
        if ($colon === false) continue;
        $head  = substr($line, 0, $colon);
        $value = substr($line, $colon + 1);
        // Le nom est ce qui précède le premier `;`
        $name  = strtoupper(strtok($head, ';'));

        $current[$name] = $value;
    }

    return $events;
}

/**
 * Parse une date iCal (YYYYMMDD ou YYYYMMDDTHHMMSSZ) → 'YYYY-MM-DD'.
 */
function parse_ical_date(string $value): string
{
    // Format date seule : 20260408
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $value, $m)) {
        return "$m[1]-$m[2]-$m[3]";
    }
    // Format date+heure : 20260408T120000Z
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T/', $value, $m)) {
        return "$m[1]-$m[2]-$m[3]";
    }
    return $value;
}

/**
 * Synchronise une source iCal avec la BDD.
 * - Préserve guest_name, notes, price, is_menage si modifiés à la main.
 * - Met à jour les dates et la source.
 *
 * @return int Nombre d'événements synchronisés
 */
function sync_ical_source(int $sourceId): int
{
    $pdo = db();

    $stmt = $pdo->prepare('SELECT id, name, url FROM ical_source WHERE id = ?');
    $stmt->execute([$sourceId]);
    $source = $stmt->fetch();
    if (!$source) {
        throw new RuntimeException("Source $sourceId introuvable");
    }

    $content = http_fetch($source['url']);
    $events  = parse_ical($content);

    // Détecte la source canonique
    $clean = strtoupper(preg_replace('/\s+/', '', $source['name']));
    if (strpos($clean, 'AIRBNB') !== false) {
        $reservationSource = 'AIRBNB';
    } elseif (strpos($clean, 'BOOKING') !== false) {
        $reservationSource = 'BOOKING';
    } else {
        $reservationSource = strtoupper($source['name']);
    }

    $synced  = 0;
    $findStmt = $pdo->prepare('SELECT id FROM reservation WHERE ical_uid = ?');
    $updStmt  = $pdo->prepare(
        'UPDATE reservation SET start_date = ?, end_date = ?, source = ? WHERE ical_uid = ?'
    );
    $insStmt  = $pdo->prepare(
        'INSERT INTO reservation (guest_name, start_date, end_date, source, ical_uid)
         VALUES (?, ?, ?, ?, ?)'
    );

    $foundUids = [];
    foreach ($events as $evt) {
        $foundUids[] = $evt['uid'];
        $findStmt->execute([$evt['uid']]);
        if ($findStmt->fetch()) {
            // Conserve le nom et les notes saisis à la main
            $updStmt->execute([
                $evt['start_date'],
                $evt['end_date'],
                $reservationSource,
                $evt['uid'],
            ]);
        } else {
            $insStmt->execute([
                $evt['summary'],
                $evt['start_date'],
                $evt['end_date'],
                $reservationSource,
                $evt['uid'],
            ]);
        }
        $synced++;
    }

    // ----- Détection des annulations -----
    // Toute réservation de cette source qui n'est plus dans le flux iCal
    // est considérée comme annulée et supprimée.
    if ($foundUids) {
        $placeholders = implode(',', array_fill(0, count($foundUids), '?'));
        $params = array_merge([$reservationSource], $foundUids);
        $sql = "DELETE FROM reservation
                WHERE source = ?
                  AND ical_uid IS NOT NULL
                  AND ical_uid NOT IN ($placeholders)";
        $pdo->prepare($sql)->execute($params);
    } else {
        // Flux vide : on supprime toutes les réservations de cette source
        $pdo->prepare(
            'DELETE FROM reservation WHERE source = ? AND ical_uid IS NOT NULL'
        )->execute([$reservationSource]);
    }

    $pdo->prepare('UPDATE ical_source SET last_sync_at = NOW() WHERE id = ?')
        ->execute([$sourceId]);

    return $synced;
}
