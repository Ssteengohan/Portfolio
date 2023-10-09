<?php
// Start the session
session_start();

require_once('sql/db.php');

// Check if the user is already logged in, if so, redirect them to the homepage or a default page
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Get user input and sanitize it
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);

    // Query the database to check if the user exists
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        // Login successful, set a session variable to mark the user as logged in
        $_SESSION['user_id'] = $user['id']; 

        // Redirect the user to the previous page or a default page
        header("Location: " . (isset($_SESSION['previous_url']) ? $_SESSION['previous_url'] : 'index.php'));
        exit();
    } else {
        // Login failed, show an error message
        echo "<script>alert('Login failed. Please check your email and password.');</script>";
    }
}

// Store the current URL as the previous URL for future use
$_SESSION['previous_url'] = $_SERVER['REQUEST_URI'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link type="text/css" rel="stylesheet" href="css/main.css">
    <title>Login</title>
</head>
<body>
    <?php include('navbar.php'); ?>
    <section class="login">
        <div class="container">
            <div class="login-container">
                <div class="login-title">
                    <h2 class="title">Login</h2>
                </div>
                <div class="login-form__container">
                    <form method="POST" action="login.php">
                        <input type="email" placeholder="Email" name="email" required> 
                        <input type="password" placeholder="Password" name="password" required>
                        <a class="register-link" href="register.php">Don't have an account? click here.</a>
                        <input type="submit" name="submit" value="Login">
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
