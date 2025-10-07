<?php
declare(strict_types=1);

require __DIR__ . '/php/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Liberty Church DB connectivity test\n";
echo "Timestamp: " . date('c') . "\n\n";

try {
    db();
    echo "STATUS: OK\n";
    echo "Message: Database connection succeeded.";
} catch (Throwable $e) {
    http_response_code(500);
    echo "STATUS: ERROR\n";
    echo 'Message: ' . $e->getMessage() . "\n";
    echo 'Exception: ' . get_class($e) . "\n";
    if ($e->getPrevious()) {
        echo 'Previous: ' . $e->getPrevious()->getMessage() . "\n";
    }
}

