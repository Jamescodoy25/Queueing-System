<?php
header('Content-Type: application/json');
session_start();

$queues = json_decode(file_get_contents('data/queues.json'), true);
echo json_encode([
    'last_reset' => $queues['last_reset'] ?? 0,
    'current_queues' => $queues['current_queues'] ?? []
]);
?>