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
$notes = trim((string)($input['notes'] ?? ''));

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

$sql = 'INSERT INTO visit_submissions (name, email, phone, visit_date, party_size, notes)
        VALUES (:name, :email, :phone, :visit_date, :party_size, :notes)';

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone !== '' ? $phone : null,
        ':visit_date' => $dateValue,
        ':party_size' => $partySize !== '' ? $partySize : null,
        ':notes' => $notes !== '' ? $notes : null,
    ]);
} catch (PDOException $e) {
    error_log('Failed to save visit submission: ' . $e->getMessage());
    fail('We could not save your visit request right now. Please try again shortly.', 500);
}

ok([
    'message' => 'Visit request received! We can’t wait to meet you.',
]);
