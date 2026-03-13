<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$window = $_SESSION['user']['window'] ?? ''; // Get window from logged in cashier

// Load queue data
$queues = json_decode(file_get_contents('data/queues.json'), true);

function formatQueueNumber($window, $number) {
    $prefix = '';
    switch($window) {
        case 'Window 1': $prefix = 'A'; break;
        case 'Window 2': $prefix = 'B'; break;
        case 'Window 3': $prefix = 'C'; break;
        case 'Window 4': $prefix = 'D'; break;
        case 'Window 5': $prefix = 'E'; break;
    }
    return $prefix . str_pad($number, 2, '0', STR_PAD_LEFT);
}

$response = ['success' => false];

switch ($action) {
    case 'call_next':
        // ... existing call_next code ...
        break;
        
    case 'call_again':
        if ($_SESSION['user']['role'] !== 'cashier' || empty($window)) {
            $response['message'] = 'Only cashiers can call again';
            break;
        }
        
        $currentQueue = $queues['current_queues'][$window] ?? ['now_serving' => null];
        
        if ($currentQueue['now_serving'] !== null) {
            $queues['queue_history'][] = [
                'window' => $window,
                'number' => $currentQueue['now_serving'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
            $response['success'] = true;
        } else {
            $response['message'] = 'No current number to call again';
        }
        break;
        
    default:
        $response['message'] = 'Invalid action';
}

echo json_encode($response);
?>