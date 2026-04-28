<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('POST');

$input = request_payload();

$requestText = trim((string)($input['request'] ?? ''));
if ($requestText === '') {
    fail('Please share your prayer need so our team can respond.', 422);
}

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$share = $input['sharePermission'] ?? $input['share_permission'] ?? null;
$allowShare = false;

if (is_string($share)) {
    $normalized = strtolower($share);
    $allowShare = in_array($normalized, ['1', 'true', 'yes', 'on'], true);
} else {
    $allowShare = (bool)$share;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please provide a valid email address.', 422);
}

$pdo = db();

$hasRequestText = db_column_exists($pdo, 'prayer_requests', 'request_text');
$hasStatus = db_column_exists($pdo, 'prayer_requests', 'status');
$hasSubmittedAt = db_column_exists($pdo, 'prayer_requests', 'submitted_at');

$columns = ['name', 'email', 'request', 'share_permission'];
$values = [':name', ':email', ':request', ':share_permission'];
$params = [
    ':name' => $name !== '' ? $name : null,
    ':email' => $email !== '' ? $email : null,
    ':request' => $requestText,
    ':share_permission' => $allowShare ? 1 : 0,
];

if ($hasRequestText) {
    $columns[] = 'request_text';
    $values[] = ':request_text';
    $params[':request_text'] = $requestText;
}

if ($hasStatus) {
    $columns[] = 'status';
    $values[] = "'new'";
}

if ($hasSubmittedAt) {
    $columns[] = 'submitted_at';
    $values[] = 'NOW()';
}

$sql = sprintf(
    'INSERT INTO prayer_requests (%s) VALUES (%s)',
    implode(', ', $columns),
    implode(', ', $values)
);

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log('Failed to save prayer request: ' . $e->getMessage());
    fail('We could not send your request right now. Please try again soon.', 500);
}

ok([
    'message' => 'Prayer request received. Our team will be praying with you.',
]);
