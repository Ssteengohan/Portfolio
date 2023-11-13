<?php
session_start();

//database connection
require_once('sql/db.php');

if (isset($_POST['like']) && isset($_POST['answer_id']) && isset($_SESSION['user_id'])) {// like answer
    $answerId = $_POST['answer_id'];
    $userId = $_SESSION['user_id'];

    // Check if the user has already liked this answer
    $checkQuery = $pdo->prepare("SELECT id FROM answer_likes WHERE answer_id = :answer_id AND user_id = :user_id");
    $checkQuery->bindParam(':answer_id', $answerId, PDO::PARAM_INT);
    $checkQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $checkQuery->execute();

    if ($checkQuery->rowCount() > 0) {
        // User has already liked this answer, so remove the like
        $deleteQuery = $pdo->prepare("DELETE FROM answer_likes WHERE user_id = :user_id AND answer_id = :answer_id");
        $deleteQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $deleteQuery->bindParam(':answer_id', $answerId, PDO::PARAM_INT);
        $deleteQuery->execute();
    } else {
        // User hasn't liked this answer yet, so insert a new like
        $insertQuery = $pdo->prepare("INSERT INTO answer_likes (user_id, answer_id) VALUES (:user_id, :answer_id)");
        $insertQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $insertQuery->bindParam(':answer_id', $answerId, PDO::PARAM_INT);
        $insertQuery->execute();
    }
}

// Redirect back to the original page
header("Location: ".$_SERVER['HTTP_REFERER']);
exit();
?>
