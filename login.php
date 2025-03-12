<?php
session_start();
include 'config.php';

if(isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])) {
        if($user['is_banned']) {
            echo "Your account has been banned!";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            header("Location: main.php");
            exit();
        }
    } else {
        echo "Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Wiarandka - Login</title>
</head>
<body>
    <h2>Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login">Login</button>
    </form>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>