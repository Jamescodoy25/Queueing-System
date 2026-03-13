<?php
session_start();

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success = $_SESSION['registration_success'];
    unset($_SESSION['registration_success']);
}

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: cashier-dashboard.php');
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $users = json_decode(file_get_contents('data/users.json'), true);
    
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            
            if ($user['role'] === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: cashier-dashboard.php');
            }
            exit();
        }
    }
    
    $error = "Invalid username or password";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiesta Appliance - Login</title>
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
<p></p>

    <div class="container">
        <h1>Fiesta Appliance Center, Inc.</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>