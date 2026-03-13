<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header('Location: ' . ($_SESSION['user']['role'] === 'admin' ? 'admin-dashboard.php' : 'cashier-dashboard.php'));
    exit();
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'cashier';
    $window = $_POST['window'] ?? null;
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        $users = json_decode(file_get_contents('data/users.json'), true);
        
        // Check if username already exists
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $error = "Username already exists";
                break;
            }
        }
        
        if (!isset($error)) {
            // Add new user
            $newUser = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'window' => $role === 'cashier' ? $window : null
            ];
            
            $users[] = $newUser;
            file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT));
            
            // If cashier, add to cashiers.json
            if ($role === 'cashier') {
                $cashiers = json_decode(file_get_contents('data/cashiers.json'), true);
                $cashiers[] = [
                    'username' => $username,
                    'window' => $window
                ];
                file_put_contents('data/cashiers.json', json_encode($cashiers, JSON_PRETTY_PRINT));
            }
            
            // Redirect to login page with success message
            $_SESSION['registration_success'] = "Registration successful! Please login.";
            header('Location: index.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiesta Appliance - Register</title>
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
    <a href="index.php" class="btn back-btn">Back to Login</a>
</div>
    <div class="container">
        <h1>Register</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="admin">Admin</option>
                    <option value="cashier" selected>Cashier</option>
                </select>
            </div>
            
            <div class="form-group" id="window-group">
                <label for="window">Assign Window</label>
                <select id="window" name="window">
                    <option value="Window 1">Window 1</option>
                    <option value="Window 2">Window 2</option>
                    <option value="Window 3">Window 3</option>
                    <option value="Window 4">Window 4</option>
                    <option value="Window 5">Window 5</option>
                </select>
            </div>
            
            <button type="submit">Register</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>

    <script>
        // Show/hide window selection based on role
        document.getElementById('role').addEventListener('change', function() {
            const windowGroup = document.getElementById('window-group');
            if (this.value === 'cashier') {
                windowGroup.style.display = 'block';
            } else {
                windowGroup.style.display = 'none';
            }
        });
    </script>
</body>
</html>