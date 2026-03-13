<?php
session_start();

// Check admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get selected windows
$visibleWindows = $_POST['visible_windows'] ?? [];

// Prepare visibility settings
$visibility = [
    'Window 1' => in_array('Window 1', $visibleWindows),
    'Window 2' => in_array('Window 2', $visibleWindows),
    'Window 3' => in_array('Window 3', $visibleWindows),
    'Window 4' => in_array('Window 4', $visibleWindows),
    'Window 5' => in_array('Window 5', $visibleWindows)
];

// Save to file
file_put_contents('data/window_visibility.json', json_encode($visibility, JSON_PRETTY_PRINT));

$_SESSION['success'] = "Window visibility updated successfully";
header('Location: admin-dashboard.php');
exit();
?>