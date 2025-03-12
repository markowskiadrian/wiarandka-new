<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(!isset($_GET['receiver_id'])) {
    header("Location: matches.php");
    exit();
}

$receiver_id = $_GET['receiver_id'];

// Verify match exists
$stmt = $pdo->prepare("SELECT * FROM matches 
                      WHERE (user1_id = ? AND user2_id = ? AND swipe_action = 'like')
                      OR (user1_id = ? AND user2_id = ? AND swipe_action = 'like')");
$stmt->execute([$_SESSION['user_id'], $receiver_id, $receiver_id, $_SESSION['user_id']]);
$is_match = $stmt->fetch();

if(!$is_match) {
    header("Location: matches.php");
    exit();
}

// Handle message sending
if(isset($_POST['send']) && !empty($_POST['message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $receiver_id, $message]);
}

// Get messages with user details
$stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_pic 
                      FROM messages m
                      JOIN users u ON m.sender_id = u.user_id
                      WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                      OR (m.sender_id = ? AND m.receiver_id = ?) 
                      ORDER BY m.sent_at ASC");
$stmt->execute([$_SESSION['user_id'], $receiver_id, $receiver_id, $_SESSION['user_id']]);
$messages = $stmt->fetchAll();

// Get receiver details
$stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$receiver_id]);
$receiver = $stmt->fetch();

// Function to format time difference
function timeAgo($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d > 7) {
        return $ago->format('M j, Y');
    } elseif ($diff->d > 0) {
        return $diff->d . ' days ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hours ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minutes ago';
    } else {
        return 'Just now';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat with <?= htmlspecialchars($receiver['username']) ?></title>
    <style>
        .chat-container { max-width: 800px; margin: 0 auto; }
        .messages { margin: 20px 0; padding: 10px; border: 1px solid #ddd; height: 400px; overflow-y: auto; }
        .message { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .received { background: #f1f1f1; }
        .sent { background: #e3f2fd; margin-left: 20%; }
        .message-header { display: flex; align-items: center; margin-bottom: 5px; }
        .profile-pic { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
        .time { font-size: 0.8em; color: #666; margin-left: auto; }
        .back-btn { margin-bottom: 20px; }
        form { display: flex; gap: 10px; }
        textarea { flex: 1; padding: 10px; }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="back-btn">
            <a href="matches.php">← Back to Matches</a> | 
            <a href="settings.php">Settings</a> | 
            <a href="main.php">Main Menu</a>
        </div>
        
        <h2>Chat with <?= htmlspecialchars($receiver['username']) ?></h2>
        
        <div class="messages">
            <?php 
            $prevTime = null;
            foreach($messages as $msg): 
                $currentTime = new DateTime($msg['sent_at']);
                $showTime = true;
                
                if($prevTime) {
                    $diff = $currentTime->diff($prevTime);
                    $minutesDiff = ($diff->d * 1440) + ($diff->h * 60) + $diff->i;
                    $showTime = $minutesDiff > 2;
                }
                
                $prevTime = $currentTime;
            ?>
                <div class="message <?= $msg['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received' ?>">
                    <div class="message-header">
                        <img src="<?= htmlspecialchars($msg['profile_pic']) ?>" class="profile-pic" alt="<?= htmlspecialchars($msg['username']) ?>">
                        <strong><?= htmlspecialchars($msg['username']) ?></strong>
                        <?php if($showTime): ?>
                            <span class="time"><?= timeAgo($msg['sent_at']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="receiver_id" value="<?= $receiver_id ?>">
            <textarea name="message" placeholder="Type your message..." required></textarea>
            <button type="submit" name="send">Send</button>
        </form>
    </div>
</body>
</html>