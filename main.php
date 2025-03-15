<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Pobranie danych aktualnego użytkownika, w tym płci
$stmt = $pdo->prepare("SELECT username, profile_pic, gender FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch();

// Obsługa swipe'owania (przeniesione ze swipe.php)
if (isset($_POST['swipe']) && isset($_POST['target_id'])) {
    $target_id = $_POST['target_id'];
    $action = $_POST['swipe'];

    // Sprawdzenie płci użytkownika docelowego
    $stmt = $pdo->prepare("SELECT LOWER(gender) AS gender FROM users WHERE user_id = ?");
    $stmt->execute([$target_id]);
    $target_user = $stmt->fetch();
    if (!$target_user || $target_user['gender'] === strtolower($current_user['gender'])) {
        // Jeśli użytkownik ma tę samą płeć – operacja niedozwolona
        header("Location: main.php?view=sparowani");
        exit();
    }
    
    // Zapis akcji swipe
    $stmt = $pdo->prepare("INSERT INTO matches (user1_id, user2_id, swipe_action) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $target_id, $action]);
    
    // Sprawdzenie wzajemnego "like" jeśli akcja to 'like'
    if ($action == 'like') {
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE user1_id = ? AND user2_id = ? AND swipe_action = 'like'");
        $stmt->execute([$target_id, $user_id]);
        $mutual = $stmt->fetch();
        // (opcjonalnie: dodaj powiadomienie o dopasowaniu)
    }
    header("Location: main.php?view=sparowani");
    exit();
}

// Obsługa wysyłania wiadomości (PRG)
if (isset($_POST['send']) && !empty($_POST['message']) && isset($_POST['receiver_id'])) {
    $receiver_id = $_POST['receiver_id'];
    $message = trim($_POST['message']);

    // Sprawdzenie, czy istnieje match (dopasowanie)
    $stmt = $pdo->prepare("SELECT * FROM matches 
                          WHERE (user1_id = ? AND user2_id = ? AND swipe_action = 'like')
                          OR (user1_id = ? AND user2_id = ? AND swipe_action = 'like')");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $is_match = $stmt->fetch();

    if ($is_match) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $message]);
    }
    header("Location: main.php?view=messages&receiver_id=" . $receiver_id);
    exit();
}

// Ustalenie aktualnego widoku – domyślnie wiadomości
$view = isset($_GET['view']) ? $_GET['view'] : 'messages';

// Pobranie matchy dla czatu (widok wiadomości)
$stmt = $pdo->prepare("
    SELECT u.user_id, u.username, u.profile_pic 
    FROM users u
    WHERE EXISTS (
        SELECT 1 FROM matches m
        WHERE m.user1_id = ? AND m.user2_id = u.user_id AND m.swipe_action = 'like'
    ) 
    AND EXISTS (
        SELECT 1 FROM matches m
        WHERE m.user1_id = u.user_id AND m.user2_id = ? AND m.swipe_action = 'like'
    )
    AND u.user_id != ?
");
$stmt->execute([$user_id, $user_id, $user_id]);
$matches = $stmt->fetchAll();

// Obsługa czatu – jeśli widok wiadomości i wybrany został odbiorca
$receiver_id = (isset($_GET['receiver_id']) && $view === 'messages') ? $_GET['receiver_id'] : null;
$messages = [];
$receiver = [];
if ($receiver_id) {
    $stmt = $pdo->prepare("SELECT * FROM matches 
                          WHERE (user1_id = ? AND user2_id = ? AND swipe_action = 'like')
                          OR (user1_id = ? AND user2_id = ? AND swipe_action = 'like')");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $is_match = $stmt->fetch();

    if ($is_match) {
        $stmt = $pdo->prepare("SELECT m.*, u.username, u.profile_pic 
                              FROM messages m
                              JOIN users u ON m.sender_id = u.user_id
                              WHERE (m.sender_id = ? AND m.receiver_id = ?)
                              OR (m.sender_id = ? AND m.receiver_id = ?)
                              ORDER BY m.sent_at ASC");
        $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
        $messages = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE user_id = ?");
        $stmt->execute([$receiver_id]);
        $receiver = $stmt->fetch();
    } else {
        $receiver_id = null;
    }
}

// Jeśli widok 'sparowani' – pobierz kandydata do swipe'owania z uwzględnieniem płci
$candidate = null;
if ($view === 'sparowani') {
    $current_gender = strtolower($current_user['gender']);
    $opposite_gender = ($current_gender === 'male') ? 'female' : 'male';
    $stmt = $pdo->prepare("SELECT * FROM users 
                           WHERE user_id != ? 
                           AND LOWER(gender) = ? 
                           AND user_id NOT IN (SELECT user2_id FROM matches WHERE user1_id = ?)
                           ORDER BY RAND() LIMIT 1");
    $stmt->execute([$user_id, $opposite_gender, $user_id]);
    $candidate = $stmt->fetch();
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
</head>

<body>
    <header>
        <div class="left">
            <img src="images/logo.svg" alt="Logo Wiarandka">
            <h1><strong>wia</strong>randka</h1>
        </div>
        <div class="right">
            <a href="">Strona główna</a>
            <a href="">Nasza misja</a>
            <a href="">Kontakt</a>
            <button class="rounded">
                <i class="bi bi-person-fill"></i>
                <?= htmlspecialchars($current_user['username']) ?>
            </button>
        </div>
    </header>

    <div class="main-container">
        <section id="chats">
            <div id="user-panel">
                <div class="left">
                    <img src="<?= htmlspecialchars($current_user['profile_pic']) ?>" alt="Zdjęcie profilowe">
                    <h2><?= htmlspecialchars($current_user['username']) ?></h2>
                </div>
                <div class="right">
                    <a href="settings.php"><i class="bi bi-gear-fill"></i></a>
                </div>
            </div>
            <!-- Nawigacja między widokami -->
            <div class="tabs">
                <a href="main.php?view=messages" class="<?= $view === 'messages' ? 'active' : '' ?>">Wiadomości</a>
                <a href="main.php?view=sparowani" class="<?= $view === 'sparowani' ? 'active' : '' ?>">Swipes</a>
            </div>
            <?php if ($view === 'messages'): ?>
                <div class="matches-list">  
                    <?php if ($matches): ?>
                        <?php foreach ($matches as $match): ?>
                            <hr>
                            <div class="match<?= ($receiver_id == $match['user_id']) ? ' selected' : ''; ?>">
                                <a href="main.php?view=messages&receiver_id=<?= $match['user_id'] ?>">
                                    <img src="<?= htmlspecialchars($match['profile_pic']) ?>" alt="Profil">
                                    <div>
                                        <h4><?= htmlspecialchars($match['username']) ?></h4>
                                    </div>
                                </a>
                            </div>
                            <hr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p id="info"><i class="bi bi-hearts"></i><br>Obecnie nikogo<br> tu nie ma. Przejdź do Swipes!</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- W widoku 'sparowani' lewa sekcja pozostaje pusta -->
                <div class="matches-list">
                    
                    <p id="info"><i class="bi bi-arrow-through-heart-fill"></i><br>Tysiące osób czeka na bycie Twoim matchem!</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="interactive">
            <?php if ($view === 'messages'): ?>
                <?php if ($receiver_id && $receiver): ?>
                    <div class="chat-container">
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
                            <button type="submit" name="send" class="rounded" id="sendbutton"><i class="bi bi-arrow-right-short"></i></button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="placeholder">
                        <p id="iconp"><i class="bi bi-chat-heart-fill"></i></p>
                        <p>Wybierz rozmówcę,<br>aby rozpocząć konwersację!</p>
                    </div>
                <?php endif; ?>
            <?php elseif ($view === 'sparowani'): ?>
                <div class="swipe-container">
                    <?php if ($candidate): ?>
                        <div class="card">
                            <img src="<?= htmlspecialchars($candidate['profile_pic']) ?>" alt="Profil">
                            <h3><?= htmlspecialchars($candidate['username']) ?></h3>
                            <!-- Dodatkowe informacje o kandydacie -->
                        </div>
                        <form method="POST" class="swipe-form">
                            <input type="hidden" name="target_id" value="<?= $candidate['user_id'] ?>">
                            <button type="submit" name="swipe" value="like" class="lovebutton"><i class="bi bi-heart-fill"></i></button>
                            <button type="submit" name="swipe" value="dislike" class="lovebutton"><i class="bi bi-x-lg"></i></button>
                        </form>
                    <?php else: ?>
                        <div class="placeholder">
                            <p id="iconp"><i class="bi bi-search-heart-fill"></i></p>
                            <p>Osoba taka jak Ty zasługuje <br>na kogoś perfekcyjnego.</p>
                            <p id="desc">Szukamy dla Ciebie nowych kandydatów.<br>Prosimy, odwiedź nas później!</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
