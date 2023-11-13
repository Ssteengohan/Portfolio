<?php
session_start();

// database connection
require_once('sql/db.php');

// timezone to Europe/Amsterdam
date_default_timezone_set('Europe/Amsterdam');
// tag filter
$tagFilter = '';
if (isset($_GET['tag'])) {
    $tag = urldecode($_GET['tag']);
    $tagFilter = ' AND t.tag_name = :tag';
}
// query to get all questions
$query = $pdo->prepare("SELECT q.id, q.title, q.question, q.posted_at, q.user_id, p.first_name, p.last_name,
                        GROUP_CONCAT(t.tag_name ORDER BY t.tag_name DESC SEPARATOR ', ') as tags 
                        FROM questions q 
                        INNER JOIN users u ON q.user_id = u.id 
                        INNER JOIN profile p ON u.id = p.user_id 
                        LEFT JOIN question_tags qt ON q.id = qt.question_id 
                        LEFT JOIN tags t ON qt.tag_id = t.id 
                        WHERE 1=1 $tagFilter
                        GROUP BY q.id 
                        ORDER BY q.posted_at DESC");

if (isset($_GET['tag'])) {
    $query->bindParam(':tag', $tag);
}

$query->execute();// execute query
$questions = $query->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {// edit question
    $questionId = $_POST['question_id'];
    $newTitle = $_POST['subject'];
    $newDescription = $_POST['description'];

    $stmt = $pdo->prepare("UPDATE questions 
                           SET title = :title, question = :question, posted_at = NOW() 
                           WHERE id = :id");
    $stmt->bindParam(':title', $newTitle);
    $stmt->bindParam(':question', $newDescription);
    $stmt->bindParam(':id', $questionId);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Question update failed. Please try again later.');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {// delete question
    $questionIdToDelete = $_POST['question_id'];

    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = :id");
    $stmt->bindParam(':id', $questionIdToDelete);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Question deletion failed. Please try again later.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link type="text/css" rel="stylesheet" href="css\main.css">
</head>
<body>
<?php include('navbar.php'); ?>
<section class="main-container">
    <div class="container">
        <?php
        $loggedIn = isset($_SESSION['user_id']);
        if ($loggedIn) { ?>
            <div class="question-button__container">
                <a href="PostQuestion.php">Ask a question</a>
            </div>
        <?php } else { ?>
            <div class="question-button__container">
                <a href="login.php">Login to ask a question</a>
            </div>
        <?php } ?>

        <div class="questions-header__container">
            <h2>Recently Asked Questions</h2>
        </div>
        <div class="questions">
            <?php foreach ($questions as $question) { ?>
                <div class="questions-container">
                    <?php if ($loggedIn && $question['user_id'] == $_SESSION['user_id']) { ?>
                        <div class="question-edit-delete">
                            <button class="button open-button" data-modal-id="modal<?php echo $question['id']; ?>">Edit Question</button>
                            <form method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this question?');">
                                <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                <input type="submit" name="delete" class="button delete-button" value="Delete Question">
                            </form>
                        </div>
                    <?php } ?>
                    <a href="question.php?id=<?php echo $question['id']; ?>">
                        <div class="question-user">
                            <h3>Question Asked By: <?php echo htmlspecialchars($question['first_name']) . ' ' . htmlspecialchars($question['last_name']); ?></h3>
                        </div>
                        <div class="question-subject">
                            <h3>Question Subject: <?php echo htmlspecialchars($question['title']); ?></h3>
                        </div>
                    </a>
                        <div class="question-Tag">
                            <h3>Question Tag:
                                <?php if (!empty($question['tags'])) { 
                                    $tags = explode(', ', $question['tags']); 
                                    foreach ($tags as $tag) { ?>
                                        <a href="index.php?tag=<?php echo urlencode($tag); ?>"><?php echo htmlspecialchars($tag); ?></a> 
                                    <?php }
                                } else { 
                                    echo 'No tags';
                                } ?>
                            </h3>
                        </div>
                    <a href="question.php?id=<?php echo $question['id']; ?>">

                        <div class="question-description">
                            <h3>Question Description: <?php echo htmlspecialchars($question['question']); ?></h3>
                        </div>
                        <div class="question-time">
                            <h3>Question Time Posted: <?php echo htmlspecialchars($question['posted_at']); ?></h3>
                        </div>
                    </a>
                    <dialog class="modal" id="modal<?php echo $question['id']; ?>">
                        <div class="edit-form">
                            <div class="edit-title">
                                <h3>Edit Question</h3>
                            </div>
                            <div class="edit-question">
                                <form method="post">
                                    <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
                                    <label for="subject">Subject</label>
                                    <input type="text" name="subject" class="subject" placeholder="Subject" value="<?php echo htmlspecialchars($question['title']); ?>">
                                    <label for="description">Description</label>
                                    <textarea name="description" class="description" cols="30" rows="10" placeholder="Description"><?php echo htmlspecialchars($question['question']); ?></textarea>
                                    <input type="submit" name="submit" class="close-button" value="Edit Question">
                                    <input type="button" class="close-button" data-modal-id="modal<?php echo $question['id']; ?>" value="Cancel">
                                </form>
                            </div>
                        </div>
                    </dialog>
                </div>
            <?php } ?>
        </div>
    </div>
</section>

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
