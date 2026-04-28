<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/admin/announcements/ordering.php';

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

$movedUp = announcement_reorder_ids([10, 20, 30], 20, 'up');
assertSameValue([20, 10, 30], $movedUp, 'Announcement should move up one slot.');

$movedDown = announcement_reorder_ids([10, 20, 30], 20, 'down');
assertSameValue([10, 30, 20], $movedDown, 'Announcement should move down one slot.');

$topStays = announcement_reorder_ids([10, 20, 30], 10, 'up');
assertSameValue([10, 20, 30], $topStays, 'Top announcement should stay put.');

$bottomStays = announcement_reorder_ids([10, 20, 30], 30, 'down');
assertSameValue([10, 20, 30], $bottomStays, 'Bottom announcement should stay put.');

echo "announcements_ordering_test passed" . PHP_EOL;
