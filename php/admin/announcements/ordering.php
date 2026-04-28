<?php
declare(strict_types=1);

function announcement_reorder_ids(array $ids, int $targetId, string $direction): array
{
    $index = array_search($targetId, $ids, true);
    if ($index === false) {
        return array_values($ids);
    }

    if ($direction === 'up' && $index > 0) {
        [$ids[$index - 1], $ids[$index]] = [$ids[$index], $ids[$index - 1]];
    }

    if ($direction === 'down' && $index < count($ids) - 1) {
        [$ids[$index], $ids[$index + 1]] = [$ids[$index + 1], $ids[$index]];
    }

    return array_values($ids);
}
