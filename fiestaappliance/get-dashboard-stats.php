<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Load data
$queues = json_decode(file_get_contents('data/queues.json'), true);
$cashiers = json_decode(file_get_contents('data/cashiers.json'), true);

// Calculate stats
$totalCashiers = count($cashiers);
$totalCustomersServed = count($queues['queue_history'] ?? []);

// Find next available window
$activeWindows = array_column($cashiers, 'window');
$allWindows = ['Window 1', 'Window 2', 'Window 3', 'Window 4', 'Window 5'];
$availableWindows = array_diff($allWindows, $activeWindows);
$nextAvailableWindow = empty($availableWindows) ? 'None' : reset($availableWindows);

echo json_encode([
    'total_cashiers' => $totalCashiers,
    'total_customers' => $totalCustomersServed,
    'next_available_window' => $nextAvailableWindow
]);
?>