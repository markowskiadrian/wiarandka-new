<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user
$user_id = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT gender FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();
$target_gender = ($current_user['gender'] == 'Male') ? 'Female' : 'Male';


// Get potential matches that haven't been swiped yet
$stmt = $pdo->prepare("SELECT * FROM users 
                      WHERE user_id != ? 
                      AND gender = ?
                      AND user_id NOT IN (
                          SELECT user2_id FROM matches WHERE user1_id = ?
                      )
                      ORDER BY RAND() LIMIT 1");
$stmt->execute([$user_id, $target_gender, $user_id]);
$profile = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wiarandka - Main</title>
</head>
<body>
    <h2>Discover Matches</h2>
    <?php if($profile): ?>
        <div class="profile">
            <h3><?= htmlspecialchars($profile['username']) ?></h3>
            <p>Religion: <?= htmlspecialchars($profile['religion']) ?></p>
            <p>Denomination: <?= htmlspecialchars($profile['denomination']) ?></p>
            <p><?= htmlspecialchars($profile['bio']) ?></p>
            
            <form method="POST" action="swipe.php">
                <input type="hidden" name="target_id" value="<?= $profile['user_id'] ?>">
                <button type="submit" name="swipe" value="like">Like</button>
                <button type="submit" name="swipe" value="dislike">Dislike</button>
            </form>
        </div>
    <?php else: ?>
        <p>No more profiles to show!</p>
    <?php endif; ?>
    
    <p><a href="matches.php">View Matches</a> | <a href="logout.php">Logout</a></p>
</body>
</html>