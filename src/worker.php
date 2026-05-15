<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Siappos\Shared\QueueManager;

$queue = new QueueManager($pdo);

echo "Starting queue worker...\n";

while (true) {
    $job = $queue->pop();

    if ($job) {
        echo "Processing job ID {$job['id']} of class {$job['class']}...\n";

        try {
            // Very simple mocked processor
            if ($job['class'] === 'SendEmailReceipt') {
                $data = $job['data'];
                echo "--> MOCK: Sending email receipt for transaction {$data['transactionNumber']}...\n";
                sleep(1); // simulate work
                echo "--> Done.\n";
            } else {
                echo "--> Unknown job class.\n";
            }
        } catch (\Exception $e) {
            echo "Failed processing job {$job['id']}: " . $e->getMessage() . "\n";
            // In a real app we'd push it back with incremented attempts
        }
    } else {
        sleep(2); // Wait before polling again
    }
}
