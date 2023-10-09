<?php
session_start();

require_once('sql/db.php');
date_default_timezone_set('Europe/Amsterdam');

$questionData = null;
$answers = null;

// Check if the user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Define the fill color based on the user's login status
$fillColor = $loggedIn ? 'gray' : 'black';

function hasUserLikedAnswer($pdo, $answerId, $userId) {
    $query = $pdo->prepare("SELECT id FROM answer_likes WHERE answer_id = :answer_id AND user_id = :user_id");
    $query->bindParam(':answer_id', $answerId, PDO::PARAM_INT);
    $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $query->execute();
    return $query->rowCount() > 0;
}

if (isset($_GET['id'])) {
    $questionId = $_GET['id'];

    // Fetch question data
    $query = $pdo->prepare("SELECT q.id, q.title, q.question, q.posted_at, q.user_id, p.first_name, p.last_name 
                        FROM questions q 
                        INNER JOIN users u ON q.user_id = u.id 
                        INNER JOIN profile p ON u.id = p.user_id 
                        WHERE q.id = :question_id");

    $query->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $query->execute();
    $questionData = $query->fetch(PDO::FETCH_ASSOC);

    // Fetch answers with user details using JOINs and order by likes in descending order
    $answersQuery = $pdo->prepare("SELECT a.id, a.answer_text, a.user_id, a.answer_time, p.first_name, p.last_name,
                        (SELECT COUNT(id) FROM answer_likes WHERE answer_id = a.id) as like_count 
                        FROM answers a
                        INNER JOIN users u ON a.user_id = u.id
                        INNER JOIN profile p ON u.id = p.user_id
                        WHERE a.question_id = :question_id
                        ORDER BY like_count DESC");
    $answersQuery->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $answersQuery->execute();
    $answers = $answersQuery->fetchAll(PDO::FETCH_ASSOC);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_answer']) && isset($_SESSION['user_id'])) {
    $answerText = $_POST['answer_text'];

    $insertQuery = $pdo->prepare("INSERT INTO answers (question_id, user_id, answer_text, answer_time) VALUES (:question_id, :user_id, :answer_text, NOW())");
    $insertQuery->bindParam(':question_id', $questionId, PDO::PARAM_INT);
    $insertQuery->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $insertQuery->bindParam(':answer_text', $answerText, PDO::PARAM_STR);
    if ($insertQuery->execute()) {
        header("Location: question.php?id=" . $questionId);
        exit();
    } else {
        echo "Error submitting the answer.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_answer'])) {
    $answerId = $_POST['answer_id'];
    $editedAnswerText = $_POST['edit_answer_text'];

    // Update the answer in the database and set answer_time to the current timestamp
    $updateQuery = $pdo->prepare("UPDATE answers SET answer_text = :edited_answer_text, answer_time = NOW() WHERE id = :answer_id");
    $updateQuery->bindParam(':edited_answer_text', $editedAnswerText, PDO::PARAM_STR);
    $updateQuery->bindParam(':answer_id', $answerId, PDO::PARAM_INT);

    if ($updateQuery->execute()) {
        // Redirect to the question page after editing
        header("Location: question.php?id=" . $questionId);
        exit();
    } else {
        echo "Error editing the answer.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete']) && isset($_POST['answer_id']) && isset($_SESSION['user_id'])) {
    $answerIdToDelete = $_POST['answer_id'];

    $deleteQuery = $pdo->prepare("DELETE FROM answers WHERE id = :answer_id AND user_id = :user_id");
    $deleteQuery->bindParam(':answer_id', $answerIdToDelete, PDO::PARAM_INT);
    $deleteQuery->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    if ($deleteQuery->execute()) {
        header("Location: question.php?id=" . $questionId);
        exit();
    } else {
        echo "Error deleting the answer.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Page</title>
    <link type="text/css" rel="stylesheet" href="css\main.css">
</head>
<body>
    <?php include('navbar.php'); ?>
    <div class="container">
        <?php if (!isset($_SESSION['user_id'])) { ?>
            <div class="question-button__container">
                <a href="login.php">Login to ask a question</a>
            </div>
        <?php } ?>
    </div>
    <section class="question">
        <div class="container">
            <?php if ($questionData) { ?>
                <div class="question-container">
                    <div class="question-header">
                        <h2 class="title">Question</h2>
                    </div>
                    <div class="question-title">
                        <h2 class="title"><?php echo htmlspecialchars($questionData['title']); ?></h2>
                    </div>
                    <div class="question-description">
                        <h3 class="description"><?php echo htmlspecialchars($questionData['question']); ?></h3>
                        <h5>Question Asked By: <?php echo htmlspecialchars($questionData['first_name'] . ' ' . $questionData['last_name']); ?></h5>
                        <h5>Question Time Posted: <?php echo htmlspecialchars($questionData['posted_at']); ?></h5>
                    </div>
                </div>
                <div class="answer">
                    <div class="answer-container">
                        <div class="answer-header">
                            <h2 class="title">Answers</h2>
                        </div>
                        <?php if ($answers) { 
                            foreach ($answers as $answer) {
                        ?>
                        <div class="question-answer">
                            <textarea class="answer-description" cols="5" rows="2" readonly><?php echo htmlspecialchars($answer['answer_text']); ?></textarea>
                            <div class="answer-user-info">
                                <h5 class="answer-user">By <?php echo htmlspecialchars($answer['first_name'] . ' ' . $answer['last_name']); ?></h5>
    
                                <h5 class="answer-time"><?php echo htmlspecialchars($answer['answer_time']); ?><span>last updated</span></h5>
                                <?php if ($loggedIn) { ?>
                                    <div class="question-delete-edit">
                                        <button class="button open-button" data-modal-id="modal-<?php echo $answer['id']; ?>">Edit Answer</button>
                                        <form method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this answer?');">
                                            <input type="hidden" name="answer_id" value="<?php echo $answer['id']; ?>">
                                            <input type="submit" name="delete" class="button delete-button" value="Delete Answer">
                                        </form>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="like-container">
                                <p><?php echo htmlspecialchars($answer['like_count']); ?></p>
                                <form method="POST" action="like.php">
                                    <input type="hidden" name="answer_id" value="<?php echo htmlspecialchars($answer['id']); ?>">
                                    <button type="submit" name="like">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512" style="fill: <?php echo $fillColor; ?>;"><!-- Your SVG path data here --><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M323.8 34.8c-38.2-10.9-78.1 11.2-89 49.4l-5.7 20c-3.7 13-10.4 25-19.5 35l-51.3 56.4c-8.9 9.8-8.2 25 1.6 33.9s25 8.2 33.9-1.6l51.3-56.4c14.1-15.5 24.4-34 30.1-54.1l5.7-20c3.6-12.7 16.9-20.1 29.7-16.5s20.1 16.9 16.5 29.7l-5.7 20c-5.7 19.9-14.7 38.7-26.6 55.5c-5.2 7.3-5.8 16.9-1.7 24.9s12.3 13 21.3 13L448 224c8.8 0 16 7.2 16 16c0 6.8-4.3 12.7-10.4 15c-7.4 2.8-13 9-14.9 16.7s.1 15.8 5.3 21.7c2.5 2.8 4 6.5 4 10.6c0 7.8-5.6 14.3-13 15.7c-8.2 1.6-15.1 7.3-18 15.1s-1.6 16.7 3.6 23.3c2.1 2.7 3.4 6.1 3.4 9.9c0 6.7-4.2 12.6-10.2 14.9c-11.5 4.5-17.7 16.9-14.4 28.8c.4 1.3 .6 2.8 .6 4.3c0 8.8-7.2 16-16 16H286.5c-12.6 0-25-3.7-35.5-10.7l-61.7-41.1c-11-7.4-25.9-4.4-33.3 6.7s-4.4 25.9 6.7 33.3l61.7 41.1c18.4 12.3 40 18.8 62.1 18.8H384c34.7 0 62.9-27.6 64-62c14.6-11.7 24-29.7 24-50c0-4.5-.5-8.8-1.3-13c15.4-11.7 25.3-30.2 25.3-51c0-6.5-1-12.8-2.8-18.7C504.8 273.7 512 257.7 512 240c0-35.3-28.6-64-64-64l-92.3 0c4.7-10.4 8.7-21.2 11.8-32.2l5.7-20c10.9-38.2-11.2-78.1-49.4-89zM32 192c-17.7 0-32 14.3-32 32V448c0 17.7 14.3 32 32 32H96c17.7 0 32-14.3 32-32V224c0-17.7-14.3-32-32-32H32z"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php
                            }
                        }
                        if (isset($_SESSION['user_id'])) {
                        ?>
                        <div class="answer-question">
                            <form method="POST">
                                <textarea placeholder="Answer" name="answer_text"></textarea>
                                <input type="submit" name="submit_answer" value="Submit">
                            </form>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </section>

    <!-- Edit Answer Modals -->
    <?php if ($answers) {
        foreach ($answers as $answer) {
    ?>
    <dialog class="modal" id="modal-<?php echo $answer['id']; ?>">
        <div class="edit-form">
            <form method="POST" action="">
                <h4>Edit Answer</h4>
                <input type="hidden" name="answer_id" value="<?php echo $answer['id']; ?>">
                <textarea name="edit_answer_text"><?php echo htmlspecialchars($answer['answer_text']); ?></textarea>
                <input type="submit" name="edit_answer" value="Edit Answer">
                <input type="submit" class="close-button" data-modal-id="modal-<?php echo $answer['id']; ?>" value="Cancel">
            </form>
        </div>
    </dialog>
    <?php
        }
    }
    ?>

<script>
    const openModal = document.querySelectorAll('.open-button');
    const closeModal = document.querySelectorAll('.close-button');

    openModal.forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-id');
            const modal = document.getElementById(modalId);
            modal.showModal();
        });
    });

    closeModal.forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal-id');
            const modal = document.getElementById(modalId);
            modal.close();
        });
    });
</script>
</body>
</html>
