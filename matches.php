<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get mutual matches
$stmt = $pdo->prepare("SELECT u.* FROM matches m1
                      JOIN matches m2 ON m1.user1_id = m2.user2_id AND m1.user2_id = m2.user1_id
                      JOIN users u ON m2.user1_id = u.user_id
                      WHERE m1.user1_id = ? AND m1.swipe_action = 'like' AND m2.swipe_action = 'like'");
$stmt->execute([$_SESSION['user_id']]);
$matches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Matches</title>
</head>
<body>
    <h2>Your Matches</h2>
    
    <?php if(count($matches) > 0): ?>
        <?php foreach($matches as $match): ?>
            <div class="match">
                <img src="<?= $match['profile_pic'] ?>" alt="Profile" style="max-width: 100px;">
                <h3><?= htmlspecialchars($match['username']) ?></h3>
                <p><?= htmlspecialchars($match['religion']) ?></p>
                <a href="chat.php?receiver_id=<?= $match['user_id'] ?>">Start Chat</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No matches yet! Keep swiping!</p>
    <?php endif; ?>
    
    <p><a href="main.php">Back to Swiping</a></p>
</body>
</html>