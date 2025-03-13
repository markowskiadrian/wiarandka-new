<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
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
            <h1><strong>Wia</strong>randka</h1>
        </div>
        <div class="right">
            <a href="">strona główna</a>
            <a href="">nasza misja</a>
            <a href="">kontakt</a>
            <button class="rounded">
                <i class="bi bi-person-fill"></i>
                <?php
                $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch();
                ?>
                <?= htmlspecialchars($current_user['username']) ?>
            </button>
        </div>
    </header>

    <section id="chats">
        <div id="user">
            <div class="left">
                <?php
                $stmt = $pdo->prepare("SELECT username, profile_pic FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $current_user = $stmt->fetch();
                ?>
                <img src="<?= htmlspecialchars($current_user['profile_pic']) ?>" alt="Zdjęcie profilowe">
                <h2><?= htmlspecialchars($current_user['username']) ?></h2>
            </div>
            <div class="right">
                <h2><i class="bi bi-gear-fill"></i></h2>
            </div>
        </div>

        <div>
            <?php if ($profile): ?>
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
        </div>
    </section>


</body>

</html>