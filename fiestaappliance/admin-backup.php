<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Load data
$users = json_decode(file_get_contents('data/users.json'), true);
$cashiers = json_decode(file_get_contents('data/cashiers.json'), true);
$queues = json_decode(file_get_contents('data/queues.json'), true);
$settings = json_decode(file_get_contents('data/settings.json'), true);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_cashier':
                $newUsername = trim($_POST['username'] ?? '');
                $newPassword = trim($_POST['password'] ?? '');
                $window = $_POST['window'] ?? '';
                
                // Validate inputs
                if (empty($newUsername) || empty($newPassword) || empty($window)) {
                    $_SESSION['error'] = "All fields are required";
                    header('Location: admin-dashboard.php');
                    exit();
                }
                
                // Load existing data
                $users = json_decode(file_get_contents('data/users.json'), true) ?: [];
                $cashiers = json_decode(file_get_contents('data/cashiers.json'), true) ?: [];
                
                // Check if username already exists
                foreach ($users as $user) {
                    if ($user['username'] === $newUsername) {
                        $_SESSION['error'] = "Username already exists";
                        header('Location: admin-dashboard.php');
                        exit();
                    }
                }
                
                // Add new user
                $newUser = [
                    'username' => $newUsername,
                    'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'role' => 'cashier',
                    'window' => $window
                ];
                
                $users[] = $newUser;
                file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT));
                
                // Add to cashiers list
                $cashiers[] = [
                    'username' => $newUsername,
                    'window' => $window
                ];
                file_put_contents('data/cashiers.json', json_encode($cashiers, JSON_PRETTY_PRINT));
                
                $_SESSION['success'] = "Cashier added successfully";
                header('Location: admin-dashboard.php');
                exit();

                case 'reset_queues':
                    $queues = [
                        'current_queues' => [],
                        'queue_history' => $queues['queue_history'], // Keep history if you want
                        'next_queue_numbers' => [
                            'Window 1' => 1,
                            'Window 2' => 1,
                            'Window 3' => 1,
                            'Window 4' => 1,    
                            'Window 5' => 1
                        ],
                        'last_reset' => time() // Add reset timestamp
                    ];
                    file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
                    
                    $_SESSION['success'] = "All queues have been reset";
                    header('Location: admin-dashboard.php');
                    exit();

                case 'update_running_text':
                    $runningText = $_POST['running_text'] ?? '';
                    if (!empty($runningText)) {
                        $settings['running_text'] = $runningText;
                        file_put_contents('data/settings.json', json_encode($settings, JSON_PRETTY_PRINT));
                        $success = "Running text updated successfully";
                    } else {
                        $error = "Running text is required";
                    }
                    break;

                    case 'remove_cashier':
                        $username = $_POST['username'] ?? '';
                        if (!empty($username)) {
                            // Remove from users.json
                            $users = array_filter($users, function($user) use ($username) {
                                return $user['username'] !== $username;
                            });
                            file_put_contents('data/users.json', json_encode(array_values($users), JSON_PRETTY_PRINT));
                            
                            // Remove from cashiers.json
                            $cashiers = array_filter($cashiers, function($cashier) use ($username) {
                                return $cashier['username'] !== $username;
                            });
                            file_put_contents('data/cashiers.json', json_encode(array_values($cashiers), JSON_PRETTY_PRINT));
                            
                            $success = "Cashier removed successfully";
                        }
                        break;
        }
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'call_next') {
        // Existing logic for calling the next number...
    } elseif ($action === 'recall_number' && !empty($_POST['recall_number'])) {
        // Existing logic for recalling a number...
    } elseif ($action === 'reset_history') {
        // Existing logic for resetting history...
    } elseif ($action === 'reset_window_queue') {
        $selected_window = $_POST['window'];
        
        // Reset the queue for the selected window
        if (isset($queues['next_queue_numbers'][$selected_window])) {
            $queues['next_queue_numbers'][$selected_window] = 1; // Reset to the initial value
            // Optionally clear the current queue for this window
            if (isset($queues['current_queues'][$selected_window])) {
                unset($queues['current_queues'][$selected_window]);
            }
            // Optionally clear the history for this window
            if (isset($queues['queue_history'][$selected_window])) {
                unset($queues['queue_history'][$selected_window]);
            }
        }
        
        file_put_contents('data/queues.json', json_encode($queues, JSON_PRETTY_PRINT));
        
        $_SESSION['announcement'] = "The queue for $selected_window has been reset.";
        header('Location: cashier-dashboard.php');
        exit();
    }
}

// Calculate statistics
$totalCashiers = count($cashiers);
$totalCustomersServed = count($queues['queue_history']);

// Find next available window
$activeWindows = array_column($cashiers, 'window');
$allWindows = ['Window 1', 'Window 2', 'Window 3', 'Window 4', 'Window 5'];
$availableWindows = array_diff($allWindows, $activeWindows);
$nextAvailableWindow = empty($availableWindows) ? 'None' : reset($availableWindows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiesta Appliance - Admin Dashboard</title>
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

<!-- <div id="images">
    <img src="images/logo.png" alt="nawala ang code" id="logopic">
    <div id="other-pics">
        <img src="images/fiesta.png" alt="nawala ang code" id="fiestapic">
        <img src="images/appliance.png" alt="nawala ang code" id="appliancepic">
    </div>
</div> -->



</div>
    <div class="nav-buttons">
    <a href="live-queues.php" class="btn">Live Queues</a>
    <a href="logout.php" class="btn logout-btn">Logout</a>
</div>
    
    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <div class="dashboard">
            <div class="card">
                <h3>Total Cashiers</h3>
                <p><?php echo $totalCashiers; ?></p>
            </div>
            
            <div class="card">
                <h3>Total Customers Served</h3>
                <p><?php echo $totalCustomersServed; ?></p>
            </div>
            
            <div class="card">
                <h3>Available Window</h3>
                <p><?php echo $nextAvailableWindow; ?></p>
            </div>
        </div>
        
        <div class="action-buttons">
            <button onclick="document.getElementById('add-cashier-modal').style.display='block'">Add New Cashier</button>
            <a href="live-queues.php" class="btn">View Live Queues</a>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="reset_queues">
                <button type="submit" class="btn-danger">Reset All Queues</button>
            </form>
        </div>

        <div class="reset-queue-section">
    <h3>Reset Queue for Specific Window</h3>
    <form method="POST" onsubmit="return confirm('Are you sure you want to reset the queue for this window? This cannot be undone.');">
        <input type="hidden" name="action" value="reset_window_queue">
        <label for="window-select">Select Window:</label>
        <select name="window" id="window-select" required>
            <option value="Window 1">Window 1</option>
            <option value="Window 2">Window 2</option>
            <option value="Window 3">Window 3</option>
            <option value="Window 4">Window 4</option>
            <option value="Window 5">Window 5</option>
        </select>
        <br>
        <br>
        <button type="submit" class="btn btn-danger">
            🗑️ Reset Queue for Selected Window
        </button>
    </form>
</div>

        <h2>Running Text Update</h2>
        <form method="POST">
            <div class="form-group">
                <label  for="running_text">Update Running Text</label>
                <input type="text" id="running_text" name="running_text"
                 value="<?php echo htmlspecialchars($settings['running_text']); ?>" required>
                <input type="hidden" name="action" value="update_running_text">
            </div>
            <button type="submit">Update Running Text</button>
        </form>
        
        <h2>Cashiers</h2>
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Window</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cashiers as $cashier): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cashier['username']); ?></td>
                        <td><?php echo htmlspecialchars($cashier['window']); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove_cashier">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($cashier['username']); ?>">
                                <button type="submit" class="btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Cashier Modal -->
    <div id="add-cashier-modal" class="modal" style="display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
        <div style="background-color: #fefefe; margin: 10% auto; padding: 30px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px;">
            <span style="float: right; font-size: 28px; font-weight: bold; cursor: pointer;" onclick="document.getElementById('add-cashier-modal').style.display='none'">&times;</span>
            <h2>Add New Cashier</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_cashier">
                
                <div class="form-group">
                    <label for="new_username">Username</label>
                    <input type="text" id="new_username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">Password</label>
                    <input type="password" id="new_password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_window">Assign Window</label>
                    <select id="new_window" name="window" required>
                        <option value="Window 1">Window 1</option>
                        <option value="Window 2">Window 2</option>
                        <option value="Window 3">Window 3</option>
                        <option value="Window 4">Window 4</option>
                        <option value="Window 5">Window 5</option>
                        
                    </select>
                </div>
                
                <button type="submit">Add Cashier</button>
            </form>
        </div>

        <script>
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

            document.addEventListener('DOMContentLoaded', function() {
    // Handle add cashier form submission
    const addCashierForm = document.getElementById('add-cashier-form');
    if (addCashierForm) {
        addCashierForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Adding...';
        });
    }
    
    // Close modal after submission if success message exists
    <?php if (isset($_SESSION['success'])): ?>
        document.getElementById('add-cashier-modal').style.display = 'none';
    <?php endif; ?>
});
            </script>
    </div>
    
    <script src="scripts.js"></script>
</body>
</html>