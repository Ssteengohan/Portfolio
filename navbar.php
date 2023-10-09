<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link  type="text/css" rel="stylesheet" href="css\main.css">
    <title>Navbar</title>
</head>
<body>
    <nav>
        <div class="container"> 
<?php

            $loggedIn = isset($_SESSION['user_id']);
            if ($loggedIn) {
               ?><ul>
                <li id="home"><a href="index.php">Home</a></li>
                <li id="profile"><a href="profile.php">Profile</a></li>
                <li id="logout"><a href="logout.php">Logout</a></li>
            </ul><?php
            } else {
            ?>
            <ul>
                <li id="home"><a href="index.php">Home</a></li>
                <li id="login"><a href="login.php">Login</a></li>
                <li id="register"><a href="register.php">Register</a></li>
            </ul>
            <?php
            }
            ?>
        </div> 
    </nav>
    <script>
    var currentUrl = window.location.href;

    if (currentUrl.indexOf("index.php") !== -1) {
        document.getElementById("home").classList.add("active");
    } else if (currentUrl.indexOf("login.php") !== -1) {
        document.getElementById("login").classList.add("active");
    } else if (currentUrl.indexOf("register.php") !== -1) {
        document.getElementById("register").classList.add("active");
    } else if (currentUrl.indexOf("profile.php") !== -1) {
        document.getElementById("profile").classList.add("active");
    }
</script>

</body>
</html>