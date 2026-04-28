<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

require_http_method('POST');

$input = request_payload();

$name = trim((string)($input['name'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$visitDate = trim((string)($input['date'] ?? $input['visit_date'] ?? ''));
$partySize = trim((string)($input['party'] ?? $input['party_size'] ?? ''));
$preferredService = trim((string)($input['preferred_service'] ?? $input['service'] ?? ''));
$notes = trim((string)($input['notes'] ?? $input['message'] ?? ''));

if ($name === '' || $email === '') {
    fail('Name and email are required to plan your visit.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please provide a valid email address.', 422);
}

$dateValue = null;
if ($visitDate !== '') {
    try {
        $dateValue = (new DateTimeImmutable($visitDate))->format('Y-m-d');
    } catch (Exception $e) {
        fail('Visit date is invalid. Use YYYY-MM-DD format.', 422);
    }
}

$pdo = db();

$hasPreferredService = db_column_exists($pdo, 'visit_submissions', 'preferred_service');
$hasMessage = db_column_exists($pdo, 'visit_submissions', 'message');
$hasSubmittedAt = db_column_exists($pdo, 'visit_submissions', 'submitted_at');

$columns = ['name', 'email', 'phone', 'visit_date', 'party_size', 'notes'];
$values = [':name', ':email', ':phone', ':visit_date', ':party_size', ':notes'];
$params = [
    ':name' => $name,
    ':email' => $email,
    ':phone' => $phone !== '' ? $phone : null,
    ':visit_date' => $dateValue,
    ':party_size' => $partySize !== '' ? $partySize : null,
    ':notes' => $notes !== '' ? $notes : null,
];

if ($hasPreferredService) {
    $columns[] = 'preferred_service';
    $values[] = ':preferred_service';
    $params[':preferred_service'] = $preferredService !== '' ? $preferredService : null;
}

if ($hasMessage) {
    $columns[] = 'message';
    $values[] = ':message';
    $params[':message'] = $notes !== '' ? $notes : null;
}

if ($hasSubmittedAt) {
    $columns[] = 'submitted_at';
    $values[] = 'NOW()';
}

$sql = sprintf(
    'INSERT INTO visit_submissions (%s) VALUES (%s)',
    implode(', ', $columns),
    implode(', ', $values)
);

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute($params);
} catch (PDOException $e) {
    error_log('Failed to save visit submission: ' . $e->getMessage());
    fail('We could not save your visit request right now. Please try again shortly.', 500);
}

ok([
    'message' => 'Visit request received! We can’t wait to meet you.',
]);
