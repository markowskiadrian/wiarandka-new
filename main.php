<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Obsługa wysyłania wiadomości z wykorzystaniem PRG, aby uniknąć ponownego wysyłania przy odświeżeniu
if (isset($_POST['send']) && !empty($_POST['message']) && isset($_POST['receiver_id'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    // Sprawdź, czy istnieje dopasowanie
    $stmt = $pdo->prepare("SELECT * FROM matches 
                          WHERE (user1_id = ? AND user2_id = ? AND swipe_action = 'like')
                          OR (user1_id = ? AND user2_id = ? AND swipe_action = 'like')");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $is_match = $stmt->fetch();

    if ($is_match) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
    }
    // Po zapisaniu wiadomości następuje przekierowanie (PRG)
    header("Location: main.php?receiver_id=" . $receiver_id);
    exit();
}

// Pobierz dane aktualnego użytkownika
$stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Pobierz dopasowania
$stmt = $pdo->prepare("SELECT DISTINCT u.user_id, u.username, u.profile_pic FROM matches m
                      JOIN users u ON (m.user1_id = u.user_id OR m.user2_id = u.user_id)
                      WHERE (m.user1_id = ? OR m.user2_id = ?)
                      AND u.user_id != ?
                      AND m.swipe_action = 'like'");
$stmt->execute([$user_id, $user_id, $user_id]);
$matches = $stmt->fetchAll();

// Jeśli został wybrany odbiorca, pobierz dane czatu
$receiver_id = isset($_GET['receiver_id']) ? $_GET['receiver_id'] : null;
$messages = [];
$receiver = [];

if ($receiver_id) {
    // Sprawdź, czy istnieje dopasowanie z odbiorcą
    $stmt = $pdo->prepare("SELECT * FROM matches 
                          WHERE (user1_id = ? AND user2_id = ? AND swipe_action = 'like')
                          OR (user1_id = ? AND user2_id = ? AND swipe_action = 'like')");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $is_match = $stmt->fetch();

    if ($is_match) {
        // Pobierz wiadomości
        $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_pic 
                              FROM messages m
                              JOIN users u ON m.sender_id = u.user_id
                              WHERE (m.sender_id = ? AND m.receiver_id = ?)
                              OR (m.sender_id = ? AND m.receiver_id = ?)
                              ORDER BY m.sent_at ASC");
        $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
        $messages = $stmt->fetchAll();

        // Pobierz dane odbiorcy
        $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE user_id = ?");
        $stmt->execute([$receiver_id]);
        $receiver = $stmt->fetch();
    } else {
        $receiver_id = null;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wiarandka - strona główna</title>
    <link rel="stylesheet" href="css/default.css">
    <link rel="stylesheet" href="css/main.css">
    <style>
        /* Style czatu */
        .chat-container { height: calc(100vh - 200px); display: flex; flex-direction: column; }
        .messages { flex: 1; overflow-y: auto; padding: 10px; border: 1px solid #ddd; margin: 10px 0; }
        .message { margin: 10px 0; padding: 10px; border-radius: 5px; max-width: 70%; }
        .received { background: #f1f1f1; }
        .sent { background: #e3f2fd; margin-left: auto; }
        .message-header { display: flex; align-items: center; margin-bottom: 5px; }
        .profile-pic { width: 40px; height: 40px; border-radius: 50%; margin-right: 10px; }
        .time { font-size: 0.8em; color: #666; margin-left: auto; }
        .chat-form { display: flex; gap: 10px; margin-top: auto; }
        .chat-form textarea { flex: 1; padding: 10px; resize: none; }
        
        /* Panel z danymi użytkownika przywrócony */
        #user-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }
        #user-panel .left {
            display: flex;
            align-items: center;
        }
        #user-panel img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        #user-panel .right {\n            font-size: 1.2em;\n        }\n    </style>
</head>
<body>
    <header>
        <div class="left">
            <img src="images/logo.svg" alt="Logo Wiarandka">
            <h1><strong>Wia</strong>randka</h1>
        </div>
        <div class="right">
            <a href="main.php">strona główna</a>
            <a href="matches.php">dopasowania</a>
            <a href="settings.php">ustawienia</a>
            <button class="rounded">
                <i class="bi bi-person-fill"></i>
                <?= htmlspecialchars($current_user['username']) ?>
            </button>
        </div>
    </header>
    
    <div class="main-container">
        <section id="chats">
            <!-- Panel użytkownika (przywrócony) -->
            <div id="user-panel">
                <div class="left">
                    <img src="<?= htmlspecialchars($current_user['profile_pic']) ?>" alt="Zdjęcie profilowe">
                    <h2><?= htmlspecialchars($current_user['username']) ?></h2>
                </div>
                <div class="right">
                    <a href="settings.php"><i class="bi bi-gear-fill"></i></a>
                </div>
            </div>
            <div class="matches-list">
                <h3>Twoje dopasowania</h3>
                <?php if ($matches): ?>
                    <?php foreach ($matches as $match): ?>
                        <a href="main.php?receiver_id=<?= $match['user_id'] ?>" class="match">
                            <img src="<?= htmlspecialchars($match['profile_pic']) ?>" alt="Profil">
                            <div>
                                <h4><?= htmlspecialchars($match['username']) ?></h4>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nie masz jeszcze dopasowań</p>
                <?php endif; ?>
            </div>
        </section>
        
        <section id="interactive">
            <?php if ($receiver_id && $receiver): ?>
                <div class="chat-container">
                    <div class="chat-header">
                        <img src="<?= htmlspecialchars($receiver['profile_pic']) ?>" class="profile-pic">
                        <h3><?= htmlspecialchars($receiver['username']) ?></h3>
                    </div>
                    
                    <div class="messages">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?= $msg['sender_id'] == $user_id ? 'sent' : 'received' ?>">
                                <div class="message-header">
                                    <img src="<?= htmlspecialchars($msg['profile_pic']) ?>" class="profile-pic">
                                    <strong><?= htmlspecialchars($msg['username']) ?></strong>
                                    <span class="time"><?= date('H:i', strtotime($msg['sent_at'])) ?></span>
                                </div>
                                <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" class="chat-form">
                        <input type="hidden" name="receiver_id" value="<?= $receiver_id ?>">
                        <textarea name="message" placeholder="Napisz wiadomość..." required></textarea>
                        <button type="submit" name="send" class="rounded">Wyślij</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="placeholder">
                    <img src="images/chat-icon.svg" alt="Ikona czatu">
                    <p>Wybierz rozmówcę, aby rozpocząć konwersację</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
