<?php
session_start();

// Include your database connection file here
require_once('sql/db.php');

// Handle bio and age update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_SESSION['user_id']; // Ensure the user is authenticated

    if (isset($_POST['bio'])) {
        $newBio = htmlspecialchars($_POST['bio']);

        // Update bio in the database
        $updateBio = $pdo->prepare("UPDATE profile SET profile_bio = :bio WHERE user_id = :user_id");
        $updateBio->bindParam(':bio', $newBio);
        $updateBio->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateBio->execute();
    }

    if (isset($_POST['age']) && is_numeric($_POST['age']) && $_POST['age'] > 0) {
        $newAge = (int)$_POST['age'];

        // Update age in the database
        $updateAge = $pdo->prepare("UPDATE profile SET age = :age WHERE user_id = :user_id");
        $updateAge->bindParam(':age', $newAge, PDO::PARAM_INT);
        $updateAge->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $updateAge->execute();
    }

    // Refresh the page to see the updated bio and age
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Check if the user is logged in and fetch profile data
$profileData = null;
$questions = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Fetching profile data
    $query = $pdo->prepare("SELECT * FROM profile WHERE user_id = :user_id");
    $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $query->execute();
    $profileData = $query->fetch(PDO::FETCH_ASSOC);

    // Fetching questions data
    $questionQuery = $pdo->prepare(
        "SELECT q.*, GROUP_CONCAT(t.tag_name ORDER BY t.tag_name DESC SEPARATOR ', ') as tags
        FROM questions q
        LEFT JOIN question_tags qt ON q.id = qt.question_id
        LEFT JOIN tags t ON qt.tag_id = t.id
        WHERE q.user_id = :user_id
        GROUP BY q.id"
    );
    $questionQuery->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $questionQuery->execute();
    $questions = $questionQuery->fetchAll(PDO::FETCH_ASSOC);
}

// Handle the case when the user is not logged in or profile data is not available
if (!$profileData) {
    echo "You need to log in to view the profile.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Page</title>
</head>
<body>
<?php include('navbar.php'); ?>
<section class="Profile">
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <h2>Hey <?php echo htmlspecialchars($profileData['first_name'] . ' ' . $profileData['last_name']); ?></h2>
            </div>
            <div class="profile-age">
                <h3>Age: 
                    <?php if ($profileData['age'] <= 0) { ?>
                        <form method="post" action="">
                            <input type="number" name="age" min="1" placeholder="Update your age">
                            <input type="submit" value="Update Age">
                        </form>
                    <?php } else { ?>
                        <?php echo htmlspecialchars($profileData['age']); ?>
                    <?php } ?>
                </h3>
            </div>
            <div class="profile-job">
                <h3>Job Title: <?php echo htmlspecialchars($profileData['job_title']); ?></h3>
            </div>
            <div class="profile-bio">
                <h3>Bio:</h3>
                <?php if (empty(trim($profileData['profile_bio']))) { ?>
                    <form method="post" action="">
                        <textarea name="bio" cols="60" rows="10" placeholder="Update your bio"></textarea>
                        <input type="submit" value="Update Bio">
                    </form>
                <?php } else { ?>
                    <p><?php echo htmlspecialchars($profileData['profile_bio']); ?></p>
                <?php } ?>
            </div>
        </div>
        
        <div class="profile-question">
            <h2>Questions Asked (<?php echo count($questions); ?>)</h2>
            <?php
                foreach ($questions as $question) {
            ?>
            <div class="profile-question__container">
                <div class="profile-question__title">
                    <h3><?php echo htmlspecialchars($question['title']); ?></h3>
                </div>
                <div class="profile-question__description">
                    <h5><?php echo htmlspecialchars($question['question']); ?></h5>
                </div>
                <div class="profile-question__tags">
                    <h5>Tags: <?php echo htmlspecialchars($question['tags'] ?? 'No tags'); ?></h5>
                </div>
                <div class="profile-question__time">
                    <p>Question Time Posted: <?php echo htmlspecialchars($question['posted_at']); ?></p>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
</body>
</html>
