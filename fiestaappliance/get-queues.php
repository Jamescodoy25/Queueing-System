<?php
header('Content-Type: application/json');
session_start();

$queues = json_decode(file_get_contents('data/queues.json'), true);
$cashiers = json_decode(file_get_contents('data/cashiers.json'), true);

echo json_encode([
    'current_queues' => $queues['current_queues'] ?? [],
    'cashiers' => $cashiers
]);
?>