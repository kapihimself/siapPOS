<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/Shared/QueueManager.php';

use Siappos\Shared\QueueManager;

$queue = new QueueManager($pdo);

echo "Starting queue worker...\n";

while (true) {
    $job = $queue->pop();

    if (!$job) {
        sleep(2);
        continue;
    }

    echo "Processing job {$job['id']}: {$job['job_class']}\n";

    try {
        $payload = json_decode((string)$job['payload'], true);

        // Mock processing
        if ($job['job_class'] === 'send_email_receipt') {
            echo "Sending email receipt for TX {$payload['transaction_id']}\n";
            sleep(1);
        } else {
            echo "Unknown job class: {$job['job_class']}\n";
        }

        $queue->markCompleted((int)$job['id']);
        echo "Job {$job['id']} completed.\n";

    } catch (\Exception $e) {
        echo "Job {$job['id']} failed: " . $e->getMessage() . "\n";
        $queue->markFailed((int)$job['id'], $e->getMessage());
    }
}
