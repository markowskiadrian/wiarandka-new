<?php
session_start();
include 'config.php';

if(!isset($_SESSION['user_id']) || !isset($_POST['swipe'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$target_id = $_POST['target_id'];
$action = $_POST['swipe'];

// Record swipe
$stmt = $pdo->prepare("INSERT INTO matches (user1_id, user2_id, swipe_action) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $target_id, $action]);

// Check for mutual like
$stmt = $pdo->prepare("SELECT * FROM matches 
                      WHERE user1_id = ? AND user2_id = ? 
                      AND swipe_action = 'like'");
$stmt->execute([$target_id, $user_id]);
$mutual = $stmt->fetch();

if($mutual && $action == 'like') {
    // Create match notification (you can implement this)
}

header("Location: main.php");
exit();
?>