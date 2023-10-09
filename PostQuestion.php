<?php
require_once('sql/db.php');

session_start();

$loggedIn = isset($_SESSION['user_id']);
$userName = "";

if ($loggedIn) {
    $user_id = $_SESSION['user_id'];
    $user_query = $pdo->prepare("SELECT first_name FROM profile WHERE user_id = :user_id");
    $user_query->bindParam(':user_id', $user_id);
    $user_query->execute();
    $user = $user_query->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $userName = $user['first_name'];
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question = $_POST['subject'];
    $description = $_POST['description'];
    $tag = $_POST['tag'];
    $user_id = $loggedIn ? $_SESSION['user_id'] : 0; 

    $stmt = $pdo->prepare("INSERT INTO questions (title, question, user_id) VALUES (:question, :description, :user_id)");
    $stmt->bindParam(':question', $question);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        $question_id = $pdo->lastInsertId(); 

        if(!empty($tag)) {
            $tag_query = $pdo->prepare("SELECT id FROM tags WHERE tag_name = :tag");
            $tag_query->bindParam(':tag', $tag);
            $tag_query->execute();
            $tag_id = $tag_query->fetchColumn();

            if (!$tag_id) {
                $tag_insert = $pdo->prepare("INSERT INTO tags (tag_name) VALUES (:tag)");
                $tag_insert->bindParam(':tag', $tag);
                $tag_insert->execute();
                $tag_id = $pdo->lastInsertId(); 
            }

            $question_tag_insert = $pdo->prepare("INSERT INTO question_tags (question_id, tag_id) VALUES (:question_id, :tag_id)");
            $question_tag_insert->bindParam(':question_id', $question_id);
            $question_tag_insert->bindParam(':tag_id', $tag_id);
            $question_tag_insert->execute();
        }

        header("Location: index.php");
        exit();
    } else {
        echo "<script>alert('Question upload failed. Please try again later.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask a Question</title>
</head>
<body>
<?php include('navbar.php'); ?>

<section class="question">
    <div class="container">
        <div class="question-header">
            <h2 class="title">
                Hey <?php echo htmlspecialchars($userName); ?>, what's your question?
            </h2>
        </div>

        <div class="question-form__container">
            <form method="POST">
                <label for="subject">What is the subject of your question?</label>
                <input type="text" name="subject" id="subject" placeholder="Subject" required>

                <label for="tag">Tag</label>
                <input type="text" name="tag" id="tag" placeholder="Tag">

                <label for="description">Describe your question</label>
                <textarea name="description" id="description" cols="30" rows="10" placeholder="Description" required></textarea>
                
                <input type="submit" value="Post Question">
            </form>
        </div>
    </div>
</section>
</body>
</html>
