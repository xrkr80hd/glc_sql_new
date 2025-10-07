<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 600');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Ensure the current request method is among the allowed ones.
 */
function require_http_method(string ...$allowed): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_response([
            'success' => false,
            'message' => 'Method Not Allowed',
        ], 405);
    }
}

/**
 * Parse the JSON request body and return it as an associative array.
 * Falls back to standard form submissions when JSON is absent.
 */
function request_payload(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        json_response([
            'success' => false,
            'message' => 'Invalid JSON payload',
        ], 400);
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    // For x-www-form-urlencoded read php://input when $_POST empty
    $raw = file_get_contents('php://input') ?: '';
    parse_str($raw, $parsed);
    return is_array($parsed) ? $parsed : [];
}

/**
 * Simple helper for consistent success payloads.
 */
function ok(array $data = [], int $status = 200): void
{
    json_response(array_merge(['success' => true], $data), $status);
}

/**
 * Simple helper for consistent error payloads.
 */
function fail(string $message, int $status = 400, array $extra = []): void
{
    json_response(array_merge(['success' => false, 'message' => $message], $extra), $status);
}

function media_public_url(?string $path): ?string
{
    if ($path === null || $path === '') {
        return null;
    }

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    return '/uploads/' . ltrim($path, '/');
}
