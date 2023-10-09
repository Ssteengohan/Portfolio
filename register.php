<?php
require_once('sql/db.php'); // Include your database connection

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Get user input
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $job_title = $_POST['job_title'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the email is already in use
    $email_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $email_check->bindParam(':email', $email);
    $email_check->execute();
    $email_count = $email_check->fetchColumn();

    if ($email_count > 0) {
        // Email already exists, show an error message
        echo "<script>
        alert('Email address is already in use. Please choose a different one.');
        </script>";
    } else {
        // Email is unique, proceed with registration
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            // Get the ID of the newly registered user
            $user_id = $pdo->lastInsertId();

            // Insert the user's profile information
            $profile_stmt = $pdo->prepare("INSERT INTO profile (user_id, first_name, last_name, job_title) VALUES (:user_id, :first_name, :last_name, :job_title)");
            $profile_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $profile_stmt->bindParam(':first_name', $first_name);
            $profile_stmt->bindParam(':last_name', $last_name);
            $profile_stmt->bindParam(':job_title', $job_title);
            
            if ($profile_stmt->execute()) {
                // Registration successful, redirect or show a success message
                header("Location: login.php");
                exit();
            } else {
                // Handle profile creation error
                echo "<script>
                alert('Profile creation failed. Please try again later.');
                </script>";
            }
        } else {
            // Registration failed, handle the error
            echo "<script>
            alert('Registration failed. Please try again later.');
            </script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link  type="text/css" rel="stylesheet" href="css\main.css">
    <title>Document</title>
</head>
<body>
    <?php include('navbar.php'); ?>
    <section class="register">
        <div class="container">
            <div class="register-container">
                <div class="register-title">
                    <h2 class="title">Register</h2>
                </div>
                <div class="register-form__container">
                    <form method="POST">
                        <input type="text" placeholder="Name" name="first_name" required pattern="[A-Za-z]+">
                        <input type="text" placeholder="Last Name" name="last_name" required pattern="[A-Za-z]+">
                        <input type="text" placeholder="Job Title" name="job_title" required> 
                        <input type="email" placeholder="Email" name="email" required>
                        <input type="password" placeholder="Password" name="password" required pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d){8,}">
                        <input type="submit" name="submit" value="Register">
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>
</html>