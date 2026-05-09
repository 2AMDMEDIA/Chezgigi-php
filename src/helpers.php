<?php
/**
 * Helpers généraux : JSON, dates, HTTP fetch.
 */

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Télécharge le contenu d'une URL distante (iCal).
 * Utilise curl si disponible, sinon file_get_contents.
 */
function http_fetch(string $url): string
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'ChezGigi-Planner/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            throw new RuntimeException("Erreur HTTP $code lors du téléchargement : $err");
        }
        return $body;
    }

    $ctx = stream_context_create([
        'http' => ['timeout' => 30, 'user_agent' => 'ChezGigi-Planner/1.0'],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new RuntimeException("Erreur lors du téléchargement de $url");
    }
    return $body;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        json_response(['error' => 'Method not allowed'], 405);
    }
}
