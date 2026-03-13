<?php
session_start();
header('Content-Type: application/json');

$queues = json_decode(file_get_contents('data/queues.json'), true);
$recalls = [];

// Get recalls from the last 10 seconds
$cutoff = time() - 10;

foreach ($queues['queue_history'] as $item) {
    if (!empty($item['is_recall']) && strtotime($item['timestamp']) >= $cutoff) {
        $recalls[] = [
            'window' => $item['window'],
            'number' => $item['number']
        ];
    }
}

echo json_encode($recalls);
?>