<?php
session_start();

// Check if user is logged in and is cashier
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'cashier') {
    header('Location: index.php');
    exit();
}

$window = $_SESSION['user']['window'] ?? '';

// Load queue data with proper initialization
$queues = json_decode(file_get_contents('data/queues.json'), true);

// Initialize next_queue_numbers if not exists
if (!isset($queues['next_queue_numbers'])) {
    $queues['next_queue_numbers'] = [
        'Window 1' => 1,
        'Window 2' => 1,
        'Window 3' => 1,
        'Window 4' => 1,
        'Window 5' => 1
    ];
    file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
}

// Format queue number based on window
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
// In your call_next handler (where action == 'call_next')
if ($action === 'call_next') {
    // Get next number for this window
    $nextNumber = $queues['next_queue_numbers'][$window];
    $formattedNumber = formatQueueNumber($window, $nextNumber);
    
    // Update current queue
    $queues['current_queues'][$window] = [
        'now_serving' => $formattedNumber,
        'next_number' => formatQueueNumber($window, $nextNumber + 1)
    ];
    
    // Update history
    $queues['queue_history'][] = [
        'window' => $window,
        'number' => $formattedNumber,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Special handling for Windows 3 and 4
    
        
        // Increment next number (reset after 100)
        $queues['next_queue_numbers'][$window] = $nextNumber < 100 ? $nextNumber + 1 : 1;
        
        file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
        
        header('Location: cashier-dashboard.php');
        exit();
    }

    // Handle set starting number action
elseif ($_POST['action'] === 'set_starting_number' && !empty($_POST['starting_number'])) {
    $startingNumber = strtoupper(trim($_POST['starting_number']));
    
    // Validate format (A01-D99)
    if (preg_match('/^([A-E])([0-9]{2})$/', $startingNumber, $matches)) {
        $prefix = $matches[1];
        $number = (int)$matches[2];
        
        // Verify prefix matches window
        $expectedPrefix = '';
        switch($window) {
            case 'Window 1': $expectedPrefix = 'A'; break;
            case 'Window 2': $expectedPrefix = 'B'; break;
            case 'Window 3': $expectedPrefix = 'C'; break;
            case 'Window 4': $expectedPrefix = 'D'; break;
            case 'Window 5': $expectedPrefix = 'E'; break;
        }
        
        if ($prefix === $expectedPrefix) {
            // Update next queue number
            $queues['next_queue_numbers'][$window] = $number;
            
            // Clear current serving number
            $queues['current_queues'][$window] = [
                'now_serving' => null,
                'next_number' => $startingNumber
            ];
            
            file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
            
            $_SESSION['success'] = "Starting queue number set to $startingNumber";
            header('Location: cashier-dashboard.php');
            exit();
        } else {
            $error = "Invalid prefix for this window. Window $window should start with $expectedPrefix";
        }
    } else {
        $error = "Invalid number format. Please use format like A01, B02, C03, etc.";
    }
}

    // Handle recall number action
elseif ($_POST['action'] === 'recall_number' && !empty($_POST['recall_number'])) {
    $recallNumber = strtoupper(trim($_POST['recall_number']));
    
    // Validate format (A01-E99)
    if (preg_match('/^[A-E][0-9]{2}$/', $recallNumber)) {
        // Add to queue history
        $queues['queue_history'][] = [
            'window' => $window,
            'number' => $recallNumber,
            'timestamp' => date('Y-m-d H:i:s'),
            'is_recall' => true
        ];
        
        file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
        
        // Set visual announcement
        $_SESSION['announcement'] = "Recalling number $recallNumber";
        
        header('Location: cashier-dashboard.php');
        exit();
    } else {
        $error = "Invalid number format. Please use format like A01, B02, C03, etc.";
    }
}
// Handle reset history action
elseif ($_POST['action'] === 'reset_history') {
    // Reset only the history while keeping current queues
    $queues['queue_history'] = [];
    
    file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
    
    $_SESSION['announcement'] = "Queue history has been reset";
    header('Location: cashier-dashboard.php');
    exit();
}
}

// Get current queue for this window
$currentQueue = $queues['current_queues'][$window] ?? [
    'now_serving' => null,
    'next_number' => formatQueueNumber($window, $queues['next_queue_numbers'][$window])
];

// Get queue history for this window
$windowQueueHistory = array_filter($queues['queue_history'], function($item) use ($window) {
    return $item['window'] === $window;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiesta Appliance - Cashier Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<p></p>

<div id="images1">
    <img src="images/logo1.png" alt="nawala ang pics" id = "logo1" >
    <div id="other-pics">
    <img src="images/fiesta.png" alt="nawala ang pics" id = "fiesta" >
    <img src="images/appliance.png" alt="nawala ang pics" id = "appliance" >

    </div>
</div>

    <div class="nav-buttons">
        <a href="logout.php" class="btn logout-btn">Logout</a>
    </div>
    
    <div class="container">
        <h1>Cashier Dashboard - <?php echo htmlspecialchars($window); ?></h1>
        
        <div class="queue-display-cashier">
            <div class="window">
                <h3><?php echo htmlspecialchars($window); ?></h3>
                <div class="now-serving">Now Serving: <?php echo $currentQueue['now_serving'] ?? '--'; ?></div>
                <div class="next-number">Next Number: <?php echo $currentQueue['next_number'] ?? formatQueueNumber($window, $queues['next_queue_numbers'][$window]); ?></div>
                
                <div class="button-group">
                    <form method="POST">
                        <input type="hidden" name="action" value="call_next">
                        <button type="submit" class="btn" id="call-next-btn">Call Next</button>
                    </form>
                    
                </div>
            </div>
        </div>

        <div class="set-queue-section">
    <h3>Set Starting Queue Number</h3>
    <form method="POST" class="set-queue-form">
        <div class="form-group">
            <label for="starting_number">Enter starting number (e.g., A01, B02):</label>
            <div class="set-queue-input-group">
                <input type="text" id="starting_number" name="starting_number" 
                       pattern="[A-E][0-9]{2}" title="Format: A01, B02, C03, D04" 
                       placeholder="e.g., <?php echo formatQueueNumber($window, 1); ?>" required>
                <button type="submit" name="action" value="set_starting_number" class="btn set-queue-btn">
                    🚀 Set Starting Number
                </button>
            </div>
        </div>
    </form>
</div>

        <!-- Recall Number Section -->
<div class="recall-section">
    <h3>Recall Specific Number</h3>
    <form method="POST" class="recall-form">
        <div class="form-group">
            <label for="recall_number">Enter number to recall (format: A01, B02, etc.):</label>
            <div class="recall-input-group">
                <input type="text" id="recall_number" name="recall_number" 
                       pattern="[A-E][0-9]{2}" title="Format: A01, B02, C03, D04,E05, etc." 
                       placeholder="e.g., A01, B02, C03, D04,E05" required>
                <button type="submit" name="action" value="recall_number" class="btn recall-btn">
                    📢 Recall Number
                </button>
            </div>
        </div>
    </form>
</div>
        
        <h2>Queue History</h2>
        <table>
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($windowQueueHistory) as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['number']); ?></td>
                        <td><?php echo htmlspecialchars($item['timestamp']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Reset Queue History Section -->
<div class="reset-section">
    <h3>Queue History Management</h3>
    <form method="POST" onsubmit="return confirm('Are you sure you want to reset ALL queue history? This cannot be undone.');">
        <input type="hidden" name="action" value="reset_history">
        <button type="submit" class="btn btn-danger">
            🗑️ Reset Queue History
        </button>
    </form>
</div>
    </div>
    
    <script src="scripts.js"></script>

    <script>
function speakAnnouncement(text) {
    if ('speechSynthesis' in window) {
        // Cancel any ongoing speech
        window.speechSynthesis.cancel();
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.volume = 1;
        utterance.rate = 0.9;
        utterance.pitch = 1;
        window.speechSynthesis.speak(utterance);
    }
}

// Handle form submission
//Call  Next and Call Again
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const action = this.querySelector('[name="action"]').value;
        if (action === 'call_next') {
            const nextNumber = document.querySelector('.next-number').textContent
                .replace('Next Number: ', '').trim();
            if (nextNumber && nextNumber !== '--') {
                speakAnnouncement("Now serving number " + nextNumber);
            }
        } else if (action === 'call_again') {
            const currentNumber = document.querySelector('.now-serving').textContent
                .replace('Now Serving: ', '').trim();
            if (currentNumber && currentNumber !== '--') {
                speakAnnouncement("Calling again number " + currentNumber);
            }
        }
    });
});
// Handle recall announcement
document.querySelector('.recall-form').addEventListener('submit', function(e) {
    const recallNumber = document.getElementById('recall_number').value.trim().toUpperCase();
    if (recallNumber && /^[A-E][0-9]{2}$/.test(recallNumber)) {
        // Speak the announcement
        if ('speechSynthesis' in window) {
            const utterance = new SpeechSynthesisUtterance();
            utterance.text = "Attention please, recalling number " + recallNumber;
            utterance.volume = 1;
            utterance.rate = 0.9;
            window.speechSynthesis.speak(utterance);
        }
        
        // Show visual feedback
        const announcement = document.createElement('div');
        announcement.className = 'announcement';
        announcement.textContent = "Recalling number " + recallNumber;
        document.body.appendChild(announcement);
        setTimeout(() => announcement.remove(), 3000);
    } else {
        e.preventDefault();
        alert("Please enter a valid number in the format A01, B02, C03, etc.");
    }
});

// Auto-refresh when queues are reset
let lastResetTime = 0;

function checkForUpdates() {
    fetch('check-updates.php')
        .then(response => response.json())
        .then(data => {
            if (data.last_reset > lastResetTime) {
                lastResetTime = data.last_reset;
                location.reload();
            }
            
            // Also check for queue changes every 5 seconds
            setTimeout(checkForUpdates, 5000);
        });
}

// Initial check after page loads
document.addEventListener('DOMContentLoaded', function() {
    // Get initial reset time
    fetch('check-updates.php')
        .then(response => response.json())
        .then(data => {
            lastResetTime = data.last_reset || 0;
            checkForUpdates();
        });
});

// Handle set starting number form
document.querySelector('.set-queue-form')?.addEventListener('submit', function(e) {
    const input = this.querySelector('#starting_number');
    const value = input.value.trim().toUpperCase();
    const windowPrefix = {
        'Window 1': 'A',
        'Window 2': 'B',
        'Window 3': 'C',
        'Window 4': 'D',
        'Window 5': 'E'
    }[<?php echo json_encode($window); ?>];
    
    if (!value.match(new RegExp(`^${windowPrefix}[0-9]{2}$`))) {
        e.preventDefault();
        alert(`Please enter a valid number starting with ${windowPrefix} (e.g., ${windowPrefix}01)`);
        input.focus();
    }
});
</script>

<style>
    .window {
    background-color: rgb(243, 243, 196);
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    justify-content: center;
    display: grid;
    border-left: 4px solid #ffc107;
    font-size: 1.5rem;
    height: auto;
    width: auto;
}

/* Set Queue Section Styles */
.set-queue-section {
    margin-top: 30px;
    padding: 20px;
    background-color: #e3f2fd;
    border-radius: 8px;
    border-left: 4px solid #2196F3;
}

.set-queue-input-group {
    display: flex;
    gap: 10px;
    align-items: center;
}

.set-queue-input-group input {
    flex: 1;
    padding: 12px;
    font-size: 1.1rem;
    text-transform: uppercase;
    border: 2px solid #2196F3;
    border-radius: 4px;
}

.set-queue-btn {
    background-color: #2196F3;
    color: white;
    font-weight: bold;
    padding: 12px 20px;
}

.set-queue-btn:hover {
    background-color: #1976D2;
}
</style>

</body>
</html>